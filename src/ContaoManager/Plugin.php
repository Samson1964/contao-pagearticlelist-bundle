<?php

declare(strict_types=1);

/**
 * Seiten- und Artikellisten für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoPagearticlelistBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Schachbulle\ContaoPagearticlelistBundle\ContaoPagearticlelistBundle;

/**
 * Meldet die Erweiterung beim Contao Manager an.
 */
class Plugin implements BundlePluginInterface
{
	/**
	 * Nennt das Bundle und seine Ladereihenfolge.
	 *
	 * Die Erweiterung wird nach dem Contao-Kern geladen, weil sie dessen DCA
	 * tl_content um zwei Inhaltselemente ergänzt — das funktioniert erst, wenn
	 * die Grundfassung der Tabelle bereits steht.
	 *
	 * @param ParserInterface $parser Vom Manager gestellter Parser; wird hier
	 *                                nicht gebraucht, gehört aber zur Schnittstelle
	 *
	 * @return array<BundleConfig> Liste mit der Bundle-Beschreibung
	 */
	public function getBundles(ParserInterface $parser): array
	{
		return [
			BundleConfig::create(ContaoPagearticlelistBundle::class)
				->setLoadAfter([ContaoCoreBundle::class]),
		];
	}
}
