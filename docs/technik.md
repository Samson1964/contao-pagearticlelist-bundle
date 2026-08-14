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

Bis Version 1.0.0 wurden die Unterseiten der aktuellen Seite ("Unterseiten
automatisch verlinken") der manuellen Auswahl **vorangestellt**
(`array_splice($arrPageIds, 0, 0, …)`) statt angehängt. Dadurch landete die
eigentliche Wurzel der Liste — die manuell ausgewählte Seite, oft die aktuelle
Seite selbst — hinter ihren eigenen Geschwistern statt davor. `collectPageIds()`
hängt die Unterseiten der aktuellen Seite seither ans Ende an
(`array_merge()`), sodass Ebene 0 immer zuerst erscheint.

## Seitenbaum der Seitenliste

`Pagelist` gibt seit Version 1.1.0 keine flache Liste mehr aus, sondern einen
echten Seitenbaum aus verschachtelten `<ul>`-Elementen. Zwei Methoden bauen
das:

* `buildTree()` ordnet die flache, nach Ebene sortierte Liste zu Knotenobjekten
  mit einer `children`-Eigenschaft an. Ein Stapel offener Knoten hält fest, wo
  das nächste Element einsortiert wird — ein neues Element hängt sich unter den
  zuletzt verbliebenen Knoten mit einer niedrigeren Ebene, unabhängig davon,
  wie groß der Sprung in der Ebenennummer ist. Das macht den Aufbau robust
  gegenüber Lücken: Fehlt eine Zwischenseite (unveröffentlicht, im Menü
  versteckt oder über "Manuelle Seitenauswahl nicht anzeigen" ausgeblendet),
  rutschen ihre Kinder einfach eine Ebene höher, statt eine Kette leerer
  `<ul>`-Hüllen zu erzeugen.
* `renderTree()` baut daraus die HTML-Auszeichnung zusammen — rekursiv, mit
  der Klasse `level_N` an der `<ul>` jeder Ebene, analog zu den
  Navigationsmodulen des Contao-Kerns.

Die Methode erzeugt die Auszeichnung selbst, statt sie dem Template zu
überlassen: Eine wechselnde Verschachtelungstiefe lässt sich in einer
einzelnen, nicht rekursiven `.html5`-Datei nicht sauber abbilden, ohne
schließende Tags über mehrere Schleifendurchläufe hinweg offen zu halten. Der
Contao-Kern löst dasselbe Problem in `Module::renderNavigation()` auf demselben
Weg — dort entsteht ebenfalls vorgefertigtes HTML je Ebene, das das Template
(`nav_default.html5`) nur noch über `$item['subitems']` ausgibt. `ce_page_list.html5`
macht es mit `$this->pages` genauso.

Die Artikelliste bekommt keinen Baum: Sie gruppiert Artikel unter
Seitenüberschriften, keine verschachtelten Unterseiten, und bleibt deshalb bei
der bisherigen flachen Struktur.

## Manuelle Auswahl ausblenden und aktive Seite nicht verlinken

Zwei Häkchen wirken unabhängig von der Ebenen-Logik:

* `AbstractListElement::isSelectionVisible()` filtert Seiten aus der manuellen
  Auswahl heraus, wenn "Manuelle Seitenauswahl nicht anzeigen" aktiv ist. Die
  Seite bleibt trotzdem Teil von `$arrPageIds` und damit weiterhin Ausgangspunkt
  für ihren Unterbaum — nur der eigene Listeneintrag entfällt. Bei der
  Seitenliste übernimmt genau dafür die Lückentoleranz von `buildTree()`: Der
  ausgeblendete Wurzelknoten fehlt, seine Kinder rutschen eine Ebene höher.
* Beide `compile()`-Methoden berechnen `$blnLinkable = !$blnProtected &&
  !($blnActive && $this->article_list_no_active_link)`. Das ist dieselbe
  Fallunterscheidung wie bei geschützten Datensätzen — auch die aktive Seite
  bzw. der aktive Artikel wird dann als `<span>` statt als `<a>` ausgegeben,
  ohne dass das Template etwas Neues wissen muss.

## Datenbankfelder

Alle Felder liegen in `tl_content`:

| Feld | Typ | Bedeutung |
|---|---|---|
| `article_list_pages` | `blob` | Manuell ausgewählte Seiten-IDs, serialisiert |
| `article_list_selection_hidden` | `char(1)` | Manuelle Auswahl nicht als Listeneintrag anzeigen |
| `article_list_childrens` | `char(1)` | Unterseiten der aktuellen Seite einbeziehen |
| `article_list_recursive` | `char(1)` | Unterbäume rekursiv einbeziehen |
| `article_list_hidden` | `char(1)` | Im Menü versteckte Seiten einbeziehen |
| `article_list_no_active_link` | `char(1)` | Aktive Seite/Artikel ohne Verweis ausgeben |
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

Seit Version 1.1.0 zusätzlich geprüft: die Reihenfolge bei gleichzeitig aktiver
manueller Auswahl und "Unterseiten automatisch verlinken" (Wurzel muss zuerst
erscheinen), die Verschachtelung über mehrere Ebenen, "Manuelle Seitenauswahl
nicht anzeigen" (Kinder rutschen bei ausgeblendeter Wurzel eine Ebene höher)
sowie "Aktive Seite nicht verlinken" auf Seiten- und auf Artikelebene.
