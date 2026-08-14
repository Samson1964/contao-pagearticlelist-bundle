<?php

declare(strict_types=1);

/**
 * Seiten- und Artikellisten für Contao Open Source CMS
 *
 * Ergänzt tl_content um die beiden Inhaltselemente "Seitenliste" und
 * "Artikelliste" samt der zugehörigen Felder.
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

/**
 * Paletten
 */
$GLOBALS['TL_DCA']['tl_content']['palettes']['page_list'] = '{type_legend},type,headline;{article_list_legend},article_list_pages,article_list_selection_hidden,article_list_childrens,article_list_recursive,article_list_hidden,article_list_no_active_link;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID,space';

$GLOBALS['TL_DCA']['tl_content']['palettes']['article_list'] = '{type_legend},type,headline;{article_list_legend},article_list_pages,article_list_selection_hidden,article_list_childrens,article_list_recursive,article_list_hidden,article_list_no_active_link;{article_list_options_legend},article_list_page_link,article_list_page_headline,article_list_teaser;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID,space';

/**
 * Felder
 */
$GLOBALS['TL_DCA']['tl_content']['fields']['article_list_pages'] = array
(
	'label'     => &$GLOBALS['TL_LANG']['tl_content']['article_list_pages'],
	'exclude'   => true,
	'inputType' => 'pageTree',
	'eval'      => array
	(
		'mandatory' => false,
		'multiple'  => true,
		'fieldType' => 'checkbox',
		'tl_class'  => 'clr'
	),
	// Bis Version 0.1.2 stand hier ein 'default' => '1'. Das Feld ist ein Blob und
	// erwartet ein serialisiertes Array — der Vorgabewert hat neu angelegten
	// Elementen also die Zeichenkette '1' untergeschoben.
	'sql'       => "blob NULL"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['article_list_selection_hidden'] = array
(
	'label'     => &$GLOBALS['TL_LANG']['tl_content']['article_list_selection_hidden'],
	'exclude'   => true,
	'inputType' => 'checkbox',
	'eval'      => array('tl_class' => 'w50'),
	'sql'       => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['article_list_childrens'] = array
(
	'label'     => &$GLOBALS['TL_LANG']['tl_content']['article_list_childrens'],
	'default'   => '1',
	'exclude'   => true,
	'inputType' => 'checkbox',
	'eval'      => array('tl_class' => 'w50'),
	'sql'       => "char(1) NOT NULL default '1'"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['article_list_recursive'] = array
(
	'label'     => &$GLOBALS['TL_LANG']['tl_content']['article_list_recursive'],
	'exclude'   => true,
	'inputType' => 'checkbox',
	'eval'      => array('tl_class' => 'w50'),
	'sql'       => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['article_list_hidden'] = array
(
	'label'     => &$GLOBALS['TL_LANG']['tl_content']['article_list_hidden'],
	'default'   => '1',
	'exclude'   => true,
	'inputType' => 'checkbox',
	'eval'      => array('tl_class' => 'w50'),
	'sql'       => "char(1) NOT NULL default '1'"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['article_list_no_active_link'] = array
(
	'label'     => &$GLOBALS['TL_LANG']['tl_content']['article_list_no_active_link'],
	'exclude'   => true,
	'inputType' => 'checkbox',
	'eval'      => array('tl_class' => 'w50'),
	'sql'       => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['article_list_page_link'] = array
(
	'label'     => &$GLOBALS['TL_LANG']['tl_content']['article_list_page_link'],
	'default'   => '1',
	'exclude'   => true,
	'inputType' => 'checkbox',
	'eval'      => array('tl_class' => 'w50'),
	'sql'       => "char(1) NOT NULL default '1'"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['article_list_page_headline'] = array
(
	'label'     => &$GLOBALS['TL_LANG']['tl_content']['article_list_page_headline'],
	'default'   => '1',
	'exclude'   => true,
	'inputType' => 'checkbox',
	'eval'      => array('tl_class' => 'w50'),
	'sql'       => "char(1) NOT NULL default '1'"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['article_list_teaser'] = array
(
	'label'     => &$GLOBALS['TL_LANG']['tl_content']['article_list_teaser'],
	'default'   => '1',
	'exclude'   => true,
	'inputType' => 'checkbox',
	'eval'      => array('tl_class' => 'w50'),
	'sql'       => "char(1) NOT NULL default '1'"
);
