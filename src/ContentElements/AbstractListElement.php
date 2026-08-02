<?php

declare(strict_types=1);

/**
 * Seiten- und Artikellisten für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoPagearticlelistBundle\ContentElements;

use Contao\ArticleModel;
use Contao\ContentElement;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\Date;
use Contao\Model\Collection;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\Routing\Exception\ExceptionInterface;

/**
 * Gemeinsamer Unterbau der beiden Inhaltselemente "Seitenliste" und "Artikelliste".
 *
 * Beide Elemente stellen dieselbe Frage: "Welche Seiten sollen ausgegeben werden?"
 * Sie unterscheiden sich erst danach — die Seitenliste gibt die Seiten selbst aus,
 * die Artikelliste die Artikel darin. Die Seitenermittlung, die Rechteprüfung und
 * der Aufbau der Frontend-Adressen liegen deshalb hier und nicht doppelt in beiden
 * Klassen.
 *
 * Die Klasse ersetzt außerdem die Contao-3-Altlasten der Ursprungserweiterung:
 * Statt der Konstanten TL_MODE, BE_USER_LOGGED_IN und FE_USER_LOGGED_IN, die es
 * seit Contao 5 nicht mehr gibt, wird über den Sicherheitsdienst und den
 * TokenChecker gefragt, und statt der entfallenen globalen Funktion deserialize()
 * kommt StringUtil::deserialize() zum Einsatz.
 */
abstract class AbstractListElement extends ContentElement
{
	/**
	 * Verschachtelungstiefe je Seiten-ID.
	 *
	 * Wird beim rekursiven Einsammeln der Unterseiten gefüllt und im Template als
	 * CSS-Klasse "levelN" ausgegeben, damit sich die Liste einrücken lässt.
	 *
	 * @var array<int,int>
	 */
	protected $arrLevels = array();

	/**
	 * Stellt die Liste der auszugebenden Seiten-IDs in Ausgabereihenfolge zusammen.
	 *
	 * Die Reihenfolge entsteht in drei Schritten und ist bewusst die Reihenfolge
	 * des Arrays und nicht die Sortierung aus der Datenbank — nur so stehen die
	 * Unterseiten direkt hinter ihrer Elternseite:
	 *
	 * 1. Die im Backend manuell ausgewählten Seiten bilden den Grundstock.
	 * 2. Ist "Unterseiten automatisch verlinken" gesetzt, werden die direkten
	 *    Unterseiten der aktuellen Seite vorangestellt.
	 * 3. Ist "Seiten rekursiv verlinken" gesetzt, wird hinter jeder bereits
	 *    gesammelten Seite deren kompletter Unterbaum eingefügt. Die Schleife
	 *    läuft von hinten nach vorn, damit das Einfügen die noch nicht
	 *    abgearbeiteten Positionen nicht verschiebt.
	 *
	 * @param int|null $intCurrentPageId ID der Seite, auf der das Element steht.
	 *                                   null, wenn sie sich nicht ermitteln ließ —
	 *                                   dann entfällt Schritt 2.
	 *
	 * @return array<int,int> Seiten-IDs ohne Dubletten. Leer, wenn nichts
	 *                        konfiguriert ist; das Element gibt dann nichts aus.
	 */
	protected function collectPageIds(?int $intCurrentPageId): array
	{
		$arrPageIds = $this->getSelectedPageIds();

		// Direkte Unterseiten der aktuellen Seite voranstellen
		if ($this->article_list_childrens && null !== $intCurrentPageId)
		{
			array_splice($arrPageIds, 0, 0, $this->getChildPages($intCurrentPageId, false));
		}

		// Unterbäume der bereits gesammelten Seiten hinter der jeweiligen Seite einfügen
		if ($this->article_list_recursive)
		{
			for ($i = \count($arrPageIds) - 1; $i >= 0; $i--)
			{
				$intLevel = ($this->arrLevels[$arrPageIds[$i]] ?? 0) + 1;

				array_splice($arrPageIds, $i + 1, 0, $this->getChildPages($arrPageIds[$i], true, $intLevel));
			}
		}

		// Eine Seite kann über die manuelle Auswahl und über einen Unterbaum
		// gleichzeitig hereinkommen; array_values stellt die Zählung von 0 an
		// wieder her, weil die Position später die Sortierung bestimmt.
		return array_values(array_unique($arrPageIds));
	}

