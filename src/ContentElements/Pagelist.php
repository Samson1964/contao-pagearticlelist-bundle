<?php

declare(strict_types=1);

/**
 * Seiten- und Artikellisten für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoPagearticlelistBundle\ContentElements;

use Contao\StringUtil;

/**
 * Inhaltselement "Seitenliste".
 *
 * Gibt eine Liste ausgewählter Seiten aus — wahlweise nur die von Hand
 * ausgewählten, zusätzlich die direkten Unterseiten der aktuellen Seite oder
 * ganze Seitenbäume. Geschützte Seiten erscheinen ohne Verweis, damit die
 * Struktur sichtbar bleibt, der Inhalt aber nicht erreichbar ist.
 *
 * Die Ausgabe ist ein echter Seitenbaum aus verschachtelten <ul>-Listen, wie
 * ihn auch die Navigationsmodule des Contao-Kerns erzeugen — nicht eine flache
 * Liste mit CSS-Klassen zur optischen Einrückung.
 */
class Pagelist extends AbstractListElement
{
	/**
	 * Name des Frontend-Templates
	 * @var string
	 */
	protected $strTemplate = 'ce_page_list';

	/**
	 * Stellt die Seitenliste zusammen und übergibt sie an das Template.
	 *
	 * Die Methode wird von Contao aufgerufen, nachdem $this->Template angelegt
	 * wurde. Sie liefert nichts zurück, sondern füllt die Template-Variable
	 * "pages" mit der fertigen, verschachtelten <ul>-Auszeichnung des
	 * Seitenbaums — oder mit einer leeren Zeichenkette, wenn nichts konfiguriert
	 * ist oder keine Seite die Bedingungen erfüllt.
	 *
	 * Anders als in der Ursprungserweiterung werden hier weder $this->Template->id
	 * noch $this->Template->class gesetzt: Contao überschreibt beide nach dem
	 * Aufruf von compile() ohnehin wieder mit den eigenen Werten.
	 */
	protected function compile(): void
	{
		$intCurrentPageId = $this->getCurrentPageId();
		$arrSelectedIds = $this->getSelectedPageIds();
		$arrPageIds = $this->collectPageIds($intCurrentPageId);

		$objPages = $this->findPages($arrPageIds);

		if (null === $objPages)
		{
			$this->Template->pages = '';

			return;
		}

		$arrPages = array();

		foreach ($objPages as $objPage)
		{
			$intId = (int) $objPage->id;

			if (!$this->isPageVisible($objPage, $arrSelectedIds) || !$this->isSelectionVisible($intId, $arrSelectedIds))
			{
				continue;
			}

			$blnProtected = $this->isProtected($objPage->protected, $objPage->groups);
			$blnActive = $intCurrentPageId === $intId;

			// Die aktive Seite bekommt auf Wunsch keinen Verweis, genau wie eine
			// geschützte — beides gibt den Titel dann als <span> statt als <a> aus.
			$blnLinkable = !$blnProtected && !($blnActive && $this->article_list_no_active_link);

			// Die CSS-Klasse entsteht erst in renderTree(): Ob ein Eintrag die
			// Klasse "submenu" bekommt, steht erst fest, wenn seine Unterebene
			// gerendert ist — und die kann leer bleiben, obwohl der Knoten
			// Kinder hat.
			$arrPages[] = array
			(
				'id'        => $intId,
				'level'     => $this->arrLevels[$intId] ?? 0,
				// Die Titel werden hier maskiert und in renderTree() unmaskiert
				// ausgegeben — dasselbe Vorgehen wie in den Navigationsmodulen
				// des Contao-Kerns.
				'name'      => StringUtil::specialchars($objPage->title),
				'title'     => StringUtil::specialchars($objPage->pageTitle ?: $objPage->title),
				'link'      => $blnLinkable ? $this->generatePageUrl($objPage) : '',
				'protected' => $blnProtected,
				'active'    => $blnActive,
			);
		}

		$arrPages = $this->sortByPageOrder($arrPages, $arrPageIds);

		$this->Template->pages = $this->renderTree($this->buildTree($arrPages));
	}

