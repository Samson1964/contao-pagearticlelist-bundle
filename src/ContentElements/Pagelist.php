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

	/**
	 * Generate the module
	 */
	protected function compile()
	{

		$this->Template->id = $this->id;
		$this->Template->class = 'ce_page_list';

		return;

	}

}
