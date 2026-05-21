<?php

namespace App\Exports;

use App\Models\Student;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Clean Learning Assessment Excel template: header row, answer key row, student rows.
 * Columns A + Item 1 … Item N only — no formulas, no Score/%age, no summaries, no instructions.
 */
class LearningAssessmentTemplateExport implements FromArray, WithEvents, WithColumnWidths, WithTitle
{
    private const DEFAULT_SHEET_TITLE = 'Learning Assessment';

    private readonly string $sheetTitle;

    /**
     * @param  Collection<int, Student>  $students
     */
    public function __construct(
        private readonly Collection $students,
        private readonly int $totalItems = 50,
        string $sheetTitle = self::DEFAULT_SHEET_TITLE,
    ) {
        $this->sheetTitle = self::sanitizeSheetTitle($sheetTitle);
    }

    /**
     * Excel worksheet titles: max 31 chars; cannot contain \ / ? * : [ ]
     */
    public static function sanitizeSheetTitle(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return self::DEFAULT_SHEET_TITLE;
        }
        $stripped = preg_replace('/[\\\\\\/:\\?\\*\\[\\]]/u', '', $name);
        $name = trim(is_string($stripped) ? $stripped : $name);
        if ($name === '') {
            return self::DEFAULT_SHEET_TITLE;
        }

        return function_exists('mb_substr')
            ? mb_substr($name, 0, 31)
            : substr($name, 0, 31);
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }

    public function array(): array
    {
        $header = array_merge(
            ['Student ID/Name'],
            array_map(fn ($i) => "Item {$i}", range(1, $this->totalItems)),
        );
        $answerKey = array_merge(['Answer Key'], array_fill(0, $this->totalItems, ''));

        $rows = [$header, $answerKey];

        if ($this->students->isEmpty()) {
            return $rows;
        }

        foreach ($this->students->values() as $idx => $student) {
            $name = trim(($student->last_name ?? '') . ', ' . ($student->first_name ?? '') . ' ' . ($student->middle_name ?? ''));
            $label = $name !== '' ? $name : sprintf('Student_%02d', $idx + 1);
            $rows[] = array_merge([$label], array_fill(0, $this->totalItems, ''));
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        $widths = ['A' => 28];
        for ($i = 2; $i <= $this->totalItems + 1; $i++) {
            $widths[Coordinate::stringFromColumnIndex($i)] = 8;
        }

        return $widths;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $studentCount = $this->students->count();
                $firstStudentRow = 3;
                $lastStudentRow = $studentCount > 0 ? $firstStudentRow + $studentCount - 1 : 2;

                $lastColIndex = 1 + $this->totalItems;
                $lastColLetter = Coordinate::stringFromColumnIndex($lastColIndex);
                $lastItemCol = Coordinate::stringFromColumnIndex($this->totalItems + 1);

                $range = "A1:{$lastColLetter}{$lastStudentRow}";

                $sheet->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->getStyle("A1:{$lastColLetter}1")->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                $sheet->getStyle("A2:{$lastColLetter}2")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFFFF2CC'],
                    ],
                ]);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle("B2:{$lastItemCol}2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                if ($studentCount > 0) {
                    $sheet->getStyle("A3:A{$lastStudentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle("B3:{$lastItemCol}{$lastStudentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                $sheet->getStyle($range)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(20);
                $sheet->getRowDimension(2)->setRowHeight(20);

                if ($studentCount > 0) {
                    $sheet->freezePane('B3');
                }
            },
        ];
    }
}
