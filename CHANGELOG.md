# Seiten- und Artikellisten Changelog

## Version 1.1.0 (2026-08-14)

* Fix: Bei gleichzeitig aktiver manueller Seitenauswahl und "Unterseiten
  automatisch verlinken" erschien die manuell ausgewählte Wurzelseite hinter
  statt vor den Unterseiten der aktuellen Seite — `collectPageIds()` hat die
  Unterseiten der aktuellen Seite versehentlich vorangestellt statt angehängt
* Add: Neues Häkchen "Manuelle Seitenauswahl nicht anzeigen" — die ausgewählten
  Seiten dienen dann nur noch als Ausgangspunkt für ihre Unterseiten, ohne
  selbst als Listeneintrag zu erscheinen
* Add: Neues Häkchen "Aktive Seite nicht verlinken" — die Seite, auf der das
  Element steht (bei der Artikelliste zusätzlich der Artikel, in dem es selbst
  liegt), erscheint dann als reiner Text statt als Verweis auf sich selbst
* Change: Die Seitenliste gibt einen echten, verschachtelten Seitenbaum aus
  (`<ul>` in `<ul>`, mit der Klasse `level_N` je Ebene) statt einer flachen
  Liste mit der CSS-Klasse `levelN` zur optischen Einrückung — CSS, das gezielt
  `levelN` angesprochen hat, muss auf die neue Struktur umgestellt werden

## Version 1.0.0 (2026-08-02)

Portierung auf PHP 8.3 und Contao 4.13/5. Die Erweiterung enthielt noch
Contao-3-Code, der unter Contao 5 zu Fatal Errors geführt hätte.

* Change: Lauffähig unter Contao 5. Ersetzt wurden die dort entfernten Bausteine
  `deserialize()`, `TL_MODE`, `BE_USER_LOGGED_IN`, `FE_USER_LOGGED_IN`,
  `TL_ROOT`, `VERSION`, `Controller::addImageToTemplate()` sowie die
  Klassenaliase im globalen Namensraum
* Change: `services.yml` gibt keinen `_instanceof`-Block mehr für
  `ContainerAwareInterface` vor — die Schnittstelle gibt es seit Symfony 7 nicht
  mehr, der Block hat den Containerbau unter Contao 5 verhindert
* Change: Die Seitenermittlung, die Rechteprüfung und der Aufbau der Adressen
  liegen jetzt gemeinsam in `AbstractListElement` statt doppelt in beiden
  Inhaltselementen
* Change: Adressen entstehen unter Contao 5 über
  `contao.routing.content_url_generator` statt über das dort überholte
  `PageModel::getFrontendUrl()`; unter Contao 4.13 bleibt es beim bisherigen Weg
* Change: Die Rechteprüfung nutzt den Gruppen-Voter des Contao-Kerns
  (`ContaoCorePermissions::MEMBER_IN_GROUPS`) statt eines eigenen
  Gruppenvergleichs
* Change: Der Vorschaumodus wird über den TokenChecker erkannt statt über das
  Cookie `FE_PREVIEW`, das seit Contao 4 nichts mehr aussagt
* Change: Die Bildausgabe der Artikelliste ist entfallen. Sie las die Spalten
  `addImage` und `singleSRC` aus `tl_article`, die es weder in Contao 4.13 noch
  in Contao 5 gibt, und stützte sich auf das Template `article_list_image`, das
  die Erweiterung gar nicht mitbringt
* Change: Beim Verlinken von Artikeln entfällt der Spaltenpräfix
  (`/articles/left:alias`); der Contao-Kern erzeugt seit Version 5 ebenfalls nur
  noch `/articles/alias`
* Change: Leere Listen schreiben keinen Fehler mehr ins Systemprotokoll — der
  Eintrag entstand bei jedem Seitenaufruf, obwohl ein nicht konfiguriertes
  Element kein Fehler ist
* Change: Templates geben Verweise jetzt als `<a>` und nicht verlinkte Titel als
  `<span>` aus, der Artikelteaser steht in einem `<div class="teaser">` und
  übernimmt eine hinterlegte Teaser-CSS-ID
* Change: Dokumentation nach `docs/` ausgelagert (Backend, Frontend, Technik);
  die Datei `TODO.md` ist darin aufgegangen
* Change: `composer.json` verlangt PHP ^8.1 und Contao ^4.13 || ^5.0, die nicht
  mehr benötigten Entwicklungsabhängigkeiten sind entfallen
* Fix: Unter PHP 8 brach die Seitenliste mit einem TypeError ab, sobald keine
  Seite von Hand ausgewählt war — `in_array()` bekam dann statt eines Arrays
  einen anderen Wert übergeben
* Fix: Die Seitenliste rief `$this->Input->cookie()` auf; `Input` ist kein
  Objektfeld, der Aufruf wäre unter Contao 4.13 ein Fatal Error gewesen
* Fix: Die Seiten-IDs aus dem Blob-Feld gingen ungeprüft in die SQL-Bedingung
  ein; sie werden jetzt ausnahmslos nach `int` gewandelt
* Fix: Die Artikelliste hat die Eigenschaft `idLevels` nie deklariert und damit
  ab PHP 8.2 eine Deprecation-Meldung erzeugt; die Einrückungsebenen kamen in
  der Artikelliste zudem nie beim Template an
* Fix: Zeitgesteuert abgeschaltete Seiten und Artikel (`start`/`stop`)
  erschienen weiter in der Liste — geprüft wurde nur das Häkchen `published`
* Fix: Die Bildausgabe hat `$this->singleSRC` statt `$objArticles->singleSRC`
  ausgewertet und damit das falsche Bild geladen (Code ist entfallen, siehe oben)
* Fix: Das Feld für die manuelle Seitenauswahl hatte den Vorgabewert `'1'`,
  obwohl es ein serialisiertes Array erwartet
* Fix: Titel von Seiten und Artikeln werden für die Ausgabe maskiert
* Fix: Die Seitenliste hat `id` und `class` des Templates überschrieben, obwohl
  Contao beide danach ohnehin wieder mit den eigenen Werten belegt

## Version 0.1.2 (2026-07-30)

* Change: Beschreibung, Keywords und Homepage in der composer.json ergänzt, damit Packagist das Paket verständlich darstellt und über die Suche auffindbar macht

## Version 0.1.1 (2024-12-02)

* Fix: Überschrift im Template wird doppelt ausgegeben -> Ausgabe headline im Template entfernt

## Version 0.1.0 (2024-05-02)

Erste Version, die unter PHP 8 mit Contao 4.13 lauffähig ist. Weitere Tests und Code-Anpassungen nötig.

## Version 0.0.1 (2024-04-30)

Initiale Version ohne Funktionen. Setzt auf https://gitlab.com/srhinow/ce_article_list auf.
