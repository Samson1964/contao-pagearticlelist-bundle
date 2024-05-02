<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (C) 2005-2013 Leo Feyer
 *
 * @package   chesstable
 * Version    1.0.0
 * @author    Frank Hoppe
 * @license   GNU/LGPL
 * @copyright Frank Hoppe 2013
 */

namespace Schachbulle\ContaoPagearticlelistBundle\ContentElements;

class Pagelist extends \ContentElement
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'ce_page_list';
	protected $idLevels = array();

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		global $objPage;
		$query = '';
		$pages = array();

		$selectedPages = deserialize($this->article_list_pages);

		// Array mit aufzulistenden Seiten erstellen
		if(is_array($selectedPages))
		{
			$articleListPages = $selectedPages;
		}
		elseif (!empty($selectedPages))
		{
			$articleListPages = array($selectedPages);
		}
		else
		{
			$articleListPages = array();
		}

		if(TL_MODE == 'FE')
		{
			// Frontend-Aufruf: ID der aktuellen Seite speichern
			$pageId = $objPage->id;
		}
		else
		{
			// Backend-Aufruf: Artikel-Objekt des Inhaltselements laden, um die Seiten-ID zu ermitteln
			$objArticle = \ArticleModel::findByIdOrAlias($this->pid);
			if($objArticle)
			{
				$pageId = $objArticle->pid;
			}
			else
			{
				$pageId = false;
			}
		}

		if($this->article_list_childrens)
		{
			// Unterseiten der aktuellen Seite sollen automatisch verlinkt werden
			array_splice($articleListPages, 0, 0, $this->getChildPages($pageId, false));
		}

		if($this->article_list_recursive)
		{
			// Seitenbäume sollen rekursiv einbezogen werden
			for($i = count($articleListPages)-1; $i >= 0; $i--)
			{
				array_splice($articleListPages, $i+1, 0, $this->getChildPages($articleListPages[$i], true, @$this->idLevels[$articleListPages[$i]]+1));
			}
		}

		if(count($articleListPages))
		{
			$objPages = \Database::getInstance()->prepare("SELECT * FROM tl_page WHERE ".(!$this->Input->cookie('FE_PREVIEW') ? "`published`='1' AND " : "") . "id IN (" . implode(',', $articleListPages) . ") ORDER BY sorting")
			                                    ->execute();

			if($objPages->numRows > 0)
			{
				$this->import('FrontendUser', 'User');

				while ($objPages->next())
				{
					if ($this->article_list_hidden || ($objPages->hide != '1') || in_array($objPages->id, $selectedPages))
					{
						$isProtected = false;

						// Protected element
						if (!BE_USER_LOGGED_IN && $objPages->protected)
						{
							if (!FE_USER_LOGGED_IN)
							{
								$isProtected = true;
							}
							else
							{
								$groups = deserialize($objPages->groups);

								if (!is_array($groups) || empty($groups) || !count(array_intersect($groups, $this->User->groups)))
								{
									$isProtected = true;
								}
							}
						}

						$level = (isset($this->idLevels[$objPages->id]) ? $this->idLevels[$objPages->id] : 0);

						$pages[] = array
						(
							'name'			=> $objPages->title,
							'title'			=> ($objPages->pageTitle != '' ? $objPages->pageTitle : $objPages->title),
							'link'			=> \PageModel::findByPk($objPages->id)->getFrontendUrl(),
							'protected'		=> $isProtected,
							'level'			=> $level,
							'active'		=> ($pageId == $objPages->id),
							'class'			=> 'level'.$level.' '.($pageId == $objPages->id ? ' active'.($isProtected ? ' protected' : '') : ($isProtected ? 'protected' : '')),
							'sort'			=> (array_search($objPages->id, $articleListPages) !== FALSE ? array_search($objPages->id, $articleListPages) + 9000000 : $objPages->sorting)
						);
					}
				}
			}
		}
		elseif (TL_MODE == 'FE')
		{
			$this->log(sprintf('No pages for ID %d (%s) found.', $objPage->id, $objPage->pageTitle), 'PageList', TL_ERROR);
		}

		if((count($pages) > 0) && (count($articleListPages) > 0))
		{
			usort($pages, array($this, 'pageSort'));
		}

		$this->Template->id = $this->id;
		$this->Template->class = 'ce_page_list';
		$this->Template->pages = $pages;

		return;

	}

	/**
	 * Helper function for usort
	 * @param $a
	 * @param $b
	 * @return int
	 */
	protected function pageSort($a, $b)
	{
		if ($a['sort'] == $b['sort']) {
		    return 0;
		}
		return ($a['sort'] < $b['sort']) ? -1 : 1;
	}

	/**
	 * Ruft alle untergeordneten Seiten ab, wenn article_list_recursive = true ist
	 */
	protected function getChildPages($pageId, $recursive = true, $level=0)
	{
		$pageArray = array();

		$objPages = \Database::getInstance()->prepare("SELECT id FROM tl_page WHERE pid=? AND type=?".(!$this->Input->cookie('FE_PREVIEW') ? " AND `published`='1' " : "")." ORDER BY sorting")
		                                    ->execute($pageId, 'regular');

		while($objPages->next())
		{
			$pageArray[] = $objPages->id;
			$this->idLevels[$objPages->id] = $level;
			if ($recursive)
			{
				$pageArray = array_merge($pageArray, $this->getChildPages($objPages->id, $recursive, $level+1));
			}
		}

		return $pageArray;
	}

}
