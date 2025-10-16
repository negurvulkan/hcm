# Turniermanagement V2

Ein schlankes Grundsystem für Reitturnier-Organisatoren. Fokus auf offlinefähiger Abwicklung: Meldestelle, Richten, Anzeige und Helferkoordination laufen auf einem gemeinsamen Web-Stack.

## Tech-Stack
- PHP 8.2 mit eigener Lightweight-Schicht für Auth, RBAC, Audit
- SQLite oder MySQL/MariaDB über PDO
- Smarty-kompatible Templates, Bootstrap 5 / jQuery (lokal referenziert)
- Dompdf für Druckstrecken

## Setup
1. PHP-Builtin-Server oder kompatiblen Webserver bereitstellen.
2. `setup.php` aufrufen – der Assistent legt `.env`/Konfig an, prüft die DB, erstellt einen Admin und kann optional Demodaten seeden.
3. Anschließend per Login im Browser anmelden.

> Hinweis: `setup.php` übernimmt die Minimalinitialisierung, es ist kein Composer-Lauf nötig.

## Browser-Support
- Chromium-basierte Browser (Edge, Chrome, Brave)
- Firefox ESR/aktueller Release
- Safari (macOS/iPadOS) für mobile Judge-UI

## Offline & Vendors
- Für den Betrieb ist kein Internetzugang erforderlich.
- Bootstrap, jQuery, Iconsets und Fonts werden **nicht** mitgeliefert. Lege sie bei Bedarf lokal unter `public/assets/vendor/` ab und referenziere sie über die bereits vorbereiteten Pfade.

## Entwicklungsnotizen
- Keine Vendor-Bundles ins Repository einchecken.
- Nur kleine, klar abgegrenzte Dateien erzeugen.
- Datenbank- und Ordnerstrukturen können projektspezifisch angepasst werden.
