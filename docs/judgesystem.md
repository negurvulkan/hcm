# Scoring-Regel-System / Scoring Rule System

## Deutsch

### Überblick
Das Scoring-Regel-System beschreibt als JSON-Objekt, wie Richterbewertungen, Zusatzfelder und Zeitstrafen zu einem Endergebnis verarbeitet werden. Jede Regel wird beim Speichern mit Standardwerten ergänzt, damit auch unvollständige Eingaben gültig bleiben.

### Top-Level-Struktur
- `version` (String) – Regelversion zur Nachverfolgung.
- `id` (String) & `label` (String) – interne Kennung und sprechender Name der Regel.
- `input` (Objekt) – Definition der erwarteten Eingaben (siehe unten).
- `penalties` (Array) – konfigurierbare Strafpunkte oder Eliminierungen.
- `time` (Objekt) – Zeitmodus und Parameter (z. B. erlaubte Zeit, Bonus/Faults).
- `per_judge_formula` (String) – Ausdruck zur Berechnung der Richter-Einzelnoten.
- `aggregate_formula` (String) – Ausdruck für die Gesamtnote nach Aggregation, Zeit und Strafen.
- `ranking` (Objekt) – Sortierreihenfolge und Tie-Break-Kette.
- `output` (Objekt) – Rundung, Einheit und Anzeigeoptionen.

### Eingaben (`input`)
- `judges`
  - `min` / `max` – erlaubte Anzahl an Richtern (Validierung vor Berechnung).
  - `aggregation`
    - `method` – `mean`, `median` oder `weighted_mean` (Default: `mean`).
    - `drop_high` / `drop_low` – Anzahl höchster/tiefster Noten, die vor der Aggregation entfernt werden.
    - `weights` – Gewichtung pro Richter-ID für `weighted_mean`.
- `fields` – zusätzliche Eingabefelder außerhalb der Richterwerte (z. B. Zeit, Fehlerpunkte). Jeder Eintrag enthält `id`, `label`, `type` (`number`, `set`, `boolean`, `text`, `textarea`, `time`), optional `required` sowie Grenzen (`min`, `max`). Für `type: "set"` wird zusätzlich ein `options`-Array mit erlaubten Werten erwartet, `number` kann über `step` und `decimals` gesteuert werden. Textfelder (`text`) sind einzeilig, mehrzeilige Textfelder (`textarea`) können optional über `rows` in der Höhe angepasst werden. Die Felddaten stehen später im Kontext `fields.<id>` für Formeln und Validierungen bereit.
- `components` – pro Richter zu erfassende Bewertungskomponenten mit `id`, `label`, optional `min`, `max`, `step`, `weight`, `required`. Komponenten müssen eindeutige IDs besitzen; sonst schlägt die Validierung fehl. Ein einfaches Beispiel:
  ```json
  "components": [
    { "id": "C1", "label": "Trabverstärkungen", "min": 0, "max": 10, "step": 0.5, "weight": 1 },
    { "id": "IMP", "label": "Impression", "min": 0, "max": 10, "step": 0.5, "weight": 0.5 }
  ]
  ```
  Ältere Regeln mit `lessons` werden automatisch in dieses Komponenten-Format überführt, sobald sie geladen oder gespeichert werden.

### Strafen (`penalties`)
Jede Strafe ist ein Objekt mit folgenden Feldern:
- `id` (optional) & `label` – Kennung und Beschreibung.
- `when` – Ausdruck, der entscheidet, ob die Strafe angewendet wird. Fehlt `when`, wird die Strafe immer angewendet.
- `points` – Ausdruck, der die Strafpunkte berechnet. Wird ignoriert, wenn `eliminate` gesetzt ist.
- `eliminate` (bool) – markiert den Teilnehmer als eliminiert und beendet die Strafverarbeitung.

### Zeitkonfiguration (`time`)
- `mode` – `none`, `faults_from_time` (Überzeit erzeugt Strafpunkte) oder `score_bonus` (Unterzeit erzeugt Bonus).
- `allowed_s` – erlaubte Zeit in Sekunden.
- `fault_per_s` – Faktor für Strafpunkte bzw. Bonus pro Sekunde Differenz.
- `cap_s` – optionales Maximum für berücksichtigte Überzeit (nur `faults_from_time`).

