# hkm-plugin-school-grading

> Solves **`school.grading`** · namespace **`Plugins\SchoolGrading\`** · on-demand GDA module
> Family: Elimu / school domain

Marks and maxima. Owns the TWO grading systems (3 trimesters vs 2 sessions) resolved through the catalog, never by regex.

## Status

> Full detail: [`docs/PORT.md`](docs/PORT.md).

**The period model is ported and verified. Marks and maxima are not.**

| Piece | State |
|---|---|
| `Period` — the two calendars, 15 periods, and the `cotation` column each maps to | **Done.** Pure; no database |
| `cotation`, `cotation_maternelle`, `student_scores`, ponderation | Not started — those tables have **no DDL** in the legacy tree and wait on the schema reconstruction (`PORT-PLAN.md` §8) |

### The trap this enum exists to close

A period's **key** and its **storage column** are different strings:

```
period key  exam_t1  ->  cotation column  t1exam
period key  exam_ps  ->  cotation column  psexam
```

Keys travel over HTTP (`?period=exam_t1`); columns are physical `cotation`
columns. `get_period_max.php` interpolates the column **straight into its SQL**:

```php
SELECT `$dbColumn` AS max_value FROM cotation WHERE class_level = :cl AND subject_id = :subid
```

So the enum is both the naming authority and the injection guard — a test
asserts every `cotationColumn()` is a bare `[a-z0-9_]+` identifier.

`tryFromKeyForSystem()` also preserves a rule that is easy to lose: a trimester
key on a secondary class is **not a period**, which is why the legacy endpoint
answered 422 *"Période invalide pour ce niveau de classe"* rather than falling
back to a default.

### How it was verified

The legacy app cannot be run, but `teacher/period_helpers.php` is pure, so both
implementations were run side by side over all 147 catalogue classes plus legacy
misspellings and edge cases — **152 class levels × 15 periods** — and reported
IDENTICAL.

```bash
php tools/diff-against-legacy.php   # needs the legacy tree
vendor/bin/phpunit                  # 23 tests, 49 assertions — runs without it
```

Which calendar a class uses is **not** decided here: it comes from
`school.classes`' `gradingSystemFor()`, which reads the catalogue instead of
regex-matching a display name. See that plugin's README.

## Where it comes from

| | |
|---|---|
| Legacy source | `teacher/period_helpers.php, teacher/encode_{maternelle,primary,secondary}_scores.php, cotation, student_scores, ponderation` |
| Legacy tables | `cotation, cotation_maternelle, student_scores, period_scores` |

The port plan, the reconstructed domain model and the per-plugin recipe live in
the **ElimuSystem** repository: `PORT-PLAN.md` and `PLUGIN-PLAN.md`.

## Requires

- `database.management`
- `school.classes`
- `school.curriculum`
- `school.people`

Declared as module **domains**, never port or contract class names. An unknown
domain fails the boot.

## Using it

```jsonc
// the consumer's module.json / proj.json
{ "requires": ["school.grading"] }
```

```php
$svc = $container->make(\Plugins\SchoolGrading\API\Contracts\SchoolGradingContract::class);
```

## Installing

```bash
hkm plugins enable SchoolGrading <project>
```
