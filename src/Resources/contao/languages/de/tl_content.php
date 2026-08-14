<?php

declare(strict_types=1);

/**
 * Seiten- und Artikellisten für Contao Open Source CMS
 *
 * Beschriftungen der Felder in tl_content.
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

/**
 * Felder
 */
$GLOBALS['TL_LANG']['tl_content']['article_list_pages'] = array('Manuelle Seitenauswahl', 'Seiten, die immer aufgelistet werden. Von Hand ausgewählte Seiten erscheinen auch dann, wenn sie im Menü versteckt sind.');
$GLOBALS['TL_LANG']['tl_content']['article_list_selection_hidden'] = array('Manuelle Seitenauswahl nicht anzeigen', 'Die oben ausgewählten Seiten selbst nicht auflisten, sondern nur als Ausgangspunkt für Unterseiten und Rekursion verwenden.');
$GLOBALS['TL_LANG']['tl_content']['article_list_childrens'] = array('Unterseiten automatisch verlinken', 'Die direkten Unterseiten der Seite einbeziehen, auf der dieses Element steht.');
$GLOBALS['TL_LANG']['tl_content']['article_list_recursive'] = array('Seiten rekursiv verlinken', 'Von jeder einbezogenen Seite auch alle tieferen Ebenen auflisten.');
$GLOBALS['TL_LANG']['tl_content']['article_list_hidden'] = array('Im Menü versteckte Seiten einbeziehen', 'Auch Seiten auflisten, die in der Navigation ausgeblendet sind.');
$GLOBALS['TL_LANG']['tl_content']['article_list_no_active_link'] = array('Aktive Seite nicht verlinken', 'Die Seite bzw. den Artikel, in dem dieses Element steht, ohne Verweis ausgeben.');

$GLOBALS['TL_LANG']['tl_content']['article_list_page_link'] = array('Seiten statt einzelner Artikel verlinken', 'Enthält eine Seite nur einen einzigen Artikel, direkt auf die Seite verweisen.');
$GLOBALS['TL_LANG']['tl_content']['article_list_page_headline'] = array('Seitenüberschriften', 'Über den Artikeln jeder Seite eine Überschrift ausgeben.');
$GLOBALS['TL_LANG']['tl_content']['article_list_teaser'] = array('Artikelteaser', 'Den Teasertext der Artikel mit ausgeben.');

/**
 * Legenden
 */
$GLOBALS['TL_LANG']['tl_content']['article_list_legend'] = 'Seitenauswahl';
$GLOBALS['TL_LANG']['tl_content']['article_list_options_legend'] = 'Artikeloptionen';