### Formeln und Ausdrücke
Formeln sind Mini-Ausdrücke, die vom Ausdrucksparser ausgewertet werden. Kontextvariablen:
- `components` – Map der Richter-Komponenten (im Aggregat bei `aggregate_formula`).
- `fields` – zusätzliche Felddaten (z. B. Zeit).
- `aggregate.score` – Durchschnitt/Median der Richterwertungen.
- `penalties.total` und `penalties.applied` – bisherige Strafpunkte.
- `time.faults`, `time.bonus`, `time.seconds` – Ergebnis der Zeitberechnung.

Verfügbare Funktionen: `sum`, `mean`, `min`, `max`, `if`, `clamp`, `round`, `coalesce`, `weighted`, `contains` sowie kontextbasierte Funktionen aus `__functions` (derzeit keine Standardfunktionen). Booleans und Vergleichsoperatoren (`<`, `<=`, `>`, `>=`, `==`, `!=`) werden unterstützt. Arrays erhalten automatisch Methoden wie `.contains(x)` über die Punktnotation.

### Ranking
- `order` – `desc` für absteigend (höhere Werte sind besser) oder `asc` für aufsteigend (niedrigere Werte sind besser).
- `tiebreak_chain` – Liste von Kriterien:
  - `best_component:<ID>` – vergleicht eine einzelne Komponente.
  - `least_time` – bevorzugt geringere Zeitwerte.
  - `lowest_penalties` – bevorzugt weniger Strafpunkte.
  - `random_draw` / `run_off` – Zufallsauswahl bzw. manueller Stechen-Hinweis.

### Ausgabe (`output`)
- `rounding` – Anzahl Dezimalstellen der Endnote.
- `unit` – Einheit (z. B. `pts`, `Fehler`).
- `show_breakdown` – steuert die Anzeige der Einzelwertungen in der UI.

### Validierung & Snapshots
Die Engine prüft die Anzahl der Richter, Wertebereiche der Komponenten und vervollständigt fehlende Felder mit Defaults. Fehlerhafte Regeln (z. B. doppelte Komponenten ohne ID) werfen eine Exception. Für Ergebnissnapshots wird eine sortierte Version der Regel inkl. Hash gespeichert.

### Beispiel
```json
{
  "version": "1",
  "id": "dressage.generic.v1",
  "label": "Dressur v1",
  "input": {
    "judges": {
      "min": 1,
      "max": 3,
      "aggregation": { "method": "mean" }
    },
    "fields": [],
    "components": [
      { "id": "C1", "label": "Trab", "min": 0, "max": 10, "step": 0.5, "weight": 1 }
    ]
  },
  "penalties": [],
  "time": { "mode": "none" },
  "per_judge_formula": "weighted(components)",
  "aggregate_formula": "aggregate.score - penalties.total",
  "ranking": {
    "order": "desc",
    "tiebreak_chain": ["best_component:C1"]
  },
  "output": { "rounding": 2, "unit": "Punkte" }
}
```

---

## English

### Overview
The scoring rule system uses a JSON object to describe how judge inputs, auxiliary fields, penalties, and timing are combined into a final score. Whenever a rule is saved, missing fields are filled with defaults to keep the structure valid.

### Top-Level Structure
- `version` (string) – rule version for traceability.
- `id` & `label` (string) – internal identifier and human-readable name.
- `input` (object) – describes expected inputs (see below).
- `penalties` (array) – configurable penalties or eliminations.
- `time` (object) – timing mode and parameters (allowed time, fault/bonus rate, etc.).
- `per_judge_formula` (string) – expression for per-judge scores.
- `aggregate_formula` (string) – expression for the final total after aggregation, penalties, and time adjustments.
- `ranking` (object) – sorting direction and tie-break chain.
- `output` (object) – rounding precision, unit, and display preferences.

### Inputs (`input`)
- `judges`
  - `min` / `max` – allowed number of judges (validated before scoring).
  - `aggregation`
    - `method` – `mean`, `median`, or `weighted_mean` (default: `mean`).
    - `drop_high` / `drop_low` – count of highest/lowest scores to discard before aggregation.
    - `weights` – per-judge weights for `weighted_mean`.
