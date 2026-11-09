# Redesign MomentumLab SEO: Mockups

Statische, klickbare HTML-Mockups für ein neues Admin-Design im Stil von Apple: klare Hierarchie, viel Weissraum, einfache Bedienelemente (Schalter, Segmente, gruppierte Listen), Systemschrift.

**Regel:** Jede häufige Aufgabe ist in höchstens drei Klicks ab dem Menüeintrag «SEO» erreichbar.

Öffnen: `docs/design/index.html` im Browser. Alles läuft lokal ohne Server und ohne externe Dateien. `docs/` ist in `.distignore` und landet nicht im Plugin-ZIP.

## Aufbau

Statt einer Seite mit neun Reitern und einem gemeinsamen «Einstellungen speichern» gibt es sieben echte Unterseiten im WordPress-Menü unter «SEO»:

| Datei | Unterseite | Inhalt |
|---|---|---|
| `01-uebersicht.html` | Übersicht | Zustand der Website, Aufgaben mit Direkt-Knopf, Schnelllinks zu sitemap.xml, robots.txt, llms.txt |
| `02-seiten.html` | Seiten | Alle Seiten mit Check-Wert, Filter «Handlungsbedarf». Klick auf eine Zeile öffnet ein Sheet mit Titel, Beschreibung, Google-Vorschau, Social-Vorschau, SEO-Check und Rich Results. `02-seiten.html#bearbeiten` öffnet das Sheet direkt. |
| `03-editor.html` | Block-Editor | SEO als Seitenleiste mit eigenem Knopf in der Werkzeugleiste, statt Box unter dem Inhalt |
| `04-suchdarstellung.html` | Suchdarstellung | Segmente Startseite, Vorlagen, Unternehmen (schema.org) |
| `05-ki-crawler.html` | KI & Crawler | Segmente Crawler (mit Zugriffstest), robots.txt, llms.txt, IndexNow, jeweils mit Live-Vorschau der Datei |
| `06-sitemap.html` | Sitemap | Adresse zum Kopieren, Inhalte mit Anzahl Einträge |
| `07-weiterleitungen.html` | Weiterleitungen (Pro) | 404-Liste zuerst, mit Vorschlag und einem Knopf; aktive Weiterleitungen |
| `08-einstellungen.html` | Einstellungen | Umzug (Import), Website bestätigen, Links und Bilder, Brotkrumen, Sprachen, Technik, Lizenz |

Einstellungen speichern automatisch (Schalter sofort, Textfelder beim Verlassen des Felds). Nur das Sheet einer Seite hat «Sichern», weil dort mehrere Texte zusammengehören.

## Klickpfade

Gezählt ab dem Klick auf «SEO» im WordPress-Menü (= Klick 1). Im Editor zählt der Klick auf den SEO-Knopf als Klick 1. Speichern ist nicht mitgezählt; heute kommt bei jeder Einstellung noch «Einstellungen speichern» dazu.

| Aufgabe | Heute | Klicks heute | Neu | Klicks neu |
|---|---|---|---|---|
| Titel und Meta-Beschreibung einer Seite | Seiten › Seite › Bereich «Meta Boxes» aufklappen › scrollen. Aus dem SEO-Menü gar nicht erreichbar. | 3 + Scrollen | SEO › Seiten › Zeile | **3** |
| … aus einer Aufgabe in der Übersicht | – | – | SEO › «Ergänzen» | **2** |
| … beim Schreiben im Editor | Meta-Box aufklappen, scrollen | 1 + Scrollen | SEO-Knopf | **1** |
| Google-Vorschau prüfen | wie Titel | 3 + Scrollen | SEO › Seiten › Zeile (Vorschau rechts) | **3** (Editor: **1**) |
| Social-Vorschau prüfen | wie Titel › Reiter «Social media». Zeigt nur das Bild, keine Karte. | 4 + Scrollen | SEO › Seiten › Zeile (Karte unter der Google-Vorschau) | **3** (Editor: **2**) |
| Schema einer Seite (Rich Results) | wie Titel › Reiter «Event & schema» | 4 + Scrollen | SEO › Seiten › Zeile (Abschnitt «Rich Results») | **3** |
| Unternehmen / Publisher (schema.org) | SEO › Publisher | 2 | SEO › Suchdarstellung › Unternehmen | **3** |
| Titel der Startseite | SEO (Reiter General, Mitte der Seite) | 1 + Scrollen | SEO › Suchdarstellung | **2** |
| Titel-Vorlagen pro Inhaltstyp | SEO › Titles & meta | 2 | SEO › Suchdarstellung › Vorlagen | **3** |
| Sitemap einstellen | SEO › Sitemap | 2 | SEO › Sitemap | **2** |
| Sitemap ansehen | SEO › Link im Status | 2 | SEO › Chip «sitemap.xml» | **2** |
| robots.txt anpassen | SEO › AI & crawlers › scrollen | 2 + Scrollen | SEO › KI & Crawler › robots.txt | **3** |
| robots.txt ansehen | kein Link im Plugin | – | SEO › Chip «robots.txt» | **2** |
| llms.txt anpassen | SEO › AI & crawlers › ganz nach unten | 2 + Scrollen | SEO › KI & Crawler › llms.txt | **3** |
| KI-Crawler freigeben | SEO › AI & crawlers › 12 Kästchen | 2 + bis 12 | SEO › KI & Crawler › «Nur Suche» | **3** |
| Zugriffstest starten | SEO › AI & crawlers › scrollen › Knopf | 3 + Scrollen | SEO › KI & Crawler › «Jetzt prüfen» | **3** |
| IndexNow ein/aus, Status | SEO › AI & crawlers › scrollen | 2 + Scrollen | SEO › KI & Crawler › IndexNow (Status auch in der Übersicht) | **3** (Status: **1**) |
| 404 weiterleiten | SEO › Redirects › unter die Regeln scrollen › «Redirect» | 3 + Scrollen | SEO › Weiterleitungen › «Weiterleiten» | **3** |
| Kaputte Links finden | SEO › Content › scrollen | 2 + Scrollen | SEO › Aufgabe «Seite öffnen» | **2** |
| Aus Yoast & Co. übernehmen | SEO › Import › «Import now» | 3 | SEO › «Daten übernehmen» › «Jetzt übernehmen» | **3** |
| Search Console bestätigen | SEO (General) › scrollen | 1 + Scrollen | SEO › Einstellungen › Zeile | **3** |
| Lizenz | SEO › Licence | 2 | SEO › Einstellungen (unten) | **2** |

## Umsetzung (Skizze)

- **Unterseiten:** `add_submenu_page()` pro Bereich statt Reiter mit `#hash`. Jede Unterseite eine eigene Datei in `parts/admin/`, die Felder bleiben in `inc/options.php`.
- **Automatisch speichern:** ein REST-Endpunkt `mlab-seo/v1/settings` (PATCH, ein Feld), Rechte über `Admin::capability()`, Bereinigung weiter über `Settings::sanitize()`.
- **Seiten:** eigene `WP_List_Table` über `Post_Meta`. Das Sheet speichert über die registrierten Post-Meta-Felder (REST), die Schnellbearbeitung fällt weg.
- **Editor:** `PluginSidebar` und `PluginDocumentSettingPanel` aus `wp.editPost` / `wp.editor`, ohne Build mit `wp.element.createElement`. Die Meta-Box bleibt für den klassischen Editor.
- **Segmente und Sheet:** gemeinsame Komponenten in `assets/css/admin.css`, Farben wie im Mockup, Systemschrift von WordPress.

Was dafür fehlt und was sonst noch offen ist: [LUECKEN.md](LUECKEN.md).
