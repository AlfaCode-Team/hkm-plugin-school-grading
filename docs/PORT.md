# Port status — `school.grading`

**Partially ported.** The period model is done and verified. Marks storage is not
started.

## Ported, and proven identical to the legacy system

The **`Period` enum** — both academic calendars, 9 trimester periods for
Maternelle and Primaire and 6 session periods above them — from
`public/teacher/period_helpers.php`.

Verified by running the legacy functions and this port side by side:

```
$ php tools/diff-against-legacy.php /path/to/ElimuSystem
class levels compared : 152
periods               : 15
RESULT: IDENTICAL to legacy
```

Suite: **23 tests, 49 assertions**.

### Why the enum exists rather than a string

A period's **key** and its **`cotation` column** are deliberately different
strings — `exam_t1` maps to `t1exam` — and the legacy `get_period_max.php`
interpolates that column name **straight into its SQL**. `cotationColumn()` is
now the single naming authority for that mapping, with a test asserting every
column it can return is a bare identifier (`/^[a-z0-9_]+$/`). The enum is both
the naming authority and the injection guard.

`tryFromKeyForSystem()` returns `null` for a key belonging to the other calendar,
preserving the legacy refusal (HTTP 422, *"Période invalide pour ce niveau de
classe"*) rather than silently reading the wrong column.

Which calendar a class uses is **not** decided here. It comes from
`school.classes`' `gradingSystemFor()`, which reads the catalogue instead of
regexing the class's display name.

## Not ported

| | |
|---|---|
| Legacy tables | `cotation`, `cotation_maternelle`, `student_scores`, `period_scores` |
| Legacy source | `teacher/encode_{maternelle,primary,secondary}_scores.php`, `ponderation` |

`SchoolGradingContract` is still an **empty interface** and
`Provider::register()` binds nothing, so resolving the contract from a module
container throws. Nothing outside this plugin can consume it yet.

Those four tables have **no DDL anywhere** in the legacy tree and wait on the
schema reconstruction (PORT-PLAN.md §8). Mark encoding is the single most
correctness-sensitive use case in the system — a wrong maximum silently changes
every bulletin — so it is gated behind golden outputs captured from production
(decision D8), not behind a code review.

## The plan this follows

`PORT-PLAN.md` and `PLUGIN-PLAN.md` in the **ElimuSystem** repository. Not public.