- `fields` – additional inputs outside judge components (e.g., time, fault points). Each entry carries `id`, `label`, `type` (`number`, `set`, `boolean`, `text`, `textarea`, `time`), optional `required`, and numeric bounds (`min`, `max`). For `type: "set"` provide an `options` array of allowed values, while `number` may specify `step` and `decimals`. Single-line text inputs use `type: "text"`, while multi-line inputs use `type: "textarea"` and may optionally define `rows` to control their height. Collected values are later exposed to formulas and validators via `fields.<id>`.
- `components` – judge-entered components with `id`, `label`, optional `min`, `max`, `step`, `weight`, `required`. Every component must have a unique `id`; otherwise validation fails. Example:
  ```json
  "components": [
    { "id": "C1", "label": "Trot extensions", "min": 0, "max": 10, "step": 0.5, "weight": 1 },
    { "id": "IMP", "label": "Impression", "min": 0, "max": 10, "step": 0.5, "weight": 0.5 }
  ]
  ```
  Legacy rules that still contain a `lessons` array are migrated to this component structure during load/save operations.

### Penalties (`penalties`)
Each penalty entry contains:
- `id` (optional) & `label` – identifier and description.
- `when` – expression deciding whether the penalty applies. If omitted, the penalty always applies.
- `points` – expression producing penalty points. Ignored when `eliminate` is set.
- `eliminate` (bool) – flags the competitor as eliminated and stops penalty processing.

### Time Configuration (`time`)
- `mode` – `none`, `faults_from_time` (overtime adds faults), or `score_bonus` (undertime adds bonus).
- `allowed_s` – allowed time in seconds.
- `fault_per_s` – rate for faults/bonus per second difference.
- `cap_s` – optional cap for overtime considered in `faults_from_time`.

### Formulas & Expressions
Expressions are parsed and executed by the expression engine. Available context variables include:
- `components` – component scores (per judge or aggregated).
- `fields` – additional field inputs.
- `aggregate.score` – judge aggregation result.
- `penalties.total` / `penalties.applied` – accumulated penalties.
- `time.faults`, `time.bonus`, `time.seconds` – computed timing data.

Supported functions: `sum`, `mean`, `min`, `max`, `if`, `clamp`, `round`, `coalesce`, `weighted`, `contains`, plus any callable registered via `__functions`. Logical and comparison operators (`<`, `<=`, `>`, `>=`, `==`, `!=`) are available. Arrays offer helper access like `.contains(x)` through dot notation.

### Ranking
- `order` – `desc` for higher-is-better or `asc` for lower-is-better rankings.
- `tiebreak_chain` – list of criteria applied in order:
  - `best_component:<ID>` – compare a single component score.
  - `least_time` – prefer smaller time values.
  - `lowest_penalties` – prefer lower total penalties.
  - `random_draw` / `run_off` – random seed or manual runoff hint.

### Output (`output`)
- `rounding` – decimal places for the rounded total.
- `unit` – measurement unit (e.g., `pts`, `faults`).
- `show_breakdown` – controls whether judge breakdowns appear in the UI.

### Validation & Snapshots
The engine validates judge counts, component ranges, and fills missing fields with defaults. Rules without component IDs trigger exceptions. For published results, a normalized rule snapshot including a SHA-256 hash is stored.

### Example
```json
{
  "version": "1",
  "id": "dressage.generic.v1",
  "label": "Dressage v1",
  "input": {
    "judges": {
      "min": 1,
      "max": 3,
      "aggregation": { "method": "mean" }
    },
    "fields": [],
    "components": [
      { "id": "C1", "label": "Trot", "min": 0, "max": 10, "step": 0.5, "weight": 1 }
    ]
  },
  "penalties": [],
  "time": { "mode": "none" },
  "per_judge_formula": "weighted(components)",
  "aggregate_formula": "aggregate.score - penalties.total",
  "ranking": {
    "order": "desc",
    "tiebreak_chain": ["best_component:C1"]
  },
  "output": { "rounding": 2, "unit": "points" }
}
```
