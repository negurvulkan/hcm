# ROADMAP

Diese Roadmap beschreibt die geplanten Entwicklungsphasen des Turnierverwaltungssystems.  
Ziel ist der Aufbau einer stabilen, modularen und reitweisenunabhängigen Plattform zur Vorbereitung und Durchführung von Reitturnieren — sowohl online als auch offline.

## Version 1.0 – Basisfunktionalität (MVP)

**Ziele:** stabiles Kernsystem, Turniere offline/online durchführen, Grundfunktionen produktionsreif.

- [x] Hybridbetrieb (Online/Offline) mit Rollen `ONLINE`, `LOCAL`, `MIRROR`
- [x] Startnummernvergabe über konfigurierbare Regeln (classic/western/custom)
- [x] Turnierphasensteuerung: Prä-Turnier, Turnier, Post-Turnier
- [x] Stammdatenverwaltung (Reiter, Pferde, Vereine)
- [x] Prüfungsverwaltung inkl. Regelengine (JSON-basiert)
- [x] Startlisten-Generator (Basislogik + Konfliktprüfung)
- [x] Zeitplanverwaltung (manuell + Verschiebungen)
- [x] Richteroberfläche (mobilfähig, offlinefähig)
- [x] Ergebnisverwaltung inkl. Tiebreaker
- [x] Druckvorlagen (Startlisten, Richterbögen, Ergebnisse, Urkunden)
- [x] Anzeigeansichten für Zuschauer (TV/Beamer)
- [x] Rollen- und Rechteverwaltung (RBAC)
- [x] Installationsroutine (Web-Installer, SQLite/MySQL)
- [ ] Grundlegende Sync-Funktionen (Pull/Push Online ↔ Lokal)
- [x] Health-/Info-Endpunkte für Instanzabfrage

## Version 1.1 – Erweiterte Turnierpraxis

**Ziele:** bessere Arbeitsabläufe am Turniertag, höhere Stabilität, erste Zusatzfunktionen.

- [ ] Sponsor-Modul (Bannerplätze auf Anzeigeansichten)
- [x] Live-Ticker (Silent Broadcast im Browser)
- [x] Startnummernverwaltung mit Blocklisten & Overrides
- [x] Verbesserte Zeitplanlogik (Slots, automatische Anpassung bei Verzögerungen)
- [x] Helferplanung (Schichtplan, Check-in)
- [x] Audit-Logging für alle Änderungen (Eintrag, Zeit, User, Aktion)
- [x] Erweiterte CSV-/JSON-Exporte (Ergebnisse, Meldungen, Finanzen)
- [ ] Bessere QR-Integration (z. B. für Richterlinks)

## Version 1.2 – Automatisierung & Integration

**Ziele:** Abläufe optimieren, externe Anbindungen ermöglichen, Reporting verbessern.

- [x] Erweiterte Regelengine (mehr Reitweisen, Gewichtungen, Mehr-Richter-Prüfungen)
- [x] Live-Zeitplananpassung mit Push an Anzeigen
- [ ] Statistiken & Auswertungen (Starterzahlen, Laufzeiten, Ausfälle)
- [ ] Automatische Sponsorberichte (Laufzeit / Sichtbarkeit)
- [ ] Erweiterte Druckfunktionen (Sponsoring/Branding auf Startnummern)
- [ ] Verbesserte Sync-Protokolle (Deltas, Konfliktauflösung, Wiederaufsetzen)
- [ ] Offene REST-API für externe Systeme
- [ ] Erste Plugin-Schnittstellen (z. B. externe Wertungslogiken)

## Version 2.0 – Erweiterbarkeit & Mobile Nutzung

**Ziele:** System skalierbar und verbandsfähig machen, mobile Komponenten ergänzen.

- [ ] Companion-App (Starter & Helfer)
- [ ] Mobile Check-in/Push-Nachrichten für Starter
- [ ] Helfer-App (Schichtwechsel, SOS-Broadcast, Live-Aufrufe)
- [ ] Plug-in-API für Reitweisen, Score-Engines und externe Systeme
- [ ] Mehrturnier-/Mehrplatz-Support (parallel laufende Prüfungen)
- [ ] Fortgeschrittene Sponsor-Integration (Anzeigenrotation, Reportings)
- [ ] Verbands-Schnittstellen (z. B. Export für FN/AQHA/WE-Verbände)

## Version 2.5+ – Intelligente Funktionen & Skalierung

**Ziele:** Komfortfunktionen, Automatisierung und große Veranstalterstrukturen unterstützen.

- [ ] Intelligente Startreihenfolgen (Pferdepausen, Vereinsblöcke, Optimierung)
- [ ] Prognosen & Warnungen (Verzögerungen, Zeitplanüberschneidungen)
- [ ] KI-Assistenzfunktionen (Fehlervorhersage, Konflikterkennung)
- [ ] Serien- & Cup-Wertungen
- [ ] Automatisierte Zeit- und Ressourcenplanung
- [ ] Erweiterte Mandantenfähigkeit (Verbände, Regionen, Großveranstaltungen)
- [ ] Fernwartung und OTA-Updates für Turnierboxen (Raspberry Pi / Mini-PC)


## Laufende Themen (parallel über alle Versionen hinweg)

- [ ] Stabilität und Offline-Robustheit
- [ ] Barrierearme und touch-optimierte Oberflächen
- [ ] Datenschutz und DSGVO-Compliance
- [ ] Dokumentation & Handbücher
- [ ] Tests, Backups und Recovery-Routinen
- [ ] Community-Funktionen (Open Source Beiträge, Plugins, Übersetzungen)

Diese Roadmap ist ein Arbeitsdokument und kann sich je nach Prioritäten und Community-Feedback ändern.
