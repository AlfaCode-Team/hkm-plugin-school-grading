<?php
declare(strict_types=1);
/*
 * Differential check: the ported Period enum vs the legacy
 * public/teacher/period_helpers.php. Needs the legacy tree.
 *
 *   php tools/diff-against-legacy.php [/path/to/ElimuSystem]
 */
$legacyRoot = $argv[1] ?? '/Users/user/Documents/HKMCODE/PROJECTS/ElimuSystem';
$legacy     = $legacyRoot . '/public';
if (! is_file($legacy . '/teacher/period_helpers.php')) {
    fwrite(STDERR, "SKIP: legacy tree not found at {$legacy}\n");
    exit(0);
}

$_SERVER['SCRIPT_FILENAME'] = __FILE__;
require $legacy . '/class_catalog.php';
require $legacy . '/teacher/period_helpers.php';

$classes = dirname(__DIR__) . '/../hkm-plugin-school-classes';
require $classes . '/API/Enums/GradingSystem.php';
require $classes . '/Domain/Catalog/ClassTrack.php';
require $classes . '/Domain/Catalog/ClassCatalog.php';
require dirname(__DIR__) . '/API/Enums/Period.php';

use Plugins\SchoolClasses\Domain\Catalog\ClassCatalog;
use Plugins\SchoolGrading\API\Enums\Period;

$fail = 0;
$check = function (string $what, $a, $b) use (&$fail): void {
    if ($a === $b) { return; }
    $fail++;
    echo "MISMATCH  $what\n   legacy: " . json_encode($a) . "\n   ported: " . json_encode($b) . "\n";
};

$sample = array_merge(elimu_class_order(), [
    '1ère année Electricité', '1ère année Méc. Auto', 'Unknown Class', '', 'MATERNELLE',
]);

foreach ($sample as $cl) {
    $system = ClassCatalog::gradingSystemFor((string) $cl);

    // 1 — the period key => cotation column map
    $portedMap = [];
    foreach (Period::forSystem($system) as $p) {
        $portedMap[$p->value] = $p->cotationColumn();
    }
    $check("getPeriodMap('$cl')", getPeriodMap((string) $cl), $portedMap);

    // 2 — the exam period keys
    $check("getExamPeriods('$cl')", getExamPeriods((string) $cl),
        array_map(static fn(Period $p): string => $p->value, Period::examsForSystem($system)));

    // 3 — isExamPeriod for EVERY key in both calendars, including the wrong-calendar ones
    foreach (Period::cases() as $p) {
        $check("isExamPeriod('{$p->value}', '$cl')",
            isExamPeriod($p->value, (string) $cl),
            Period::tryFromKeyForSystem($p->value, $system)?->isExam() ?? false);
    }

    // 4 — a key from the other calendar must not resolve
    foreach (Period::cases() as $p) {
        $legacyKnows = isset(getPeriodMap((string) $cl)[$p->value]);
        $check("key '{$p->value}' valid for '$cl'",
            $legacyKnows, Period::tryFromKeyForSystem($p->value, $system) !== null);
    }
}

echo "\nclass levels compared : " . count($sample) . "\n";
echo "periods               : " . count(Period::cases()) . "\n";
echo $fail === 0 ? "RESULT: IDENTICAL to legacy\n" : "RESULT: $fail MISMATCH(ES)\n";
exit($fail === 0 ? 0 : 1);
