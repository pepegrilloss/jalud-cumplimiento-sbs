<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$path = $argv[1] ?? dirname(__DIR__).'/resources/templates/rop/modelo-rop.xlsx';
$sheet = IOFactory::load($path)->getActiveSheet();

for ($row = 1; $row <= $sheet->getHighestRow(); $row++) {
    $values = [];

    foreach ($sheet->getRowIterator($row, $row)->current()->getCellIterator('A', $sheet->getHighestColumn()) as $cell) {
        $value = trim((string) $cell->getFormattedValue());

        if ($value !== '') {
            $values[] = $cell->getCoordinate().'='.preg_replace('/\s+/u', ' ', $value);
        }
    }

    if ($values !== []) {
        echo $row.'|'.implode(' || ', $values).PHP_EOL;
    }
}
