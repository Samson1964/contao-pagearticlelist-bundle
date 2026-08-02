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
use Contao\Model\Collection;
use Contao\PageModel;
use Contao\StringUtil;

/**
 * Inhaltselement "Artikelliste".
 *
 * Gibt zu jeder ausgewählten Seite deren Artikel aus, wahlweise mit
 * Seitenüberschrift und Artikelteaser. Seiten ohne Artikel erscheinen nicht.
 *
 * Der Bildblock der Ursprungserweiterung ist in Version 1.0.0 entfallen: Er hat
 * die Spalten addImage und singleSRC in tl_article ausgewertet, die es nur mit
 * der Contao-3-Erweiterung "zArticleImage" gab, und er stützte sich auf
 * Controller::addImageToTemplate(), das es seit Contao 5 nicht mehr gibt.
 */
class Articlelist extends AbstractListElement
{
	/**
	 * Name des Frontend-Templates
	 * @var string
	 */
	protected $strTemplate = 'ce_article_list';

	/**
	 * Stellt die Artikelliste zusammen und übergibt sie an das Template.
	 *
	 * Die Methode wird von Contao aufgerufen, nachdem $this->Template angelegt
	 * wurde, und liefert nichts zurück. Sie füllt zwei Template-Variablen:
	 * "pages" mit je einem Eintrag pro Seite samt der darin enthaltenen Artikel
	 * und "hlPage" mit der HTML-Auszeichnung der Seitenüberschrift — oder mit
	 * false, wenn keine Überschriften gewünscht sind.
	 *
	 * Seiten ohne sichtbare Artikel werden ausgelassen, damit keine leeren
	 * Überschriften stehen bleiben.
	 */
	protected function compile(): void
	{
		$intCurrentPageId = $this->getCurrentPageId();
		$arrSelectedIds = $this->getSelectedPageIds();
		$arrPageIds = $this->collectPageIds($intCurrentPageId);

		$this->Template->hlPage = $this->getPageHeadlineTag();

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

			$objArticles = $this->findArticles((int) $objPage->id);

			if (null === $objArticles)
			{
				continue;
			}

			$intId = (int) $objPage->id;
			$blnProtected = $this->isProtected($objPage->protected, $objPage->groups);
			$blnActive = $intCurrentPageId === $intId;

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
				'name'      => StringUtil::specialchars($objPage->title),
				'title'     => StringUtil::specialchars($objPage->pageTitle ?: $objPage->title),
				'link'      => $blnProtected ? '' : $this->generatePageUrl($objPage),
				'protected' => $blnProtected,
				'articles'  => $this->compileArticles($objArticles, $objPage, $blnProtected),
				'level'     => $this->arrLevels[$intId] ?? 0,
				'active'    => $blnActive,
				'class'     => implode(' ', $arrClasses),
			);
		}

		$this->Template->pages = $this->sortByPageOrder($arrPages, $arrPageIds);
	}

	/**
	 * Bereitet die Artikel einer Seite für das Template auf.
	 *
	 * @param Collection $objArticles      Die Artikel der Seite in
	 *                                     Sortierreihenfolge
	 * @param PageModel  $objPage          Die Seite, zu der die Artikel gehören;
	 *                                     wird für den Aufbau der Adresse gebraucht
	 * @param bool       $blnPageProtected true, wenn bereits die Seite gesperrt
	 *                                     ist — dann sind auch alle Artikel darin
	 *                                     gesperrt und die Rechteprüfung je Artikel
	 *                                     kann entfallen
	 *
	 * @return array<int,array<string,mixed>> Ein Eintrag je Artikel mit Titel,
	 *                                        Adresse, Teaser und CSS-Klasse
	 */
	protected function compileArticles(Collection $objArticles, PageModel $objPage, bool $blnPageProtected): array
	{
		$arrArticles = array();

		// Die Option "Seiten statt einzelnen Artikel verlinken" greift nur, wenn
		// die Seite genau einen Artikel enthält — sonst wäre nicht erkennbar,
		// welcher Artikel gemeint ist.
		$blnLinkPage = (bool) $this->article_list_page_link && 1 === \count($objArticles);

		foreach ($objArticles as $objArticle)
		{
			$intId = (int) $objArticle->id;
			$blnProtected = $blnPageProtected || $this->isProtected($objArticle->protected, $objArticle->groups);

			// $this->pid ist die ID des Artikels, in dem dieses Inhaltselement
			// liegt — damit lässt sich der eigene Artikel in der Liste markieren.
			$blnActive = (int) $this->pid === $intId;

			$arrClasses = array();

			if ($blnActive)
			{
				$arrClasses[] = 'active';
			}

			if ($blnProtected)
			{
				$arrClasses[] = 'protected';
			}

			$arrTeaserCssID = StringUtil::deserialize($objArticle->teaserCssID, true);

			$arrArticles[] = array
			(
				'id'           => $intId,
				'active'       => $blnActive,
				'class'        => implode(' ', $arrClasses),
				'title'        => StringUtil::specialchars($objArticle->title),
				// Der Teaser ist redaktionell gepflegtes HTML und wird deshalb
				// bewusst nicht maskiert.
				'teaser'       => $this->article_list_teaser ? ($objArticle->teaser ?? '') : '',
				'teaser_cssID' => StringUtil::specialchars((string) ($arrTeaserCssID[0] ?? '')),
				'teaser_class' => StringUtil::specialchars((string) ($arrTeaserCssID[1] ?? '')),
				'link'         => $blnProtected ? '' : $this->generateArticleUrl($objArticle, $objPage, $blnLinkPage),
				'protected'    => $blnProtected,
			);
		}

		return $arrArticles;
	}

	/**
	 * Baut die Adresse eines Artikels.
	 *
	 * Contao spricht Artikel über den Adresszusatz /articles/<alias> an. Der in
	 * Contao 3 übliche Spaltenpräfix (etwa /articles/left:mein-artikel) entfällt —
	 * der Contao-Kern erzeugt seit Version 5 ebenfalls nur noch den reinen Alias,
	 * und ohne Alias springt Contao auf die Artikel-ID zurück.
	 *
	 * @param ArticleModel $objArticle  Der zu verlinkende Artikel
	 * @param PageModel    $objPage     Die Seite, auf der der Artikel liegt
	 * @param bool         $blnLinkPage true = statt des Artikels die Seite selbst
	 *                                  verlinken
	 *
	 * @return string Die Adresse, oder eine leere Zeichenkette wenn sich für die
	 *                Seite keine Route erzeugen lässt
	 */
	protected function generateArticleUrl(ArticleModel $objArticle, PageModel $objPage, bool $blnLinkPage): string
	{
		if ($blnLinkPage)
		{
			return $this->generatePageUrl($objPage);
		}

		return $this->generatePageUrl($objPage, '/articles/' . ($objArticle->alias ?: $objArticle->id));
	}

	/**
	 * Ermittelt die HTML-Auszeichnung für die Seitenüberschriften.
	 *
	 * Die Überschrift einer Seite steht in der Gliederung eine Stufe unter der
	 * Überschrift des Inhaltselements — steht das Element auf h2, bekommen die
	 * Seiten h3. Unterhalb von h6 gibt es keine Überschriftenebene mehr, dort
	 * wird auf einen Absatz ausgewichen.
	 *
	 * @return string|false Der Tagname, oder false wenn im Backend keine
	 *                      Seitenüberschriften gewünscht sind
	 */
	protected function getPageHeadlineTag()
	{
		if (!$this->article_list_page_headline)
		{
			return false;
		}

		$arrNext = array('h1' => 'h2', 'h2' => 'h3', 'h3' => 'h4', 'h4' => 'h5', 'h5' => 'h6', 'h6' => 'p');

		return $arrNext[$this->hl] ?? 'p';
	}
}
