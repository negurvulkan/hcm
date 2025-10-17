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

## Party/Rollen-Modell
- **Kurzarchitektur:** Eine gemeinsame Stammtabelle `parties` verwaltet Personen und Organisationen. Personenspezifische Angaben wie Vereinszuordnung oder Sprache liegen in `person_profiles`. Rollen (z. B. `judge`, `office`) werden normiert in `party_roles` gespeichert und können über den `context` weiter segmentiert werden. Clubs sind als Organisationen modelliert und verknüpfen ihren Datensatz mit `organization_profiles` sowie einem Eintrag in `parties`.
- **Migrationsschritte:** Bestehende Installationen erhalten das neue Schema über `20240730000000__party_role_model.php`. Die Migration legt die neuen Tabellen an, überführt alle bisherigen `persons`-Datensätze, migriert Referenzen (`entries.party_id`, `horses.owner_party_id`, `helper_shifts.party_id`) und erzeugt Organisations-Partys für vorhandene Vereine. Neue Setups nutzen automatisch die aktualisierte Initialmigration.
- **Rollentabelle:** `party_roles` enthält `party_id`, `role`, `context`, `assigned_at` und `updated_at`. Pro Kombination aus Partei/Rolle/Kontext besteht ein Unique-Index. Rollenänderungen aktualisieren den Timestamp und sind damit für Sync- und Audit-Prozesse nachvollziehbar.

## Entwicklungsnotizen
- Keine Vendor-Bundles ins Repository einchecken.
- Nur kleine, klar abgegrenzte Dateien erzeugen.
- Datenbank- und Ordnerstrukturen können projektspezifisch angepasst werden.

## Internationalisierung
- Übersetzungen liegen als PHP-Arrays in `lang/<locale>.php`. Keys sind per Punkt-Notation strukturiert (z. B. `dashboard.tiles.peer_connection.title`).
- Neue Keys werden über `t('namespace.key', ['placeholder' => $value])` bzw. `tn('namespace.key', $count)` genutzt. Templates und Controller erhalten damit sprachabhängige Strings.
- Zusätzliche Sprachen ergänzt man, indem ein weiteres `<locale>.php` erstellt und in der App-Konfiguration (`app/bootstrap.php`, `config/app.php`) whitelistet wird. JS greift automatisch auf die aktuelle Locale via `window.I18n` zu.
