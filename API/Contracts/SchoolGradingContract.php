<?php

declare(strict_types=1);

namespace Plugins\SchoolGrading\API\Contracts;

/**
 * Published contract for `school.grading`.
 *
 * Marks and maxima. Owns the TWO grading systems (3 trimesters vs 2 sessions) resolved through the catalog, never by regex.
 *
 * Legacy source : teacher/period_helpers.php, teacher/encode_{maternelle,primary,secondary}_scores.php, cotation, student_scores, ponderation
 * Legacy tables : cotation, cotation_maternelle, student_scores, period_scores
 *
 * Consumers declare `"requires": ["school.grading"]` and resolve THIS interface.
 * Nothing outside this plugin may touch its internals — those are bound with
 * `bindInternal()` and throw `ScopeViolationException` cross-scope.
 *
 * @see docs/PORT.md in this repository for what still has to be ported.
 */
interface SchoolGradingContract
{
    // Methods are added as each legacy use case is ported. Keep this surface
    // minimal: one method per use case the OUTSIDE world needs, never a
    // pass-through to a repository.
}