	/**
	 * Liest die im Backend manuell ausgewählten Seiten aus dem Feld article_list_pages.
	 *
	 * Das Feld ist ein Blob und enthält üblicherweise ein serialisiertes Array.
	 * Ältere Datensätze aus der Ursprungserweiterung können aber auch eine einzelne
	 * ID als Zeichenkette enthalten, deshalb der Umweg über StringUtil::deserialize()
	 * mit erzwungenem Array.
	 *
	 * @return array<int,int> Seiten-IDs als Ganzzahlen, leeres Array wenn nichts
	 *                        ausgewählt ist
	 */
	protected function getSelectedPageIds(): array
	{
		$arrIds = StringUtil::deserialize($this->article_list_pages, true);

		// Leere Einträge entfernen und alles auf Ganzzahlen bringen. Die IDs gehen
		// später in eine SQL-Bedingung ein — ohne diese Wandlung stünde hier eine
		// Einfallstür für manipulierte Blob-Inhalte offen.
		return array_values(array_map('\intval', array_filter($arrIds, static fn ($v): bool => '' !== (string) $v)));
	}

	/**
	 * Sammelt die Unterseiten einer Seite ein und merkt sich dabei deren Ebene.
	 *
	 * Es werden ausschließlich reguläre Seiten berücksichtigt — Weiterleitungen,
	 * Fehlerseiten und Seitenwurzeln haben keine eigene Adresse und würden die
	 * Liste nur verunreinigen. Unveröffentlichte Seiten fallen heraus, sofern
	 * nicht gerade die Vorschau läuft.
	 *
	 * Seitennebenwirkung: Für jede gefundene Seite wird $this->arrLevels gefüllt.
	 *
	 * @param int  $intPid       ID der Elternseite
	 * @param bool $blnRecursive true = auch alle tieferen Ebenen einsammeln
	 * @param int  $intLevel     Ebene, die den gefundenen Seiten zugeordnet wird;
	 *                           die Rekursion zählt sie selbständig hoch
	 *
	 * @return array<int,int> Seiten-IDs in Sortierreihenfolge, leer wenn es keine
	 *                        passenden Unterseiten gibt
	 */
	protected function getChildPages(int $intPid, bool $blnRecursive = true, int $intLevel = 0): array
	{
		$arrColumns = array('tl_page.pid=?', "tl_page.type='regular'");
		$arrValues = array($intPid);

		if (!$this->isPreviewMode())
		{
			$arrColumns[] = $this->getPublishedCondition('tl_page');
		}

		$objPages = PageModel::findBy($arrColumns, $arrValues, array('order' => 'tl_page.sorting'));

		if (null === $objPages)
		{
			return array();
		}

		$arrPageIds = array();

		foreach ($objPages as $objPage)
		{
			$intId = (int) $objPage->id;

			$arrPageIds[] = $intId;
			$this->arrLevels[$intId] = $intLevel;

			if ($blnRecursive)
			{
				$arrPageIds = array_merge($arrPageIds, $this->getChildPages($intId, true, $intLevel + 1));
			}
		}

		return $arrPageIds;
	}

