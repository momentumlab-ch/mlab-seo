# Was noch fehlt

Stand 9.11.2026, Basis MomentumLab SEO 1.7.0. Verglichen mit der Gap-Analyse (Stand 1.2.0/1.3.0), den offenen Punkten zu 1.6.0 und den Redesign-Mockups in diesem Ordner. Sortiert nach Wirkung für die Nutzer, Aufwand grob S / M / L.

## A. Für das Redesign nötig

| # | Lücke | Warum wichtig | Aufwand |
|---|---|---|---|
| 1 | **SEO-Seitenleiste im Block-Editor** | Betrifft jede bearbeitete Seite. Heute liegt die Box unter dem Inhalt, im Block-Editor von WordPress 7 sogar im zugeklappten Bereich «Meta Boxes». Viele sehen sie gar nicht. | M |
| 2 | **Check-Wert für alle Seiten auf dem Server** | Der Wert wird heute nur im Browser berechnet (`assets/js/editor.js`) und erst gespeichert, wenn jemand die Seite im Editor öffnet. Seiten, die nie geöffnet wurden, zeigen «–». Ohne Server-Berechnung bleiben Übersicht und Seitenliste leer. | M |
| 3 | **Seitenliste mit Bearbeiten-Sheet und Sammelbearbeitung** | Schliesst die Lücke «Sammelbearbeitung» aus der Gap-Analyse. Heute kann man nur einzeln (Schnellbearbeitung) Titel ändern und mehrfach nur noindex setzen, und nirgends sieht man, welche Seiten keine Beschreibung haben. | L |
| 4 | **Übersicht mit Aufgaben statt Statusliste** | Die Daten gibt es schon (`Admin::status()`, Inhalts-Check, Link-Check, 404-Log). Es fehlen die Zählung «ohne Beschreibung» und je ein Knopf, der direkt zur Lösung führt. | S |
| 5 | **Unterseiten und automatisches Speichern** | Heute ein Formular über fünf Reiter mit «Einstellungen speichern», dazu eigene Formulare für Weiterleitungen, Lizenz und Import. Der Reiter «General» mischt sieben Themen (Startseite, Social, Links, Brotkrumen, Bestätigung, Sprachen, WooCommerce). | M |
| 6 | **Social-Vorschau als Karte** | Heute zeigt der Reiter «Social media» nur das Bild. Wie der Link in WhatsApp oder LinkedIn aussieht, sieht man nicht. | S |
| 7 | **robots.txt-Vorschau und Link** | Im ganzen Plugin gibt es keinen Link auf robots.txt. Man tippt eigene Regeln, ohne die fertige Datei mit den KI-Regeln zu sehen. | S |
| 8 | **Voreinstellung für KI-Crawler** («Alle», «Nur Suche», «Keine») | Heute 12 einzelne Kästchen; die meisten wollen nur «in KI-Suchen erscheinen, aber nicht ins Training». | S |

## B. Funktionen, die noch fehlen

| # | Lücke | Warum wichtig | Aufwand |
|---|---|---|---|
| 9 | **Beiträge standardmässig nicht in der Sitemap** | Der Standard von `sitemap_types` ist nur `page` (`inc/options.php`). Auf einer neuen Website mit Blog fehlen die Beiträge in der Sitemap, bis jemand den Schalter findet. Bitte prüfen, ob das Absicht war. | S |
| 10 | **Einrichtungsassistent** (3 Schritte: Website und Unternehmen, Import, KI-Freigabe) | Yoast und Rank Math haben einen. Nach der Aktivierung sind heute Publisher und Beschreibung der Startseite leer. | M |
| 11 | **Beschreibung aus dem Seitentext vorschlagen** | Ohne KI, aus dem ersten Absatz auf 155 Zeichen gekürzt. Macht die Aufgabe «5 Seiten ohne Beschreibung» in Sekunden lösbar. | S |
| 12 | **IndexNow-Verlauf pro Seite** | Heute wird nur die letzte Übermittlung gespeichert. Ob eine bestimmte Seite gemeldet wurde, sieht man nicht. | S |
| 13 | **Interne Link-Vorschläge** | Aus der Gap-Analyse noch offen. Bewusst zurückgestellt, weil der Ranking-Effekt klein ist. | L |
| – | Search-Console-Daten, HowTo, News-Sitemap | Bewusst nicht umgesetzt (siehe Gap-Analyse), bleibt so. | – |

## C. Offen aus dem Plan zu 1.6.0

Erledigt seit dem Plan: Funktionslängen gekürzt und FAQ-Block über `wp.i18n` (beides 1.6.1), Review-Kommentare zu PR #10 (gemerged).

| # | Punkt | Stand |
|---|---|---|
| 14 | WooCommerce mit echtem Shop testen (einfaches und variables Produkt, Bewertungen) | Im Repo kein Beleg, dass es gemacht wurde. |
| 15 | Update-Weg von 1.5.0 auf die neue Version prüfen | Im Repo kein Beleg. |
| 16 | Sprache im Changelog | Ab 1.6.0 deutsch, 1.5.0 und älter englisch. Entscheid offen. |
| 17 | GitHub App auf dem umbenannten Repo `mlab-seo` | Offen laut Plan. |
| 18 | Übergangs-ZIP `mlab-seo-<version>.zip` entfernen | Erst, wenn keine Website mehr 1.5.0 oder älter hat. |

## Empfohlene Reihenfolge

1. Nr. 9 (Sitemap-Standard) sofort prüfen: kleiner Eingriff, grosse Wirkung.
2. Nr. 1, 6 und 11 zusammen als «Editor-Paket».
3. Nr. 2, 3 und 4 als «Übersicht und Seiten».
4. Nr. 5, 7 und 8 mit dem Umbau der Einstellungen.
5. Nr. 10 zum Schluss, weil der Assistent die neuen Unterseiten wiederverwendet.
