# Ausgabe im Frontend

Beide Elemente bringen ein eigenes Template mit. Wie bei allen Contao-Elementen
lässt sich das Template im Backend unter **Template-Einstellungen** durch eine
eigene Fassung ersetzen; dazu genügt eine Kopie im Ordner `templates/` der
Installation.

## Seitenliste — `ce_page_list`

```html
<nav class="ce_page_list block">
<ul>
	<li class="level0"><a href="/kapitel-1" title="Kapitel 1">Kapitel 1</a></li>
	<li class="level1"><a href="/kapitel-1-1" title="Kapitel 1.1">Kapitel 1.1</a></li>
	<li class="level0 protected"><span>Interner Bereich</span></li>
</ul>
</nav>
```

Die CSS-Klassen am `<li>`:

| Klasse | Bedeutung |
|---|---|
| `levelN` | Verschachtelungstiefe, beginnend bei `level0` |
| `active` | Die Seite, auf der das Element steht |
| `protected` | Geschützte Seite, ohne Verweis ausgegeben |

Verlinkte Seiten stehen in einem `<a>`, nicht verlinkte in einem `<span>` — so
lassen sich beide Fälle getrennt gestalten.

Der Verweistext ist der Seitentitel (`pageTitle`), ersatzweise der Seitenname.
Der Seitenname steht zusätzlich im `title`-Attribut.

## Artikelliste — `ce_article_list`

```html
<nav class="ce_article_list block">
<h3 class="page_headline">Kapitel 1</h3>
<ul>
	<li><a href="/kapitel-1/articles/erster-artikel">Erster Artikel</a>
		<div class="teaser"><p>Teasertext des Artikels.</p></div>
	</li>
	<li><a href="/kapitel-1/articles/zweiter-artikel">Zweiter Artikel</a></li>
</ul>
</nav>
```

Seiten ohne sichtbare Artikel erscheinen nicht — es bleiben also keine leeren
Überschriften stehen.

Die CSS-Klassen am `<li>`:

| Klasse | Bedeutung |
|---|---|
| `active` | Der Artikel, in dem das Element selbst liegt |
| `protected` | Geschützter Artikel, ohne Verweis ausgegeben |

Der Teaser bekommt die Klasse `teaser`. Sind am Artikel unter **Teaser-CSS-ID**
eine ID oder eigene Klassen hinterlegt, werden sie übernommen.

## Durchsuchbarkeit

Beide Templates bauen auf `block_searchable` auf. Die Listen landen damit im
Suchindex von Contao. Wer das nicht möchte, kann in einer eigenen
Template-Fassung stattdessen auf `block_unsearchable` aufbauen.
