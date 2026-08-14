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

			$arrClasses = array();

			if ($blnActive)
			{
				$arrClasses[] = 'active';
			}

			if ($blnProtected)
			{
				$arrClasses[] = 'protected';
			}

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
				'class'     => implode(' ', $arrClasses),
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
	 * Die umschließende <ul> jeder Ebene trägt die Klasse "level_N", genau wie
	 * bei den Navigationsmodulen des Contao-Kerns.
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
	 *                                   buildTree() geliefert
	 * @param int                $intLevel Verschachtelungstiefe der aktuellen
	 *                                     Ebene, beginnend bei 1
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
			if ($objNode->link)
			{
				$strLink = '<a href="' . StringUtil::specialcharsUrl($objNode->link) . '" title="' . $objNode->name . '">' . $objNode->title . '</a>';
			}
			else
			{
				$strLink = '<span>' . $objNode->title . '</span>';
			}

			$strClass = $objNode->class ? ' class="' . $objNode->class . '"' : '';

			$strItems .= '<li' . $strClass . '>' . $strLink . $this->renderTree($objNode->children, $intLevel + 1) . '</li>';
		}

		return '<ul class="level_' . $intLevel . '">' . $strItems . '</ul>';
	}
}
