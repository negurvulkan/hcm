# Projektleitfaden – Turniermanagement V2

## Feature-Matrix

### Basis & Sicherheit
- Konfigurierbarer Setup-Assistent (ENV-Laden, DB-Check, Admin-Anlage, optionaler Demo-Seed) — **V2-Umfang**
- Authentifizierung mit Login/Logout, Sessions, CSRF-Token, Passwortänderung — **V2-Umfang**
- Rollenbasiertes Berechtigungssystem (admin, office, judge, steward, helpers, announcer, participant) — **V2-Umfang**
- Serverseitige Validierung kritischer Aktionen — **V2-Umfang**
- Rudimentäres Rate-Limit für Live-Eingaben — **V2-Umfang**
- Audit-Trail inklusive Undo der letzten Änderung pro Start/Score — **V2-Umfang**
- Erweiterte Sicherheitsmaßnahmen (2FA, vollständige DSGVO-Workflows) — **Optional**

### UI & Layout
- Smarty-Layout mit Bootstrap-Grundgerüst, Navbar, Flash-Messages, Dark/Light-Variablen — **V2-Umfang**
- Dezentes UI ohne Bilder, vendor assets nur lokal referenziert — **V2-Umfang**
- Barrierearme Judge-UI (Tastaturbedienung) — **V2-Umfang**
- Vollständiges Theme-/Branding-System — **Geplant**

### Dashboard & Navigation
- Rollenabhängige Dashboard-Kacheln mit Statusanzeigen (Zeitplan, Nennungen, laufende Prüfung, Helfer-Check-ins) — **V2-Umfang**
- Öffentliche Anzeige (Display) mit aktuellem Starter, Top-3, Sponsor-Ticker — **V2-Umfang**
- Erweiterte Analytics & Widgets — **Geplant**

### Stammdaten
- Personen-CRUD mit Rollenverwaltung und Suche/Filter — **V2-Umfang**
- Pferde-CRUD mit Besitzerzuordnung und Dokumentenstatus — **V2-Umfang**
- Vereine-CRUD mit Kürzel und Filter — **V2-Umfang**
- Upload-Handling für Dokumente (Impfpass etc.) — **Geplant**

### Events & Klassen
- Turnierverwaltung (Titel, Daten, Orte) — **V2-Umfang**
- Prüfungsverwaltung (Bezeichnung, Platz, Zeitfenster, max. Starter, Richter) — **V2-Umfang**
- JSON-Regel-Editor mit Dressur/Springen/Western-Presets und konfigurierbarer Tiebreaker-Kette — **V2-Umfang**
- Mehrplatz- und Mehrturnierressourcenplanung — **Optional**

### Nennungen & Startlisten
- Manuelle Nennung (Reiter, Pferd, Prüfung) mit Status offen/bezahlt — **V2-Umfang**
- CSV-Import mit Mapping-Dialog — **V2-Umfang**
- Startlisten-Generator (Vereinstrennung, Pausenheuristik) — **V2-Umfang**
- Ummelden/Abmelden inkl. Audit-Trail — **V2-Umfang**
- Externe Nennungsschnittstellen — **Optional**

### Zeitplan & Anzeige
- Slotverwaltung mit Startzeit und Intervallbearbeitung — **V2-Umfang**
- Live-Verschiebung (+/- Minuten) mit Broadcast-Event für Dashboard/Display — **V2-Umfang**
- On-Site Ticker per Polling/Long-Poll — **V2-Umfang**
- Automatisierte Ressourcenplanung (Richterreihenfolge, Material) — **Geplant**

### Richten & Ergebnisse
- Judge-UI für Dressur (0–10 in 0.5), Springen (Zeit+Fehler), Western (Maneuver + Penalties) — **V2-Umfang**
- Auto-Save mit lokalem Puffer und Offline-Hinweis — **V2-Umfang**
- Elektronische Signatur/Freigabe je Start — **V2-Umfang**
- Live-Ergebnisaggregation mit Tiebreaker und Änderungslog — **V2-Umfang**
- Mehr-Richter-Wertungen und Gewichtungen — **Geplant**
- Externe Zeitnahmeintegration — **Optional**

### Helferkoordination
- Rollen/Stationen-Definition und Schichtplaner — **V2-Umfang**
- Konfliktprüfung Person vs. Zeitfenster — **V2-Umfang**
- QR-Check-in mit Tokenverwaltung und Liste — **V2-Umfang**
- Broadcast „Wer kann übernehmen?“ — **Optional**

### Druck & Export
- PDF-Erzeugung via Dompdf (Startliste, Richterbogen je Preset, Ergebnisliste, Urkunde) — **V2-Umfang**
- CSV-Export (Nennungen, Starter, Ergebnisse) — **V2-Umfang**
- JSON-Export (Ergebnisse einer Prüfung) — **V2-Umfang**
- DATEV-/Finanzexporte — **Optional**

### Benachrichtigungen & Ticker
- Notify-Endpunkte für Zeitplan-Shift, Starterwechsel, Ergebnisfreigaben — **V2-Umfang**
- ticker.js (Polling, UI-Updates) — **V2-Umfang**
- Push-/Broadcast-Services (WebSockets, Push-API) — **Optional**

### Nicht in V2
- Serien-/Cup-Punkte-Automatik, Sponsoren-Scheduler, Teilnehmer-Self-Service, Service-Worker-Offline-Caching, vollständige DSGVO-Abwicklung — **Optional**

## Modul-Interaktionen
- `setup.php` initialisiert Konfiguration, ruft Schema-Migration und optionales Seeding.
- `auth.php` kapselt Sessions, CSRF, Login/Logout und wird von allen Modul-Controllern eingebunden.
- Seiten-Controller (`dashboard.php`, `persons.php`, `horses.php`, ...) interagieren über das Datenzugriffslayer mit der Datenbank und geben Daten an Smarty-Templates (`*.tpl`) weiter.
- `helpers.js` und `ticker.js` stellen Client-Hilfsfunktionen bzw. Polling für `notify.php` bereit.
- `notify.php` liefert Broadcast-Events für `dashboard.tpl` und `display.tpl`.
- `audit.php` wird von kritischen Aktionen (Entries, Startlisten, Scores) genutzt, um Änderungen zu protokollieren und ggf. Undo auszulösen.
- `print.php` nutzt Dompdf und die entsprechenden Smarty-Templates für Druckansichten.
- `export.php` stellt CSV/JSON-Dumps bereit und bindet Validierungen/RBAC aus `auth.php` ein.

## Hinweise
- Vendor-Assets werden nicht eingecheckt, sondern nur unter `public/assets/vendor/` referenziert.
- Dateien klein und modulbezogen halten, keine globalen Dogmen zu Ordnern oder Datenbank benennen.
- Kommentare nur sparsam einsetzen.
