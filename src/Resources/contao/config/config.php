<?php

declare(strict_types=1);

/**
 * Seiten- und Artikellisten für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

use Schachbulle\ContaoPagearticlelistBundle\ContentElements\Articlelist;
use Schachbulle\ContaoPagearticlelistBundle\ContentElements\Pagelist;

/**
 * Inhaltselemente
 *
 * Die Anmeldung läuft bewusst über $GLOBALS['TL_CTE'] und nicht über das
 * PHP-Attribut #[AsContentElement]: Das Attribut gibt es erst ab Contao 5, die
 * Erweiterung soll aber auch unter Contao 4.13 laufen. Contao 5 unterstützt die
 * Anmeldung über TL_CTE weiterhin.
 */
$GLOBALS['TL_CTE']['includes']['page_list'] = Pagelist::class;
$GLOBALS['TL_CTE']['includes']['article_list'] = Articlelist::class;