	/**
	 * Lädt die Seitendatensätze zu den übergebenen IDs.
	 *
	 * Die Reihenfolge der Rückgabe spielt keine Rolle, weil die aufrufenden
	 * Elemente anschließend nach der Position in $arrPageIds sortieren. Die
	 * Sortierung nach tl_page.sorting ist trotzdem gesetzt, damit die Ausgabe
	 * auch ohne Nachsortierung nachvollziehbar bleibt.
	 *
	 * @param array<int,int> $arrPageIds Vorher auf Ganzzahlen gebrachte Seiten-IDs
	 *
	 * @return Collection|null Die Seiten, oder null wenn keine der IDs (mehr)
	 *                         existiert oder alle unveröffentlicht sind
	 */
	protected function findPages(array $arrPageIds): ?Collection
	{
		if (empty($arrPageIds))
		{
			return null;
		}

		// Die IDs stammen aus getSelectedPageIds() bzw. getChildPages() und sind
		// dort bereits nach int gewandelt worden. Der zusätzliche intval hier ist
		// die zweite Sicherung, falls die Methode einmal von anderer Stelle
		// aufgerufen wird.
		$arrColumns = array('tl_page.id IN(' . implode(',', array_map('\intval', $arrPageIds)) . ')');

		if (!$this->isPreviewMode())
		{
			$arrColumns[] = $this->getPublishedCondition('tl_page');
		}

		return PageModel::findBy($arrColumns, null, array('order' => 'tl_page.sorting'));
	}

	/**
	 * Lädt die Artikel einer Seite in Sortierreihenfolge.
	 *
	 * ArticleModel bringt keinen passenden Finder mit: findPublishedByPidAndColumn()
	 * würde auf eine Spalte einschränken, findPublishedWithTeaserByPid() nur Artikel
	 * mit gesetztem Teaser liefern. Deshalb die Bedingungen von Hand.
	 *
	 * @param int $intPageId ID der Seite, deren Artikel gesucht werden
	 *
	 * @return Collection|null Die Artikel, oder null wenn die Seite keine
	 *                         (sichtbaren) Artikel enthält
	 */
	protected function findArticles(int $intPageId): ?Collection
	{
		$arrColumns = array('tl_article.pid=?');
		$arrValues = array($intPageId);

		if (!$this->isPreviewMode())
		{
			$arrColumns[] = $this->getPublishedCondition('tl_article');
		}

		return ArticleModel::findBy($arrColumns, $arrValues, array('order' => 'tl_article.sorting'));
	}

	/**
	 * Baut die SQL-Bedingung für "ist gerade veröffentlicht".
	 *
	 * Die Ursprungserweiterung hat nur das Häkchen "published" geprüft und die
	 * Felder start und stop übergangen — zeitgesteuert abgeschaltete Seiten und
	 * Artikel tauchten dadurch weiter in der Liste auf. Der Zeitstempel wird auf
	 * die volle Minute abgerundet, damit die erzeugte Bedingung innerhalb einer
	 * Minute gleich bleibt und der Seitencache greifen kann.
	 *
	 * @param string $strTable Tabellenname, entweder tl_page oder tl_article;
	 *                         wird nur als Feldpräfix eingesetzt und stammt
	 *                         ausschließlich aus dem eigenen Code
	 *
	 * @return string Die fertige Bedingung ohne führendes AND
	 */
	protected function getPublishedCondition(string $strTable): string
	{
		$intTime = Date::floorToMinute();

		return "$strTable.published='1' AND ($strTable.start='' OR $strTable.start<=$intTime) AND ($strTable.stop='' OR $strTable.stop>$intTime)";
	}

