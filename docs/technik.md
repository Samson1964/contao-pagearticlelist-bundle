# Technischer Aufbau

## Dateien

```text
src/
├── ContaoManager/Plugin.php                    Anmeldung beim Contao Manager
├── ContentElements/
│   ├── AbstractListElement.php                 Gemeinsamer Unterbau beider Elemente
│   ├── Articlelist.php                         Inhaltselement "Artikelliste"
│   └── Pagelist.php                            Inhaltselement "Seitenliste"
├── DependencyInjection/…Extension.php          Lädt services.yml
├── Resources/
│   ├── config/services.yml                     Dienstkonfiguration (derzeit leer)
│   └── contao/
│       ├── config/config.php                   Anmeldung der Inhaltselemente
│       ├── dca/tl_content.php                  Paletten und Felder
│       ├── languages/de/                       Beschriftungen
│       └── templates/                          Frontend-Templates
└── ContaoPagearticlelistBundle.php             Symfony-Bundle
```

Die beiden Elemente unterscheiden sich nur in dem, was sie ausgeben. Die Frage
"Welche Seiten sind gemeint?" beantworten beide gleich, deshalb liegen die
Seitenermittlung, die Rechteprüfung und der Aufbau der Adressen in
`AbstractListElement`.

## Anmeldung der Inhaltselemente

Die Elemente werden über `$GLOBALS['TL_CTE']` angemeldet und nicht über das
PHP-Attribut `#[AsContentElement]`. Das Attribut gibt es erst ab Contao 5, die
Erweiterung soll aber auch unter Contao 4.13 laufen. Contao 5 unterstützt die
Anmeldung über `TL_CTE` weiterhin.

## Was für Contao 4.13 und Contao 5 gleichzeitig zu beachten war

Die Ursprungserweiterung stammt aus der Contao-3-Zeit. Folgende Stellen mussten
so gelöst werden, dass dieselbe Datei unter beiden Contao-Generationen läuft:

| Alt (Contao 3) | Neu | Grund |
|---|---|---|
| `deserialize()` | `StringUtil::deserialize()` | Die globale Funktion gibt es in Contao 5 nicht mehr |
| `TL_MODE` | Seite aus dem Request | Konstante in Contao 5 entfernt |
| `BE_USER_LOGGED_IN`, `FE_USER_LOGGED_IN` | `contao.security.token_checker` | Konstanten in Contao 5 entfernt |
| `Input::cookie('FE_PREVIEW')` | `TokenChecker::isPreviewMode()` | Der Vorschaumodus hängt seit Contao 4 am Sicherheitstoken, nicht mehr am Cookie |
| `Controller::addImageToTemplate()` | entfallen | In Contao 5 entfernt; der Aufruf galt Spalten, die es in `tl_article` gar nicht gibt |
| `TL_ROOT`, `VERSION` | entfallen | Konstanten in Contao 5 entfernt |
| `\ContentElement`, `\Database` … | `Contao\ContentElement`, `Contao\Database` … | Die Klassenaliase im globalen Namensraum fallen weg |
| `$this->import('FrontendUser', 'User')` und Gruppenvergleich von Hand | `ContaoCorePermissions::MEMBER_IN_GROUPS` | Nutzt denselben Voter wie die Navigationsmodule des Kerns |

## Adressen

`PageModel::getFrontendUrl()` ist ab Contao 5.3 als überholt gekennzeichnet und
schreibt bei jedem Aufruf eine Deprecation-Meldung ins Protokoll. Zuständig ist
dort der Dienst `contao.routing.content_url_generator` — den es unter Contao
4.13 aber noch nicht gibt.

`AbstractListElement::generatePageUrl()` fragt deshalb den Container nach dem
Dienst und greift nur dann auf `getFrontendUrl()` zurück, wenn es ihn nicht
gibt. Eine Versionsabfrage wäre der schlechtere Weg, weil sie an eine
Versionsnummer bindet statt an das, was tatsächlich vorhanden ist.

Lässt sich für eine Seite keine Route erzeugen — etwa weil ihre Seitenwurzel
keine Domain hat —, liefert die Methode eine leere Zeichenkette. Die Templates
geben den Titel dann ohne Verweis aus, statt mit einer Ausnahme abzubrechen.

## Sortierung

Die Reihenfolge der Ausgabe steckt im Array der Seiten-IDs, nicht in der
Datenbanksortierung: Bei einer rekursiven Auflistung muss jede Unterseite direkt
hinter ihrer Elternseite stehen, und das ergibt sich erst aus der Reihenfolge,
in der die IDs eingesammelt wurden. `sortByPageOrder()` bringt die geladenen
Datensätze deshalb zum Schluss wieder in genau diese Reihenfolge.

## Datenbankfelder

Alle Felder liegen in `tl_content`:

| Feld | Typ | Bedeutung |
|---|---|---|
| `article_list_pages` | `blob` | Manuell ausgewählte Seiten-IDs, serialisiert |
| `article_list_childrens` | `char(1)` | Unterseiten der aktuellen Seite einbeziehen |
| `article_list_recursive` | `char(1)` | Unterbäume rekursiv einbeziehen |
| `article_list_hidden` | `char(1)` | Im Menü versteckte Seiten einbeziehen |
| `article_list_page_link` | `char(1)` | Bei einem einzelnen Artikel die Seite verlinken |
| `article_list_page_headline` | `char(1)` | Seitenüberschriften ausgeben |
| `article_list_teaser` | `char(1)` | Artikelteaser ausgeben |

Die IDs aus `article_list_pages` gehen in eine `IN(…)`-Bedingung ein und werden
vorher ausnahmslos nach `int` gewandelt.

## Prüfen der Erweiterung

Es gibt keine PHPUnit-Suite: Beide Klassen erben von `Contao\ContentElement` und
lassen sich ohne laufenden Contao-Kernel nicht sinnvoll instanziieren; ein Test
gegen Attrappen würde die Contao-Anbindung prüfen, die gerade das Fehleranfällige
an dieser Erweiterung war.

Geprüft wird stattdessen in einer echten Installation je Contao-Generation
(4.13 und 5.x): eine Seitenstruktur mit Unterseiten, einer im Menü versteckten
Seite, einer unveröffentlichten Seite, einer Seite ohne Artikel und einer
geschützten Seite, dazu Seiten mit einem und mit mehreren Artikeln. Damit sind
alle Verzweigungen der beiden `compile()`-Methoden abgedeckt.
