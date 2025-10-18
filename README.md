# Turniermanagementsystem

Ein webbasierter Werkzeugkasten für Reitturnier-Organisator:innen. Die Anwendung deckt Stammdatenpflege, Nennungsabwicklung, Zeitplanung, Live-Richten und Anzeige in einem gemeinsam nutzbaren System ab – auch ohne dauerhafte Internetverbindung.

## Funktionsüberblick
- **Verwaltung & Stammdaten:** Personen, Pferde, Vereine sowie Rollenverwaltung mit Audit-Trail.
- **Events & Prüfungen:** Turnier- und Klassenverwaltung, Regel-Editor inklusive Dressur-, Spring- und Western-Presets.
- **Nennungen & Startlisten:** Manuelle Erfassung, CSV-Import, Startlistengenerator mit Vereins-Trennung und Pausenheuristik.
- **Zeitplan & Anzeige:** Slotverwaltung, Live-Verschiebung, öffentliches Display mit Starter, Top-3 und Sponsor-Ticker.
- **Richten & Ergebnisse:** Judge-UI mit Auto-Save, Signaturfreigabe, Mehr-Richter-Auswertung und Ergebnisaggregation.
- **Helferkoordination:** Rollen-/Stationsplanung, Konfliktprüfung, QR-Check-in und Übernahmeanfragen.
- **Druck & Export:** PDF-Strecken via Dompdf, CSV-/JSON-Exporte für Starter, Ergebnisse und Nennungen.
- **Benachrichtigungen:** Polling-basierte Broadcasts für Zeitplanänderungen, Starterwechsel und Ergebnisfreigaben.

## Tech-Stack
- PHP 8.2 mit eigener Lightweight-Schicht für Authentifizierung, RBAC und Auditierung
- SQLite oder MySQL/MariaDB via PDO
- Smarty-kompatible Templates mit Bootstrap 5 und jQuery (lokal gebundene Assets)
- Dompdf für Druckstrecken

## Systemvoraussetzungen
- Webserver mit PHP 8.2+, `pdo_sqlite` oder `pdo_mysql`, `mbstring`, `intl`, `gd`
- Schreibrechte für `storage/`, `public/assets/vendor/` und das Konfigurationsverzeichnis
- Optional: CLI-Zugriff für Wartungs- und Testskripte

## Installation & Einrichtung
1. Repository auschecken und Webroot auf das Projektverzeichnis zeigen lassen.
2. Benötigte Vendor-Assets (Bootstrap, jQuery, Iconsets, Fonts) lokal unter `public/assets/vendor/` bereitstellen.
3. Einen Datenbankzugang (SQLite-Datei oder MySQL/MariaDB) anlegen und Verbindung testen.
4. Im Browser `setup.php` aufrufen:
   - `.env`/Konfiguration erstellen lassen
   - Datenbankprüfung und Migration durchführen
   - Administrationskonto anlegen
   - Optional Demo-Datensatz einspielen
5. Nach Abschluss mit dem angelegten Admin im Browser anmelden.

> `setup.php` deckt die Ersteinrichtung ab; Composer-Abhängigkeiten werden nicht benötigt.

### Konfiguration
- Globale Einstellungen werden in `.env` bzw. `config/*.php` gepflegt (Datenbank, Locale, Feature-Flags).
- Rollenberechtigungen und Menü-Sichtbarkeit werden zentral in `app/Auth/Permissions.php` geregelt.
- Für SMTP/Benachrichtigungen kann `config/notify.php` angepasst werden.

## Betrieb & Wartung
- **Updates:** Neue Versionen spielen zusätzliche Migrationen im Verzeichnis `sync/migrations/` ein. Führe sie via `php sync/run.php` oder über den Setup-Assistenten aus.
- **Backups:** Vor Updates Datenbank und `storage/` sichern. Für SQLite genügt eine Dateikopie, MySQL/MariaDB über Dump.
- **Audit-Logs:** Änderungsprotokolle liegen in der Tabelle `audit_log` und können über `audit.php` eingesehen werden.
- **Fehlerdiagnose:** Log-Ausgaben befinden sich unter `storage/logs/` (konfigurierbar in `config/app.php`).

## Entwicklungs-Workflow
- **Lokales Setup:** PHP-Builtin-Server mit `php -S localhost:8000 index.php` oder ein valider Webserver. SQLite erleichtert den Einstieg.
- **Coding-Guidelines:** Kleine, modulare PHP-Dateien; Kommentare sparsam verwenden; keine Vendor-Bundles einchecken.
- **Tests:** Eigenständige PHP-Skripte im `tests/`-Verzeichnis. Beispielaufrufe:
  - `php tests/i18n_test.php`
  - `php tests/start_number_engine_test.php`
  - `php tests/scoring_engine_test.php`
- **Seed-Daten:** Für dedizierte Szenarien stehen optionale Seeds unter `sync/seeds/` bereit.

## Contribution Guide
- Issues und Feature-Requests via GitHub-Issues eröffnen.
- Pull-Requests bitte mit kurzer Feature-Beschreibung und Testnachweisen einreichen.
- Code-Style folgt PSR-12, Ausnahmen sind in `AGENTS.md` dokumentiert.
- Neue Übersetzungen in `lang/<locale>.php` ergänzen und entsprechende Tests aktualisieren.

## Browser-Support
- Chromium-basierte Browser (Edge, Chrome, Brave)
- Firefox ESR/aktueller Release
- Safari (macOS/iPadOS) für mobile Judge-UI

## Offline & Vendors
- Internetzugang ist für den Betrieb nicht erforderlich.
- Bootstrap, jQuery, Iconsets und Fonts werden **nicht** mitgeliefert. Lege sie bei Bedarf lokal unter `public/assets/vendor/` ab und nutze die vorbereiteten Pfade.

## Internationalisierung
- Übersetzungen liegen als PHP-Arrays in `lang/<locale>.php`. Keys folgen der Punkt-Notation (`dashboard.tiles.peer_connection.title`).
- Templates nutzen `t('namespace.key', ['placeholder' => $value])` bzw. `tn('namespace.key', $count)`.
- Weitere Sprachen können durch ein neues `<locale>.php` ergänzt und in `app/bootstrap.php` sowie `config/app.php` freigeschaltet werden. JavaScript erhält die aktuelle Locale über `window.I18n`.

## Lizenz
Dieses Projekt steht unter der **GNU Affero General Public License v3.0 (AGPL-3.0)**. Siehe [`LICENSE`](LICENSE) für den vollständigen Text.
