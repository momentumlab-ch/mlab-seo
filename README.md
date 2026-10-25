<img src="assets/img/icon-128x128.png" width="64" height="64" alt="" align="right">

# MomentumLab SEO

Suchmaschinen- und KI-Optimierung für WordPress: Titel, Meta-Beschreibungen, Google-Vorschau mit SEO-Check, Social-Vorschauen, schema.org, Sitemap, robots.txt und llms.txt.

| | |
|---|---|
| Aktuelle Version | 1.6.0 |
| WordPress | ab 6.5, getestet bis 7.1 |
| PHP | ab 8.0 |
| Lizenz | GPL-2.0-or-later |
| Sprachen | Englisch, Deutsch (de_DE, de_DE_formal, de_CH, de_CH_informal) |

## Installation

1. Die neueste `mlab-seo.zip` herunterladen aus den [Releases](https://github.com/momentumlab-ch/mlab-seo/releases).
2. In WordPress unter **Plugins › Installieren › Plugin hochladen** die ZIP-Datei wählen und installieren.
3. Das Plugin aktivieren. Die Einstellungen erscheinen im Menü **SEO**.

## Updates

Updates laufen über die Update-Funktion von WordPress mit dem [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) (`lib/plugin-update-checker/`, eingebunden in `inc/updater.php`):

- WordPress fragt das neueste Release dieses Repos ab. Als Update gilt nur die angehängte Datei `mlab-seo.zip`.
- Neue Versionen erscheinen unter **Dashboard › Aktualisierungen** und auf der Plugin-Seite, mit Changelog unter «Details ansehen». Auto-Updates lassen sich pro Plugin einschalten.
- Das Repo ist öffentlich, ein Token ist nicht nötig. Optional (etwa bei vielen Websites hinter einer IP) in `wp-config.php`: `define( 'MLAB_SEO_GITHUB_TOKEN', '…' );`
- Websites mit 1.4.x und 1.5.0 suchen mit dem alten Updater noch nach `mlab-seo-<version>.zip`. Die Release-Action hängt diese Datei deshalb zusätzlich an. Ab 1.6.0 übernimmt der Plugin Update Checker.

### Neue Version veröffentlichen

1. Version in `mlab-seo.php` erhöhen (Header `Version` und Konstante `MLAB_SEO_VERSION`), dazu `Stable tag` und Changelog in `readme.txt` sowie einen Abschnitt in `CHANGELOG.md`.
2. Änderungen in `main` mergen.

Die GitHub Action [`release.yml`](.github/workflows/release.yml) läuft bei jedem Push auf `main`. Gibt es für die Version noch kein Release, baut sie `mlab-seo.zip` (ohne die Dateien aus `.distignore`), prüft Header, Konstante und `Stable tag`, erstellt Tag und Release `v<version>` und nimmt die Notizen aus dem passenden Abschnitt in `CHANGELOG.md`. Die Action lässt sich auch von Hand starten (**Actions › Release › Run workflow**).

## Aufbau des Repos

```
mlab-seo.php         Plugin-Datei mit Header und Version, lädt die Module
inc/                 PHP-Klassen, options.php (alle Einstellungen), updater.php
parts/               Vorlagen für Einstellungen, SEO-Box und Kategorie-Felder
assets/              JavaScript, CSS und Plugin-Icon (img/)
languages/           Übersetzungen (.pot, .po, .mo, .l10n.php, .json für JavaScript)
lib/                 Plugin Update Checker
readme.txt           Readme im WordPress-Format
CHANGELOG.md         Änderungen pro Version
.github/workflows/   Release-Action
```

Prüfen mit PHPCS: `phpcs --standard=phpcs.xml.dist .`

Funktionen, Hooks und Datenschutz sind in [`readme.txt`](readme.txt) beschrieben, die Änderungen pro Version in [`CHANGELOG.md`](CHANGELOG.md).

## Versionen

| Version | Datum | Änderungen |
|---|---|---|
| 1.6.0 | 2026-10-25 | Titel-Vorlagen mit Variablen, Schnellbearbeitung, Robots-Regeln, Hauptkategorie, Archive, Link- und Bild-Filter, WooCommerce-Schema, Regex-Weiterleitungen und Import aus Rank Math, Updates über Plugin Update Checker |
| 1.5.0 | 2026-10-25 | Rich Snippets in der Google-Vorschau |
| 1.4.2 | 2026-10-25 | Gängige Sitemap-Adressen leiten direkt auf wp-sitemap.xml weiter |
| 1.4.1 | 2026-10-25 | Sitemap ohne XSL-Stylesheet (keine XSLT-Warnung in Chrome) |
| 1.4.0 | 2026-10-22 | 404-Wächter mit 1-Klick-Weiterleitung, Prüfung defekter Links |
| 1.3.0 | 2026-10-10 | FAQ-Block mit Schema, Bilder- und Video-Sitemap, hreflang, Inhalts-Check für dünne Seiten |
| 1.2.0 | 2026-10-10 | Import aus Yoast, Rank Math, SEOPress und AIOSEO, SEO für Kategorien, IndexNow, Breadcrumbs, Local SEO, Social-Titel, Lesbarkeits-Check |
| 1.1.1 | 2026-10-10 | Plugin-Icon |
| 1.1.0 | 2026-10-10 | Automatische Updates aus den GitHub-Releases |
| 1.0.0 | 2026-10-10 | Erste Version |

## Lizenz

GPL-2.0-or-later, siehe [LICENSE](LICENSE).
