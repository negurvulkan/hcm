# Roadmap: Modulare Turnier-WebApp für Reitturniere

## Zielbild
Eine modulare WebApp, die Veranstalter bei der kompletten Turnierabwicklung unterstützt – von der Meldestelle über Helferkoordination bis zum Live-Richten. Analoge (PDF, Ausdrucke) und digitale Ausgaben (Anzeige, mobile Eingaben) greifen ineinander.

## Installation auf eigenem Server
* Offline-Web-Installer (setup.php) richtet das System ohne Internet ein.
* Vendored Dependencies (Smarty, Dompdf, vlucas/phpdotenv) werden gebündelt; Frontend-Bibliotheken (Bootstrap 5, jQuery, Icon-Sets) liegen lokal unter `public/assets/vendor`.
* PDF-Druck läuft offline per Dompdf.

## Rollen & Rechte
* **Admin/Veranstalter** – Gesamtverwaltung, Systemeinstellungen, Freigaben.
* **Meldestelle** – Nennungen, Startlisten, Kommunikation mit Teilnehmern.
* **Richter/Jury** – Wertungseingabe, Signaturen, Protokolle.
* **Parcours-/Prüfungsleitung** – Zeitplan, Ablaufkoordination, Ergebnisfreigabe.
* **Helferkoordination** – Schichtplanung, Check-in, Konfliktauflösung.
* **Moderation/Ansage** – Live-Infos, Sponsoreneinblendungen.
* **Teilnehmer** – Self-Service (Nennungen, Dokumente, Ergebnisse) optional.

## Kern-Module
### Stammdaten
* Personenverwaltung (Reiter, Helfer, Richter, Teilnehmer) inkl. Rollen und Kontaktinformationen.
* Pferdedaten mit Besitz-/Reiterverknüpfung, Gesundheitsstatus, Impf- und Dokumenten-Uploads.
* Vereine/Teams inklusive Lizenzen, Qualifikationen, Vereinsmitgliedschaften.

### Prüfungen/Disziplinen
* Unterstützung für Dressur, Springen, Western, Working Equitation, TREC, Gelände u. a.
* Parameterisierbare Prüfungsprofile (Klassen, Leistungsklassen, Startberechtigungen).

### Regel-/Scoring-Engine
* Deklarative Formeln (Summe, Durchschnitt, Gewichtungen).
* Abzüge für Zeit, Fehler, Strafen; Knock-out und Stechen.
* Kriterienkataloge je Disziplin; Tiebreaker-Ketten.

### Nennungen & Meldestelle
* Manuelle Eingabe, CSV/Excel-Importe.
* Automatische Startberechtigungsprüfungen und Quali-Checks.
* Doppelstarts, Startgeldstaffeln, Spätmeldegebühren.
* Startlisten-Generator, Ummeldungen/Abmeldungen mit Audit-Trail.

### Zeitplan & Ringleitung
* Slot-Berechnung aus Startdauer, Puffern, Pausen.
* Live-Verschiebungen (minutengenau) und Push an Anzeige/Moderation.
* Mehrere Arenen/Ringe mit eigener Planung.

### Helfer-Management
* Rollen/Stationen, Schichtplanung, Konfliktprüfung.
* Check-in via QR, Ersatzlisten, Broadcast „Wer kann übernehmen?".

### Richten/Wertung
* Mobile, offlinefähige Eingaben mit Autosave.
* Mehr-Richter-Unterstützung inkl. Mittelwert, Drop-High-Low, Gewichtungen.
* Disziplinspezifische UI (Dressur, Springen, Western etc.).
* Elektronische Signatur/Freigabe, Protokoll-PDF-Generierung.

### Ergebnisse & Rangierung & Stechen
* Live-Rankings, Team-/Vereinswertungen, Serien-/Cup-Punkte.
* Automatisches Stechen mit eigener Startliste.
* Änderungslog und Freigabeprozesse.

### Anzeige & Kommunikation
* TV/Beamer-Modus, Sponsor-Banner, Druckvorlagen.
* Benachrichtigungen (Mail/SMS optional), On-Site-Ticker, QR-Aushänge.

### Finanzen
* Startgelder, Nach-/Ummeldegebühren, Rabatte, Gutscheine, Helfer-Bons.
* Zahlungsmittel (Bar, EC, QR), Quittungen/Rechnungen, Tagesabschluss.
* CSV/DATEV-kompatible Exporte.

### Dokumente & DSGVO
* Einwilligungen (Haftung, Foto/Video), Pferdepass/Impfstatus.
* Rollenbasierte Sichtbarkeit, Protokollierung.

### Import/Export
* CSV/Excel-Importe (Nennungen, Starter, Ergebnisse).
* Ergebnisexporte (CSV/JSON/PDF-Bündel), Serienpunkte, Druckstapel.

### Mehrturnier-/Mehrplatz-Support
* Mehrere Plätze mit eigenem Personal, Zeitplan, Ressourcen.
* Ressourcenplanung für Technik (Lautsprecher, Uhren, Tablets, Drucker).

## Turniertag-Flow (End-to-End)
Check-in & Zahlung → Pferdekontrolle → Startbereich → Richten → Live-Ergebnis/Stechen → Preisverteilung (Druckstapel) → Abschluss/Kassensturz/Archivierung.

## Technik & Qualität
* Service-Worker optional für Offline-Puffer.
* Migrationsskripte, Foreign Keys, Soft-Delete, Audit-Logs.
* Rate-Limits, Autosave, „Verbindung weg?"-Banner, Undo/Änderungshistorie.
* Moderationsansicht, Helfer-SOS-Knopf.

## Roadmap
* API-Schnittstellen (Zeitnahme extern, Ergebnisdienste).
* Serien/Cups, Teilnehmer-Self-Service, E-Mail/SMS-Versand.
* Erweiterte DATEV-Schnittstelle, Mehrsprachigkeit, Theme-System.
