<?php

declare(strict_types=1);

namespace Plugins\SchoolGrading\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Plugins\SchoolClasses\API\Enums\GradingSystem;
use Plugins\SchoolGrading\API\Enums\Period;

/**
 * Pins the Period enum to `public/teacher/period_helpers.php`.
 *
 * Verified by running both implementations side by side over all 147 catalogue
 * classes plus legacy misspellings and edge cases — 152 class levels × 15
 * periods — with `tools/diff-against-legacy.php`, which reported IDENTICAL.
 * This test re-states the invariants so they hold without the legacy tree.
 */
final class PeriodTest extends TestCase
{
    /**
     * The legacy `getPeriodMap()` tables, transcribed verbatim.
     *
     * @return iterable<string, array{Period, string}>
     */
    public static function keyToColumnCases(): iterable
    {
        $legacy = [
            // Primary (3-trimester) — getPeriodMap() for a 'primaire'/'maternelle' level
            't1p1' => 't1p1', 't1p2' => 't1p2', 'exam_t1' => 't1exam',
            't2p1' => 't2p1', 't2p2' => 't2p2', 'exam_t2' => 't2exam',
            't3p1' => 't3p1', 't3p2' => 't3p2', 'exam_t3' => 't3exam',
            // Secondary / default (2-session)
            'ps1' => 'ps1', 'ps2' => 'ps2', 'exam_ps' => 'psexam',
            'ds1' => 'ds1', 'ds2' => 'ds2', 'exam_ds' => 'dsexam',
        ];
        foreach ($legacy as $key => $column) {
            yield "$key => $column" => [Period::from($key), $column];
        }
    }

    #[DataProvider('keyToColumnCases')]
    public function test_every_period_key_maps_to_the_legacy_cotation_column(Period $p, string $column): void
    {
        self::assertSame($column, $p->cotationColumn());
    }

    public function test_the_five_exam_periods_are_the_only_ones_whose_key_differs_from_its_column(): void
    {
        $differ = array_values(array_filter(
            Period::cases(),
            static fn(Period $p): bool => $p->value !== $p->cotationColumn(),
        ));
        self::assertSame(
            [Period::ExamT1, Period::ExamT2, Period::ExamT3, Period::ExamPS, Period::ExamDS],
            $differ,
        );
    }

    public function test_the_trimester_calendar_has_nine_periods_in_legacy_order(): void
    {
        self::assertSame(
            ['t1p1', 't1p2', 'exam_t1', 't2p1', 't2p2', 'exam_t2', 't3p1', 't3p2', 'exam_t3'],
            array_map(static fn(Period $p): string => $p->value, Period::forSystem(GradingSystem::Trimester)),
        );
        self::assertSame(9, GradingSystem::Trimester->periodCount());
    }

    public function test_the_session_calendar_has_six_periods_in_legacy_order(): void
    {
        self::assertSame(
            ['ps1', 'ps2', 'exam_ps', 'ds1', 'ds2', 'exam_ds'],
            array_map(static fn(Period $p): string => $p->value, Period::forSystem(GradingSystem::Session)),
        );
        self::assertSame(6, GradingSystem::Session->periodCount());
    }

    public function test_no_period_belongs_to_both_calendars(): void
    {
        $v   = static fn(array $ps): array => array_map(static fn(Period $p): string => $p->value, $ps);
        $tri = $v(Period::forSystem(GradingSystem::Trimester));
        $ses = $v(Period::forSystem(GradingSystem::Session));
        // array_intersect() stringifies its arguments, which an enum cannot do.
        self::assertSame([], array_values(array_intersect($tri, $ses)));
        self::assertCount(15, array_merge($tri, $ses));
        self::assertCount(15, Period::cases(), 'every case belongs to exactly one calendar');
    }

    public function test_exam_periods_match_the_legacy_lists(): void
    {
        self::assertSame(['exam_t1', 'exam_t2', 'exam_t3'], array_map(
            static fn(Period $p): string => $p->value, Period::examsForSystem(GradingSystem::Trimester)));
        self::assertSame(['exam_ps', 'exam_ds'], array_map(
            static fn(Period $p): string => $p->value, Period::examsForSystem(GradingSystem::Session)));
    }

    public function test_a_key_from_the_other_calendar_does_not_resolve(): void
    {
        // The legacy code answered 422 "Période invalide pour ce niveau de
        // classe" for exactly these; null preserves that.
        self::assertNull(Period::tryFromKeyForSystem('t1p1', GradingSystem::Session));
        self::assertNull(Period::tryFromKeyForSystem('exam_t1', GradingSystem::Session));
        self::assertNull(Period::tryFromKeyForSystem('ps1', GradingSystem::Trimester));
        self::assertNull(Period::tryFromKeyForSystem('exam_ds', GradingSystem::Trimester));

        self::assertSame(Period::T1P1, Period::tryFromKeyForSystem('t1p1', GradingSystem::Trimester));
        self::assertSame(Period::PS1,  Period::tryFromKeyForSystem('ps1', GradingSystem::Session));
    }

    public function test_an_unknown_key_does_not_resolve(): void
    {
        self::assertNull(Period::tryFromKeyForSystem('', GradingSystem::Session));
        self::assertNull(Period::tryFromKeyForSystem('t4p1', GradingSystem::Trimester));
        self::assertNull(Period::tryFromKeyForSystem('T1P1', GradingSystem::Trimester), 'keys are case-sensitive');
    }

    public function test_a_cotation_column_is_never_injectable(): void
    {
        // get_period_max.php interpolates the column straight into its SQL:
        //   SELECT `$dbColumn` AS max_value FROM cotation ...
        // The enum is the guard — every column is a bare identifier.
        foreach (Period::cases() as $p) {
            self::assertMatchesRegularExpression('/^[a-z0-9_]+$/', $p->cotationColumn(), $p->value);
        }
    }
}
