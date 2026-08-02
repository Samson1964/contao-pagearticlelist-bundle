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
	 * "pages" mit einer Liste aus Namen, Titel, Adresse, Ebene und CSS-Klasse.
	 * Ist nichts konfiguriert oder trifft keine Seite die Bedingungen, bleibt die
	 * Liste leer und das Template gibt lediglich das leere Grundgerüst aus.
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
			$this->Template->pages = array();

			return;
		}

		$arrPages = array();

		foreach ($objPages as $objPage)
		{
			if (!$this->isPageVisible($objPage, $arrSelectedIds))
			{
				continue;
			}

			$intId = (int) $objPage->id;
			$blnProtected = $this->isProtected($objPage->protected, $objPage->groups);
			$blnActive = $intCurrentPageId === $intId;
			$intLevel = $this->arrLevels[$intId] ?? 0;

			$arrClasses = array('level' . $intLevel);

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
				// Die Titel werden hier maskiert und im Template unmaskiert
				// ausgegeben — dasselbe Vorgehen wie in den Navigationsmodulen
				// des Contao-Kerns.
				'name'      => StringUtil::specialchars($objPage->title),
				'title'     => StringUtil::specialchars($objPage->pageTitle ?: $objPage->title),
				'link'      => $blnProtected ? '' : $this->generatePageUrl($objPage),
				'protected' => $blnProtected,
				'level'     => $intLevel,
				'active'    => $blnActive,
				'class'     => implode(' ', $arrClasses),
			);
		}

		$this->Template->pages = $this->sortByPageOrder($arrPages, $arrPageIds);
	}
}
