# Bedienung im Backend

Die Erweiterung stellt zwei Inhaltselemente bereit, die im Auswahlfeld unter
**Einbindungen** stehen:

* **Seitenliste** (`page_list`) — listet Seiten auf.
* **Artikelliste** (`article_list`) — listet zu jeder Seite deren Artikel auf.

Beide Elemente teilen sich die Seitenauswahl, die Artikelliste hat zusätzliche
Optionen für die Ausgabe der Artikel.

## Seitenauswahl

Diese vier Einstellungen entscheiden gemeinsam, welche Seiten in der Liste
landen. Sie greifen ineinander, deshalb hier zuerst die Einzelbedeutung und
darunter die Kombinationen.

### Manuelle Seitenauswahl

Die hier ausgewählten Seiten werden immer ausgegeben — auch dann, wenn sie in
der Navigation ausgeblendet sind. Eine bewusste Auswahl im Backend überstimmt
die Ausblendung.

Ohne weitere Häkchen ist das die vollständige Liste: genau diese Seiten,
in der Reihenfolge der Auswahl.

### Unterseiten automatisch verlinken

Bezieht die direkten Unterseiten **der Seite mit ein, auf der das Element
steht**. Die Seite selbst erscheint nicht in der Liste. Wird eine Ausgabe für
die aktuelle Seite nicht gewünscht, bleibt die Einstellung besser aus.

### Seiten rekursiv verlinken

Ergänzt zu jeder bereits eingesammelten Seite deren kompletten Unterbaum. Die
Unterseiten stehen dabei direkt hinter ihrer Elternseite und bekommen über die
CSS-Klasse `levelN` ihre Tiefe mit — `level0` für die oberste Ebene, `level1`
für deren Kinder und so fort.

Die Einstellung wirkt nur auf Seiten, die schon in der Liste sind. Allein
gesetzt bewirkt sie deshalb nichts.

### Im Menü versteckte Seiten einbeziehen

Nimmt auch Seiten auf, die in der Navigation ausgeblendet sind. Ohne dieses
Häkchen erscheinen ausgeblendete Seiten nur dann, wenn sie in der manuellen
Seitenauswahl stehen.

### Zusammenspiel

| Manuelle Auswahl | Unterseiten | Rekursiv | Ergebnis |
|---|---|---|---|
| – | – | – | Keine Ausgabe |
| – | ✓ | – | Die direkten Unterseiten der aktuellen Seite |
| – | – | ✓ | Keine Ausgabe |
| – | ✓ | ✓ | Alle Unterseiten der aktuellen Seite, über alle Ebenen |
| ✓ | – | – | Genau die ausgewählten Seiten |
| ✓ | ✓ | – | Die ausgewählten Seiten und die direkten Unterseiten der aktuellen Seite |
| ✓ | – | ✓ | Die ausgewählten Seiten samt ihrer kompletten Unterbäume |

In allen Fällen gilt: Unveröffentlichte und zeitgesteuert abgeschaltete Seiten
erscheinen nicht, und es werden ausschließlich reguläre Seiten aufgelistet —
Weiterleitungen und Fehlerseiten haben keine eigene Adresse.

## Artikeloptionen

Diese drei Einstellungen gibt es nur bei der Artikelliste.

### Seiten statt einzelner Artikel verlinken

Enthält eine Seite genau einen Artikel, verweist die Liste direkt auf die Seite
statt auf `/articles/<alias>`. Bei mehreren Artikeln bleibt es beim Verweis auf
den einzelnen Artikel — sonst wäre nicht erkennbar, welcher gemeint ist.

### Seitenüberschriften

Setzt über die Artikel jeder Seite deren Titel als Überschrift. Die Ebene liegt
eine Stufe unter der Überschrift des Inhaltselements: Steht das Element auf
`h2`, bekommen die Seiten `h3`. Unterhalb von `h6` wird auf `<p>` ausgewichen.

### Artikelteaser

Gibt den Teasertext der Artikel mit aus. Der Teaser ist redaktionelles HTML und
wird unverändert übernommen.

## Geschützte Seiten und Artikel

Geschützte Seiten und Artikel erscheinen in der Liste, aber ohne Verweis — die
Struktur bleibt sichtbar, der Inhalt ist nicht erreichbar. Sie tragen die
CSS-Klasse `protected`.

Ob jemand Zugriff hat, entscheidet die Mitgliedergruppe: Angemeldete Mitglieder
in einer der freigegebenen Gruppen sehen den Verweis, alle anderen nicht.
Angemeldete Backend-Benutzer sehen beim Prüfen der Seite alles.

Ist bereits die Seite geschützt, gelten auch alle Artikel darin als geschützt.
