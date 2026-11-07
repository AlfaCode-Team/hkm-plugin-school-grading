<?php

declare(strict_types=1);

/**
 * Test bootstrap.
 *
 * Neither this plugin nor school-classes has a vendor/ yet, so both PSR-4 maps
 * are registered by hand. `school.grading` legitimately reads
 * `Plugins\SchoolClasses\API\…` — the API namespace is the published seam
 * between the two modules; anything under its Domain/ would be a scope
 * violation.
 */
spl_autoload_register(static function (string $class): void {
    $siblings = dirname(__DIR__, 2);
    foreach ([
        'Plugins\\SchoolGrading\\Tests\\' => __DIR__ . '/',
        'Plugins\\SchoolGrading\\'        => dirname(__DIR__) . '/',
        'Plugins\\SchoolClasses\\'        => $siblings . '/hkm-plugin-school-classes/',
    ] as $prefix => $base) {
        if (! str_starts_with($class, $prefix)) {
            continue;
        }
        $file = $base . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});
