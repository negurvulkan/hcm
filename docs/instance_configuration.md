# Instanz- und Moduskonfiguration

Die Anwendung unterstützt verschiedene Instanzrollen sowie Betriebsphasen, um hybride Setups aus Online- und lokalen Turnierservern abzubilden.

## Rollen (`instance_role`)

- `ONLINE`: Schreibt in Vor- und Nachbereitung, spiegelt während des Turniers.
- `LOCAL`: Lokale Installation, die am Turniertag führend schreibt.
- `MIRROR`: Reine Anzeigeinstanz mit schreibgeschütztem Betrieb.

## Betriebsmodi (`operation_mode`)

- `PRE_TOURNAMENT`: Online agiert als Master, lokale Instanzen sind gesperrt.
- `TOURNAMENT`: Lokale Instanz schreibt, Online wechselt in den Mirror-Betrieb.
- `POST_TOURNAMENT`: Online übernimmt erneut als Master, lokale Instanz wird archiviert.

## Konfiguration

Die Einstellungen werden über die Admin-Seite **Instanz & Modus** gepflegt. Neben Rolle und Modus können Peer-Informationen (Basis-URL, API-Token, Turnier-ID) hinterlegt werden. Die Seite bietet Health-Checks, einen Dry-Run-Sync sowie einen dialoggestützten Phasenwechsel mit Checkliste.

## Durchsetzung der Schreibregeln

Abhängig von Rolle und Modus erzwingt die Anwendung schreibgeschützte Zustände. Beispielsweise laufen Mirror-Instanzen vollständig read-only, während während des Turniers die Online-Instanz blockiert und die lokale Instanz schreibt.

## Peer-Informationen

Über `/health` und `/mirror/info` stehen Endpunkte zur Verfügung, die Status, Version, Turnierdaten und letzte Synchronisation für verbundene Instanzen liefern. Die Admin-Oberfläche nutzt diese Endpunkte für Verbindungstests und Sync-Probekäufe.
