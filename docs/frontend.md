# Ausgabe im Frontend

Beide Elemente bringen ein eigenes Template mit. Wie bei allen Contao-Elementen
lässt sich das Template im Backend unter **Template-Einstellungen** durch eine
eigene Fassung ersetzen; dazu genügt eine Kopie im Ordner `templates/` der
Installation.

## Seitenliste — `ce_page_list`

Die Liste ist ein echter, verschachtelter Seitenbaum — jede Unterseite steht
innerhalb des `<li>` ihrer Elternseite. Die Auszeichnung entspricht der
Navigation und der Sitemap des Contao-Kerns (`nav_default`), sodass vorhandenes
Navigations-CSS eines Themes ohne Anpassung greift:

```html
<nav class="ce_page_list block">
<ul class="level_1">
	<li class="submenu"><a href="/kapitel-1" title="Kapitel 1" class="submenu" aria-haspopup="true">Kapitel 1</a>
		<ul class="level_2">
			<li><a href="/kapitel-1-1" title="Kapitel 1.1">Kapitel 1.1</a></li>
			<li><a href="/kapitel-1-2" title="Kapitel 1.2">Kapitel 1.2</a></li>
		</ul>
	</li>
	<li class="protected"><span class="protected">Interner Bereich</span></li>
</ul>
</nav>
```

Bis Version 1.0.0 war das eine flache Liste, deren Tiefe nur über die
CSS-Klasse `levelN` am `<li>` erkennbar war. Vorhandenes CSS, das gezielt
`levelN` angesprochen hat, muss deshalb auf die neue Struktur umgestellt
werden — etwa auf `ul.level_2 > li` für die zweite Ebene.

Die CSS-Klassen stehen — wie beim Contao-Kern — sowohl am `<li>` als auch am
Verweiselement darin:

| Klasse | Bedeutung |
|---|---|
| `submenu` | Der Eintrag hat eine Unterebene |
| `active` | Die Seite, auf der das Element steht |
| `protected` | Geschützte Seite, ohne Verweis ausgegeben |

Die Klassen `first` und `last` gibt es nicht: Der Contao-Kern setzt sie seit
Version 5 ebenfalls nicht mehr (in 4.13 gab es sie noch), und die Ausgabe soll
auf beiden Generationen gleich sein. Für dieselbe Wirkung genügt in CSS
`li:first-child` beziehungsweise `li:last-child`.

Verlinkte Seiten stehen in einem `<a>`, nicht verlinkte in einem `<span>` — so
lassen sich beide Fälle getrennt gestalten. Ein `<span>` erscheint bei
geschützten Seiten immer und bei der aktiven Seite zusätzlich dann, wenn
"Aktive Seite nicht verlinken" aktiv ist.

Barrierefreiheit: Einträge mit Unterebene tragen `aria-haspopup="true"`, der
aktive Eintrag trägt `aria-current="page"` — beides analog zum Kern.

Der Verweistext ist der Seitentitel (`pageTitle`), ersatzweise der Seitenname.
Der Seitenname steht zusätzlich im `title`-Attribut.

Enthält die Liste keine Seite, bleibt `<nav>` leer — ohne verschachteltes
CSS für einen Leerzustand zu benötigen.

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

Ebenso trägt der Eintrag der Seite, auf der das Element steht, die Klasse
`active`. Ist "Aktive Seite nicht verlinken" aktiv, gilt für beide dasselbe wie
für geschützte Einträge: Titel als `<span>` statt als `<a>`.

Der Teaser bekommt die Klasse `teaser`. Sind am Artikel unter **Teaser-CSS-ID**
eine ID oder eigene Klassen hinterlegt, werden sie übernommen.

## Durchsuchbarkeit

Beide Templates bauen auf `block_searchable` auf. Die Listen landen damit im
Suchindex von Contao. Wer das nicht möchte, kann in einer eigenen
Template-Fassung stattdessen auf `block_unsearchable` aufbauen.
