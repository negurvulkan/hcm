# Scoring-Regel-System / Scoring Rule System

## Deutsch

# Übersicht: Scoring-Schema-Definition

Das **Scoring-Schema** beschreibt die gesamte Bewertungslogik einer Prüfung
in einem standardisierten JSON-Format. Es ist modular aufgebaut und kann
Dressur, Western, Springen, Vielseitigkeit oder andere Reitdisziplinen abbilden.

---

## Top-Level-Struktur

| Feld          | Typ                                              | Beschreibung                                             |
| ------------- | ------------------------------------------------ | -------------------------------------------------------- |
| `id`          | String                                           | Eindeutiger Schlüssel (z. B. `dressage.a.fn.v1`)         |
| `label`       | String                                           | Menschlich lesbarer Name der Regel                       |
| `description` | String                                           | (Optional) Langtext zur Beschreibung                     |
| `mode`        | Enum: `scale`, `adjustment`, `penalty`, `hybrid` | Bewertungslogik auf globaler Ebene                       |
| `judges`      | Objekt                                           | Definition der Richter (Anzahl, Gewichtung, Aggregation) |
| `scoring`     | Objekt                                           | Kern der Bewertungslogik: Komponenten, Strafen, Formeln  |
| `metadata`    | Objekt                                           | Disziplin- und Organisationsinfos (z. B. FN, AQHA etc.)  |

---

## Bewertungsmodi (`mode`)

| Modus        | Beschreibung                                  | Beispiel                  |
| ------------ | --------------------------------------------- | ------------------------- |
| `scale`      | Bewertung jeder Lektion mit Note (z. B. 0–10) | Dressur, Western Dressage |
| `adjustment` | Startwert + / − Anpassungen (Maneuvers)       | Western Riding            |
| `penalty`    | Fehler-/Zeitpunktesystem                      | Springen, Eventing        |
| `hybrid`     | Kombination (z. B. Kür: Note + Strafen)       | Dressur Kür, Trail        |

---

## Richterkonfiguration (`judges`)

```json
"judges": {
  "min": 1,
  "max": 3,
  "positions": ["C", "E", "M"],
  "aggregationMethod": "mean",
  "weights": { "C": 1.0, "E": 0.9, "M": 0.9 },
  "dropHigh": 0,
  "dropLow": 0
}
```

### Optionen

* `aggregationMethod`: `mean`, `median`, `weightedMean`, `sum`, `custom`
* `dropHigh` / `dropLow`: entfernt Ausreißer vor Aggregation
* `customAggregation`: Script- oder Ausdruck, wenn nötig
* `weights`: Gewichtung pro Richter oder Position
* `positions`: für FEI- oder FN-konforme Wertungen

---

## Bewertungslogik (`scoring`)

### 1. Components – Lektionen, Manöver, Sammelnoten

Jede zu bewertende Einheit ist ein **Component**.

```json
{
  "id": "L1",
  "label": "Einreiten, Halten, Grüßen",
  "scoreType": "scale",
  "min": 0, "max": 10, "step": 0.5,
  "weight": 1,
  "group": "lesson",
  "section": "Einfahrt",
  "code": "L1"
}
```

| Feld                                | Beschreibung                                        |
| ----------------------------------- | --------------------------------------------------- |
| `id`                                | Eindeutige Kennung                                  |
| `label`                             | Anzeigetext                                         |
| `scoreType`                         | Berechnungs-/Eingabetyp (siehe unten)               |
| `min` / `max` / `step` / `decimals` | Zahlenbereich & Schrittweite                        |
| `weight` / `coefficient`            | Gewichtung in der Endnote                           |
| `group`                             | `"lesson"`, `"collective"`, `"maneuver"`, `"other"` |
| `section`                           | Visuelle Gruppierung (z. B. Trab, Galopp)           |
| `code`                              | Referenz aus Aufgabenheft                           |
| `ui`                                | Anzeige-Optionen (`hint`, `visible`, `readonly`)    |

---

### 2. scoreType (pro Komponente)

| scoreType | Beschreibung                            | Typische UI                 |
| --------- | --------------------------------------- | --------------------------- |
| `scale`   | Klassische 0–10 Note                    | Button-Group (Radio Toggle) |
| `delta`   | Startwert ± Anpassung (z. B. −1.5…+1.5) | Neg./Pos. Buttons           |
| `count`   | Häufigkeit × Faktor                     | Spinner/Counter             |
| `time`    | Zeitwert (mm:ss → Punkte)               | Eingabefeld + Stoppuhr      |
| `binary`  | Ja/Nein = 0/10                          | Switch/Checkbox             |
| `custom`  | Berechnet aus Ausdruck (`calcExpr`)     | Read-Only                   |

