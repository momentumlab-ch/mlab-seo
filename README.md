<img src="mlab-seo/assets/images/icon-128x128.png" width="64" height="64" alt="" align="right">

# MomentumLab SEO

Suchmaschinen- und KI-Optimierung für WordPress: Titel, Meta-Beschreibungen, Google-Vorschau mit SEO-Check, Social-Vorschauen, schema.org, Sitemap, robots.txt und llms.txt.

| | |
|---|---|
| Aktuelle Version | 1.1.1 |
| WordPress | ab 6.5, getestet bis 7.1 |
| PHP | ab 8.0 |
| Lizenz | GPL-2.0-or-later |
| Sprachen | Englisch, Deutsch (de_DE, de_DE_formal, de_CH, de_CH_informal) |

## Installation

1. Die neueste `mlab-seo-x.y.z.zip` herunterladen aus den [Releases](https://github.com/momentumlab-ch/wp-mlab-seo/releases).
2. In WordPress unter **Plugins › Installieren › Plugin hochladen** die ZIP-Datei wählen und installieren.
3. Das Plugin aktivieren. Die Einstellungen erscheinen im Menü **SEO**.

## Updates

Ab Version 1.1.0 holt sich das Plugin Updates selbst aus diesem Repo:

- Der Plugin-Header enthält `Update URI: https://github.com/momentumlab-ch/wp-mlab-seo`. Dadurch fragt WordPress nicht WordPress.org, sondern das Plugin selbst nach Updates.
- Das Plugin liest das neueste veröffentlichte Release über die GitHub-API (`/releases/latest`). Entwürfe und Pre-Releases werden ignoriert.
- Als Update gilt nur die angehängte Datei `mlab-seo-<version>.zip`, nie das automatisch erzeugte Quellcode-ZIP von GitHub.
- WordPress sucht von sich aus alle 12 Stunden nach Updates. Das Plugin merkt sich die Antwort von GitHub 1 Stunde lang.
- Sofort prüfen: **Dashboard › Aktualisierungen › Erneut prüfen**.
- Neue Versionen erscheinen unter **Dashboard › Aktualisierungen** und auf der Plugin-Seite, mit den Release-Notes unter «Details ansehen». Auto-Updates lassen sich pro Plugin einschalten.
- Es werden keine Daten der Website übertragen. Das Repo ist öffentlich, ein Token ist nicht nötig.

Wer noch 1.0.0 installiert hat, muss 1.1.0 einmal von Hand hochladen. Danach laufen Updates über WordPress.

### Neue Version veröffentlichen

1. Version in `mlab-seo/mlab-seo.php` erhöhen (Header `Version` und Konstante `MLAB_SEO_VERSION`), dazu `Stable tag` und Changelog in `readme.txt` sowie einen Abschnitt in `CHANGELOG.md`.
2. Änderungen in `main` mergen.

Die GitHub Action [`release.yml`](.github/workflows/release.yml) läuft bei jedem Push auf `main`. Gibt es für die Plugin-Version noch kein Release, baut sie das ZIP aus `mlab-seo/`, erstellt Tag und Release `v<version>` und nimmt die Notizen aus dem passenden Abschnitt in `CHANGELOG.md`. Die Action lässt sich auch von Hand starten (**Actions › Release › Run workflow**).

## Aufbau des Repos

```
mlab-seo/            Quellcode des Plugins (Inhalt des ZIPs)
  mlab-seo.php       Plugin-Datei mit Header und Version
  includes/          PHP-Klassen (u. a. Updater)
  assets/            JavaScript, CSS und Plugin-Icon (images/)
  languages/         Deutsche Übersetzungen (.l10n.php)
  readme.txt         Readme im WordPress-Format
  CHANGELOG.md       Änderungen pro Version
.github/workflows/   Release-Action
```

Funktionen, Hooks und Datenschutz sind in [`mlab-seo/readme.txt`](mlab-seo/readme.txt) beschrieben, die Änderungen pro Version in [`CHANGELOG.md`](mlab-seo/CHANGELOG.md).

## Versionen

| Version | Datum | Änderungen |
|---|---|---|
| 1.1.1 | 2026-10-10 | Plugin-Icon |
| 1.1.0 | 2026-10-10 | Automatische Updates aus den GitHub-Releases |
| 1.0.0 | 2026-10-10 | Erste Version |

## Lizenz

GPL-2.0-or-later, siehe [LICENSE](LICENSE).
