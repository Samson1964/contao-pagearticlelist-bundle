<?php

declare(strict_types=1);

/**
 * Seiten- und Artikellisten für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoPagearticlelistBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Lädt die Dienstkonfiguration der Erweiterung in den Symfony-Container.
 */
class ContaoPagearticlelistExtension extends Extension
{
	/**
	 * Liest src/Resources/config/services.yml ein.
	 *
	 * Symfony ruft die Methode einmal beim Bau des Containers auf. Die Datei
	 * enthält derzeit nur die _defaults-Vorgaben; sie wird trotzdem geladen,
	 * damit später ergänzte Dienste ohne weitere Änderung an dieser Klasse
	 * wirksam werden.
	 *
	 * @param array<int,array<string,mixed>> $mergedConfig Die zusammengeführte
	 *                                                     Konfiguration aller
	 *                                                     Konfigurationsdateien;
	 *                                                     die Erweiterung wertet
	 *                                                     sie nicht aus
	 * @param ContainerBuilder               $container    Der im Bau befindliche
	 *                                                     Dienstcontainer
	 *
	 * @return void
	 */
	public function load(array $mergedConfig, ContainerBuilder $container): void
	{
		$loader = new YamlFileLoader(
			$container,
			new FileLocator(__DIR__ . '/../Resources/config')
		);

		$loader->load('services.yml');
	}
}