---

### 3. Startwert & Adjustments

```json
"startValue": 70,
"adjustments": [
  { "id": "M1", "label": "Maneuver 1", "min": -1.5, "max": 1.5, "step": 0.5 }
]
```

> Wird genutzt, wenn `mode = adjustment` oder `hybrid`.
> Beispiel: Western Riding mit Startwert 70 und ± Manöverwertungen.

---

### 4. Penalties & Time

#### Penalties

```json
"penalties": [
  { "id": "ERR1", "label": "Fehler in der Aufgabe", "type": "deduction", "value": 2 },
  { "id": "ELIM", "label": "Eliminierung", "type": "elimination", "eliminate": true }
]
```

#### Zeitsteuerung

```json
"time": {
  "mode": "faults_from_time",
  "allowedSeconds": 300,
  "faultPerSecond": 0.2
}
```

> Modus:
>
> * `faults_from_time` = Überzeit → Strafpunkte
> * `score_bonus` = Unterzeit → Bonus
> * `elimination_from_time` = Disqualifikation

---

### 5. Formeln

| Ebene         | Feld               | Beispiel                              |
| ------------- | ------------------ | ------------------------------------- |
| Pro Richter   | `perJudgeFormula`  | `"weighted(components)"`              |
| Gesamtwertung | `aggregateFormula` | `"aggregate.score - penalties.total"` |

Optional:
`calcExpr` / `toPointsExpr` innerhalb einzelner Components (z. B. `time` oder `custom`).

---

### 6. Rundung & Ausgabe

```json
"rounding": {
  "decimals": 2,
  "unit": "%",
  "normalizeToPercent": true
}
```

* **normalizeToPercent**: rechnet automatisch auf 0–100 % bezogen auf theoretisches Maximum.
* Einheit: `"%"`, `"Punkte"`, `"Sek."` etc.

---

### 7. Tiebreakers

```json
"tiebreakers": [
  { "type": "highestComponent", "componentId": "C3" },
  { "type": "lowestPenalties" },
  { "type": "random" }
]
```

| Typ                                    | Beschreibung                             |
| -------------------------------------- | ---------------------------------------- |
| `highestComponent` / `lowestComponent` | Direktvergleich bestimmter Komponente    |
| `bestOfGroup`                          | Mittelwert einer Gruppe (z. B. „lesson“) |
| `lowestPenalties`                      | Geringste Strafpunkte                    |
| `fastestTime`                          | Kürzeste Zeit                            |
| `runOff` / `random`                    | Stechen / Zufallsauswahl                 |
| `custom`                               | Ausdruck definierbar                     |

---

## 🏁 Workflow

1. **Regel anlegen** (JSON-Objekt nach Schema)
2. **Validierung** → Schema-Check + Ausdrucksprüfung
3. **UI generieren** (Widgets je scoreType)
4. **Eingabe der Werte** durch Richter:innen
5. **Formelauswertung** (`perJudgeFormula` → `aggregateFormula`)
6. **Normalisierung** & Rundung
7. **Ranking** nach `tiebreakers`

---

## 🧾 Beispiel (FN A-Dressur)

```json
{
  "id": "dressage.a.fn.v1",
  "label": "FN A-Dressur",
  "mode": "scale",
  "judges": { "min": 1, "max": 3, "aggregationMethod": "mean" },
  "scoring": {
    "components": [
      { "id": "L1", "label": "Einreiten, Halten, Grüßen", "scoreType": "scale", "min": 0, "max": 10, "step": 0.5, "weight": 1, "group": "lesson" },
      { "id": "C3", "label": "Sitz & Einwirkung", "scoreType": "scale", "min": 0, "max": 10, "step": 0.5, "weight": 2, "group": "collective" }
    ],
    "perJudgeFormula": "weighted(components)",
    "aggregateFormula": "aggregate.score - penalties.total",
    "rounding": { "decimals": 2, "unit": "%", "normalizeToPercent": true }
  },
  "metadata": { "discipline": "Dressur", "level": "A", "organization": "FN" }
}
