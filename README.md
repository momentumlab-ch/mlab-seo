# MomentumLab SEO

Suchmaschinen- und KI-Optimierung für WordPress: Titel, Meta-Beschreibungen, Google-Vorschau mit SEO-Check, Social-Vorschauen, schema.org, Sitemap, robots.txt und llms.txt.

| | |
|---|---|
| Aktuelle Version | 1.0.0 |
| WordPress | ab 6.5, getestet bis 7.1 |
| PHP | ab 8.0 |
| Lizenz | GPL-2.0-or-later |
| Sprachen | Englisch, Deutsch (de_DE, de_DE_formal, de_CH, de_CH_informal) |

## Installation

1. Den Ordner `mlab-seo/` als ZIP packen (oder ein Release-ZIP verwenden).
2. In WordPress unter **Plugins › Installieren › Plugin hochladen** die ZIP-Datei wählen und installieren.
3. Das Plugin aktivieren. Die Einstellungen erscheinen im Menü **SEO**.

## Aufbau des Repos

```
mlab-seo/            Quellcode des Plugins (Inhalt des ZIPs)
  mlab-seo.php       Plugin-Datei mit Header und Version
  includes/          PHP-Klassen
  assets/            JavaScript und CSS
  languages/         Deutsche Übersetzungen (.l10n.php)
  readme.txt         Readme im WordPress-Format
  CHANGELOG.md       Änderungen pro Version
```

Funktionen, Hooks und Datenschutz sind in [`mlab-seo/readme.txt`](mlab-seo/readme.txt) beschrieben, die Änderungen pro Version in [`CHANGELOG.md`](mlab-seo/CHANGELOG.md).

## Lizenz

GPL-2.0-or-later, siehe [LICENSE](LICENSE).
