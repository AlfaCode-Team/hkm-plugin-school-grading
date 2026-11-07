<?php

declare(strict_types=1);

namespace Plugins\SchoolGrading\API\Enums;

use Plugins\SchoolClasses\API\Enums\GradingSystem;

/**
 * A marking period, and the `cotation` column that stores its maximum mark.
 *
 * PUBLISHED: `school.bulletin` and the teacher UI both name periods.
 *
 * Faithful port of `getPeriodMap()` / `getExamPeriods()` / `isExamPeriod()` in
 * `public/teacher/period_helpers.php`.
 *
 * ## The key and the column are NOT the same string
 *
 * This is the trap the legacy map encodes, and the reason this is an enum:
 *
 *     period key   exam_t1   ->   cotation column   t1exam
 *     period key   exam_ps   ->   cotation column   psexam
 *
 * The keys travel over HTTP (`?period=exam_t1`); the columns are physical
 * `cotation` columns. Renaming either side independently silently breaks mark
 * entry — `get_period_max.php` interpolates the column straight into its SQL.
 *
 * Nine trimester periods, six session periods, and no period belongs to both
 * calendars.
 */
enum Period: string
{
    // Trimester calendar — Maternelle + École Primaire.
    case T1P1    = 't1p1';
    case T1P2    = 't1p2';
    case ExamT1  = 'exam_t1';
    case T2P1    = 't2p1';
    case T2P2    = 't2p2';
    case ExamT2  = 'exam_t2';
    case T3P1    = 't3p1';
    case T3P2    = 't3p2';
    case ExamT3  = 'exam_t3';

    // Session calendar — Éducation de Base and every Humanités track.
    case PS1     = 'ps1';
    case PS2     = 'ps2';
    case ExamPS  = 'exam_ps';
    case DS1     = 'ds1';
    case DS2     = 'ds2';
    case ExamDS  = 'exam_ds';

    /** The `cotation` column holding this period's maximum mark. */
    public function cotationColumn(): string
    {
        return match ($this) {
            self::ExamT1 => 't1exam',
            self::ExamT2 => 't2exam',
            self::ExamT3 => 't3exam',
            self::ExamPS => 'psexam',
            self::ExamDS => 'dsexam',
            // Every other period's key and column are the same string.
            default      => $this->value,
        };
    }

    public function system(): GradingSystem
    {
        return match ($this) {
            self::T1P1, self::T1P2, self::ExamT1,
            self::T2P1, self::T2P2, self::ExamT2,
            self::T3P1, self::T3P2, self::ExamT3 => GradingSystem::Trimester,
            default                              => GradingSystem::Session,
        };
    }

    public function isExam(): bool
    {
        return in_array($this, [
            self::ExamT1, self::ExamT2, self::ExamT3, self::ExamPS, self::ExamDS,
        ], true);
    }

    /** @return list<self> the periods of one calendar, in order */
    public static function forSystem(GradingSystem $system): array
    {
        return $system === GradingSystem::Trimester
            ? [self::T1P1, self::T1P2, self::ExamT1,
               self::T2P1, self::T2P2, self::ExamT2,
               self::T3P1, self::T3P2, self::ExamT3]
            : [self::PS1, self::PS2, self::ExamPS,
               self::DS1, self::DS2, self::ExamDS];
    }

    /** @return list<self> */
    public static function examsForSystem(GradingSystem $system): array
    {
        return array_values(array_filter(
            self::forSystem($system),
            static fn(self $p): bool => $p->isExam(),
        ));
    }

    /**
     * Resolve a period key, REJECTING one that belongs to the other calendar.
     *
     * The legacy code did this with `isset($periodMap[$period])` and answered
     * HTTP 422 "Période invalide pour ce niveau de classe". Returning null here
     * preserves that: a trimester key on a secondary class is not a period, not
     * merely an unknown one.
     */
    public static function tryFromKeyForSystem(string $key, GradingSystem $system): ?self
    {
        $period = self::tryFrom($key);

        return $period !== null && $period->system() === $system ? $period : null;
    }
}
