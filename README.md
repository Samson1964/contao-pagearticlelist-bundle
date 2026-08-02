# Seiten- und Artikellisten

Zwei Inhaltselemente für Contao, die Seiten beziehungsweise die Artikel
ausgewählter Seiten auflisten — von Hand ausgewählt, als Unterseiten der
aktuellen Seite oder als kompletter Seitenbaum.

Die Erweiterung ist ein Fork von <https://gitlab.com/srhinow/ce_article_list>,
das ab Contao 4.9 nicht mehr lief.

## Voraussetzungen

* PHP 8.1 oder neuer
* Contao 4.13 oder Contao 5

## Installation

```bash
composer require schachbulle/contao-pagearticlelist-bundle
```

Anschließend die Datenbank aktualisieren, damit die Felder in `tl_content`
angelegt werden.

## Verwendung

Im Backend stehen unter **Einbindungen** zwei neue Inhaltselemente bereit:

* **Seitenliste** — listet Seiten auf.
* **Artikelliste** — listet zu jeder Seite deren Artikel auf, wahlweise mit
  Seitenüberschrift und Teaser.

## Dokumentation

* [Bedienung im Backend](docs/backend.md) — was die einzelnen Einstellungen tun
  und wie sie zusammenwirken
* [Ausgabe im Frontend](docs/frontend.md) — Templates und CSS-Klassen
* [Technischer Aufbau](docs/technik.md) — Aufbau der Erweiterung und die
  Besonderheiten der Unterstützung beider Contao-Generationen

## Lizenz

LGPL-3.0-or-later

**Frank Hoppe**
