<?php

declare(strict_types=1);

/**
 * Single source of truth for Rule D-65 (Bounded Page & Progressive Disclosure) thresholds.
 * Adjust limits here once — all bounded grids, tables, and filters read from this file.
 */
return [
    'bounded' => [
        'cards_per_page_desktop' => 12,
        'cards_per_page_mobile' => 6,
        'table_rows_initial' => 10,
        'table_rows_max' => 25,
        'max_tables_inside_card' => 0,
        'max_search_bars_per_page' => 1,
        'max_open_sections' => 4,
        'max_compare_programmes' => 3,
    ],
];