	/**
	 * Ordnet eine nach Ebenen sortierte flache Liste zu einem Seitenbaum an.
	 *
	 * Die Eingabe ist bereits in der richtigen Reihenfolge (siehe
	 * AbstractListElement::collectPageIds()) — jede Seite steht unmittelbar vor
	 * ihrem eigenen Unterbaum. Für die Verschachtelung genügt deshalb ein
	 * Vergleich mit der zuletzt offenen Ebene; ein Stapel von Knoten hält fest,
	 * wo das nächste Element einsortiert wird.
	 *
	 * Fehlt eine Zwischenseite — weil sie unveröffentlicht, im Menü versteckt
	 * oder über "Manuelle Seitenauswahl nicht anzeigen" ausgeblendet ist —,
	 * klafft eine Lücke in den Ebenennummern. Der Stapel schließt diese Lücke
	 * von selbst: Jede Seite hängt sich unter die zuletzt verbliebene Seite mit
	 * einer niedrigeren Ebene, unabhängig davon, wie groß der Sprung in der
	 * ursprünglichen Ebenennummer war. Ohne diese Regel entstünde eine Kette
	 * leerer Verschachtelungen ohne umschließendes Element.
	 *
	 * @param array<int,array<string,mixed>> $arrItems Aufbereitete Datensätze in
	 *                                                 Ausgabereihenfolge, jeder
	 *                                                 mit dem Schlüssel "level"
	 *
	 * @return array<int,object> Die Wurzelknoten des Baums. Jeder Knoten trägt
	 *                           die übrigen Schlüssel von $arrItems als
	 *                           Eigenschaft sowie "children" mit den gleich
	 *                           aufgebauten Kindknoten
	 */
	protected function buildTree(array $arrItems): array
	{
		$arrRoots = array();
		$arrStack = array();
		$arrStackLevels = array();

		foreach ($arrItems as $arrItem)
		{
			$intLevel = $arrItem['level'];
			unset($arrItem['level']);

			$objNode = (object) $arrItem;
			$objNode->children = array();

			while (!empty($arrStackLevels) && end($arrStackLevels) >= $intLevel)
			{
				array_pop($arrStack);
				array_pop($arrStackLevels);
			}

			if (empty($arrStack))
			{
				$arrRoots[] = $objNode;
			}
			else
			{
				end($arrStack)->children[] = $objNode;
			}

			$arrStack[] = $objNode;
			$arrStackLevels[] = $intLevel;
		}

		return $arrRoots;
	}

	/**
	 * Rendert einen Seitenbaum als verschachtelte <ul>-Struktur.
	 *
	 * Die Auszeichnung folgt der Navigation und der Sitemap des Contao-Kerns
	 * (siehe dessen Template navigation/nav_default.html5):
	 *
	 * * Die <ul> jeder Ebene trägt die Klasse "level_N", beginnend bei 1.
	 * * Ein Eintrag mit Unterebene bekommt die Klasse "submenu" und am
	 *   Verweiselement zusätzlich aria-haspopup="true".
	 * * Die Klassen stehen sowohl am <li> als auch am Verweiselement, weil sich
	 *   in CSS je nach Zusammenspiel mit dem Theme mal das eine, mal das andere
	 *   besser ansprechen lässt — auch das macht der Kern so.
	 * * Der aktive Eintrag bekommt aria-current="page".
	 *
	 * Die Klassen "first" und "last" setzt der Kern seit Contao 5 nicht mehr
	 * (in 4.13 gab es sie noch in Module::renderNavigation()); sie fehlen hier
	 * deshalb ebenfalls, damit die Ausgabe auf beiden Generationen gleich ist.
	 *
	 * Die Methode baut die Auszeichnung selbst zusammen statt sie dem Template
	 * zu überlassen: Eine wechselnde Verschachtelungstiefe lässt sich in einer
	 * einzelnen, nicht rekursiven .html5-Datei nicht sauber abbilden, ohne
	 * schließende Tags über mehrere Schleifendurchläufe hinweg offen zu halten.
	 * Der Contao-Kern löst dasselbe Problem in Module::renderNavigation() auf
	 * demselben Weg — dort entsteht ebenfalls vorgefertigtes HTML je Ebene, das
	 * das Template nur noch ausgibt.
	 *
	 * @param array<int,object> $arrNodes Baumknoten dieser Ebene, wie von
	 *                                    buildTree() geliefert
	 * @param int               $intLevel Verschachtelungstiefe der aktuellen
	 *                                    Ebene, beginnend bei 1
	 *
	 * @return string Die fertige <ul>-Struktur dieser Ebene, oder eine leere
	 *                Zeichenkette wenn $arrNodes leer ist
	 */
	protected function renderTree(array $arrNodes, int $intLevel = 1): string
	{
		if (empty($arrNodes))
		{
			return '';
		}

		$strItems = '';

		foreach ($arrNodes as $objNode)
		{
			// Erst die Unterebene rendern: Ob "submenu" gesetzt wird, hängt am
			// tatsächlichen Ergebnis und nicht daran, ob der Knoten Kinder trägt —
			// eine Unterebene kann leer bleiben.
			$strSubitems = $this->renderTree($objNode->children, $intLevel + 1);

			// Reihenfolge wie in Module::compileNavigationRow(): active, submenu,
			// protected.
			$arrClasses = array();

			if ($objNode->active)
			{
				$arrClasses[] = 'active';
			}

			if ($strSubitems)
			{
				$arrClasses[] = 'submenu';
			}

			if ($objNode->protected)
			{
				$arrClasses[] = 'protected';
			}

			$strClass = !empty($arrClasses) ? ' class="' . implode(' ', $arrClasses) . '"' : '';
			$strHasPopup = $strSubitems ? ' aria-haspopup="true"' : '';
			$strCurrent = $objNode->active ? ' aria-current="page"' : '';

			if ($objNode->link)
			{
				$strLink = '<a href="' . StringUtil::specialcharsUrl($objNode->link) . '" title="' . $objNode->name . '"' . $strClass . $strCurrent . $strHasPopup . '>' . $objNode->title . '</a>';
			}
			else
			{
				$strLink = '<span' . $strClass . $strCurrent . $strHasPopup . '>' . $objNode->title . '</span>';
			}

			$strItems .= '<li' . $strClass . '>' . $strLink . $strSubitems . '</li>';
		}

		return '<ul class="level_' . $intLevel . '">' . $strItems . '</ul>';
	}
}