	/**
	 * Prüft, ob ein geschützter Datensatz für den aktuellen Besucher gesperrt ist.
	 *
	 * Ersetzt die Contao-3-Prüfung über die Konstanten BE_USER_LOGGED_IN und
	 * FE_USER_LOGGED_IN, die es seit Contao 5 nicht mehr gibt. Backend-Benutzer im
	 * Vorschaumodus sehen weiterhin alles; für Mitglieder entscheidet der
	 * Gruppen-Voter des Contao-Kerns, damit die Erweiterung dieselbe Logik
	 * verwendet wie die Navigationsmodule des Kerns.
	 *
	 * @param mixed $varProtected Inhalt des Feldes "protected"; Contao liefert je
	 *                            nach Herkunft '1', 1 oder true
	 * @param mixed $varGroups    Inhalt des Feldes "groups", serialisiertes Array
	 *                            oder bereits entpacktes Array
	 *
	 * @return bool true, wenn der Datensatz zwar aufgelistet, aber nicht verlinkt
	 *              werden darf; false, wenn er frei zugänglich ist
	 */
	protected function isProtected($varProtected, $varGroups): bool
	{
		if (!$varProtected)
		{
			return false;
		}

		$container = System::getContainer();
		$tokenChecker = $container->get('contao.security.token_checker');

		// Angemeldete Backend-Benutzer sollen beim Prüfen der Seite alles sehen
		if ($tokenChecker->hasBackendUser())
		{
			return false;
		}

		if (!$tokenChecker->hasFrontendUser())
		{
			return true;
		}

		$arrGroups = StringUtil::deserialize($varGroups, true);

		if (empty($arrGroups))
		{
			return true;
		}

		return !$container->get('security.helper')->isGranted(ContaoCorePermissions::MEMBER_IN_GROUPS, $arrGroups);
	}

	/**
	 * Erzeugt die Frontend-Adresse einer Seite.
	 *
	 * Ab Contao 5.3 ist PageModel::getFrontendUrl() als überholt gekennzeichnet und
	 * schreibt bei jedem Aufruf eine Deprecation-Meldung ins Protokoll; zuständig
	 * ist dort der Dienst contao.routing.content_url_generator. Den gibt es unter
	 * Contao 4.13 aber noch nicht, deshalb die Abfrage auf den Dienst statt einer
	 * Versionsabfrage — so läuft dieselbe Datei auf beiden Contao-Generationen ohne
	 * Meldungen.
	 *
	 * @param PageModel   $objPage   Die zu verlinkende Seite
	 * @param string|null $strParams Zusätzliche Adressbestandteile, etwa
	 *                               "/articles/meinalias"; null für die reine
	 *                               Seitenadresse
	 *
	 * @return string Die Adresse, oder eine leere Zeichenkette wenn sich für die
	 *                Seite keine Route erzeugen lässt (etwa weil die zugehörige
	 *                Seitenwurzel keine Domain besitzt). Die Aufrufer geben den
	 *                Titel dann ohne Verweis aus.
	 */
	protected function generatePageUrl(PageModel $objPage, ?string $strParams = null): string
	{
		$container = System::getContainer();

		try
		{
			if ($container->has('contao.routing.content_url_generator'))
			{
				$arrParams = null !== $strParams ? array('parameters' => $strParams) : array();

				return $container->get('contao.routing.content_url_generator')->generate($objPage, $arrParams);
			}

			return $objPage->getFrontendUrl($strParams);
		}
		catch (ExceptionInterface $e)
		{
			return '';
		}
	}

	/**
	 * Ermittelt die ID der Seite, auf der das Inhaltselement steht.
	 *
	 * Der Wert wird für die Option "Unterseiten automatisch verlinken" und für die
	 * Markierung der aktiven Seite gebraucht. Der reguläre Weg ist die Seite aus
	 * dem Request; steht die nicht zur Verfügung — etwa wenn das Element über ein
	 * Insert-Tag außerhalb des Seitenaufbaus gerendert wird —, führt der Umweg über
	 * den Artikel, in dem das Element liegt, zum Ziel.
	 *
	 * @return int|null Die Seiten-ID, oder null wenn sie sich nicht bestimmen lässt
	 */
	protected function getCurrentPageId(): ?int
	{
		$objPage = $this->getCurrentPage();

		if (null !== $objPage)
		{
			return (int) $objPage->id;
		}

		// Inhaltselemente liegen normalerweise in einem Artikel; ptable ist bei
		// Altbeständen leer, meint dann aber ebenfalls tl_article.
		if (!$this->ptable || 'tl_article' === $this->ptable)
		{
			$objArticle = ArticleModel::findByPk($this->pid);

			if (null !== $objArticle)
			{
				return (int) $objArticle->pid;
			}
		}

		return null;
	}

	/**
	 * Liefert die aktuell aufgerufene Seite.
	 *
	 * Zuerst wird das Request-Attribut "pageModel" gelesen, weil $GLOBALS['objPage']
	 * ab Contao 5.3 als überholt gilt. Beide Wege bleiben stehen, damit die Datei
	 * unter Contao 4.13 und Contao 5 gleichermaßen funktioniert.
	 *
	 * @return PageModel|null Die Seite, oder null bei einem Aufruf außerhalb des
	 *                        Frontends
	 */
	protected function getCurrentPage(): ?PageModel
	{
		$request = System::getContainer()->get('request_stack')->getCurrentRequest();

		if (null !== $request)
		{
			$objPage = $request->attributes->get('pageModel');

			if ($objPage instanceof PageModel)
			{
				return $objPage;
			}
		}

		return ($GLOBALS['objPage'] ?? null) instanceof PageModel ? $GLOBALS['objPage'] : null;
	}

	/**
	 * Sagt, ob gerade die Vorschau eines angemeldeten Backend-Benutzers läuft.
	 *
	 * Ersetzt die alte Abfrage des Cookies FE_PREVIEW, die seit Contao 4 nichts
	 * mehr aussagt: Der Vorschaumodus hängt seitdem am Sicherheitstoken und nicht
	 * mehr am Cookie.
	 *
	 * @return bool true in der Vorschau, sonst false
	 */
	protected function isPreviewMode(): bool
	{
		return System::getContainer()->get('contao.security.token_checker')->isPreviewMode();
	}

	/**
	 * Entscheidet, ob eine Seite trotz Menü-Ausblendung aufgelistet wird.
	 *
	 * Eine im Menü versteckte Seite erscheint nur, wenn das Häkchen "Im Menü
	 * versteckte Seiten einbeziehen" gesetzt ist oder wenn die Seite im Backend
	 * ausdrücklich von Hand ausgewählt wurde — eine bewusste Auswahl soll die
	 * Ausblendung überstimmen.
	 *
	 * @param PageModel      $objPage         Die zu prüfende Seite
	 * @param array<int,int> $arrSelectedIds  Die manuell ausgewählten Seiten-IDs
	 *
	 * @return bool true, wenn die Seite ausgegeben werden soll
	 */
	protected function isPageVisible(PageModel $objPage, array $arrSelectedIds): bool
	{
		if ($this->article_list_hidden || !$objPage->hide)
		{
			return true;
		}

		return \in_array((int) $objPage->id, $arrSelectedIds, true);
	}

	/**
	 * Sortiert die aufbereiteten Seiten in die Reihenfolge der eingesammelten IDs.
	 *
	 * Die Datenbank liefert nach tl_page.sorting, das reicht aber nicht: Bei einer
	 * rekursiven Auflistung muss jede Unterseite direkt hinter ihrer Elternseite
	 * stehen, und diese Reihenfolge steckt nur im ID-Array. Die Ursprungserweiterung
	 * hat dafür einen Sortierschlüssel mit dem Aufschlag 9000000 gebastelt, dessen
	 * zweiter Zweig nie erreicht wurde; die Position im Array führt zum selben
	 * Ergebnis und ist nachvollziehbar.
	 *
	 * @param array<int,array<string,mixed>> $arrItems   Aufbereitete Datensätze,
	 *                                                   jeder mit Schlüssel "id"
	 * @param array<int,int>                 $arrPageIds Die Seiten-IDs in
	 *                                                   Ausgabereihenfolge
	 *
	 * @return array<int,array<string,mixed>> Dieselben Datensätze, neu sortiert
	 */
	protected function sortByPageOrder(array $arrItems, array $arrPageIds): array
	{
		$arrOrder = array_flip($arrPageIds);

		usort(
			$arrItems,
			static fn (array $a, array $b): int => ($arrOrder[$a['id']] ?? PHP_INT_MAX) <=> ($arrOrder[$b['id']] ?? PHP_INT_MAX)
		);

		return $arrItems;
	}
}
