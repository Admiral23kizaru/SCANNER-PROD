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

class GmrcTemplateExport implements FromArray, WithEvents, WithColumnWidths, WithTitle
{
    /**
     * @param \Illuminate\Support\Collection<int, \App\Models\Student> $students
     */
    public function __construct(
        private readonly Collection $students,
        private readonly int $totalItems = 50,
    ) {}

    public function title(): string
    {
        return 'GMRC';
    }

    public function array(): array
    {
        $header = array_merge(
            ['Student ID/Name'],
            array_map(fn ($i) => "item {$i}", range(1, $this->totalItems)),
            ['Score', '%age']
        );
        $answerKey = array_merge(['Answer Key'], array_fill(0, $this->totalItems + 2, ''));

        $rows = [$header, $answerKey];

        if ($this->students->isEmpty()) {
            // Keep the sheet usable as a clean template even without loaded students.
            for ($i = 1; $i <= 30; $i++) {
                $rows[] = array_merge([sprintf('Student_%02d', $i)], array_fill(0, $this->totalItems + 2, ''));
            }
            return $rows;
        }

        foreach ($this->students->values() as $idx => $student) {
            $name = trim(($student->last_name ?? '') . ', ' . ($student->first_name ?? '') . ' ' . ($student->middle_name ?? ''));
            $label = $name ?: sprintf('Student_%02d', $idx + 1);
            $rows[] = array_merge([$label], array_fill(0, $this->totalItems + 2, ''));
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        $widths = ['A' => 28];
        // Items start at column B and continue to total_items.
        for ($i = 2; $i <= $this->totalItems + 1; $i++) {
            $widths[Coordinate::stringFromColumnIndex($i)] = 8;
        }
        $scoreCol = Coordinate::stringFromColumnIndex($this->totalItems + 2);
        $pctCol = Coordinate::stringFromColumnIndex($this->totalItems + 3);
        $widths[$scoreCol] = 10;
        $widths[$pctCol] = 12;
        return $widths;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $studentCount = $this->students->isEmpty() ? 30 : $this->students->count();
                $firstStudentRow = 3;
                $lastStudentRow = $firstStudentRow + $studentCount - 1;

                $lastColIndex = $this->totalItems + 3; // A + item columns + score + percentage
                $lastColLetter = Coordinate::stringFromColumnIndex($lastColIndex);
                $firstItemCol = Coordinate::stringFromColumnIndex(2); // B
                $lastItemCol = Coordinate::stringFromColumnIndex($this->totalItems + 1); // AY for 50 items
                $scoreCol = Coordinate::stringFromColumnIndex($this->totalItems + 2); // AZ for 50
                $pctCol = Coordinate::stringFromColumnIndex($this->totalItems + 3); // BA for 50

                $summaryStart = $lastStudentRow + 2;
                $totalCorrectRow = $summaryStart;
                $actualExamineesRow = $summaryStart + 1;
                $itemAnalysisRow = $summaryStart + 2;
                $difficultyLevelRow = $summaryStart + 3;
                $difficultyIndexRow = $summaryStart + 4;
                $interpretationRow = $summaryStart + 5;
                $instructionsTitleRow = $summaryStart + 9;

                $range = "A1:{$lastColLetter}{$interpretationRow}";

                // Global alignment
                $sheet->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                // Header row (clean white like the reference)
                $sheet->getStyle("A1:{$lastColLetter}1")->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Answer key row (yellow)
                $sheet->getStyle("A2:{$lastColLetter}2")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFFFF2CC'],
                    ],
                ]);

                // Score / %age header colors like the reference
                $sheet->getStyle("{$scoreCol}1")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFFFA500'], // orange
                    ],
                    'font' => ['bold' => true],
                ]);
                $sheet->getStyle("{$pctCol}1")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FF00FFFF'], // cyan
                    ],
                    'font' => ['bold' => true],
                ]);

                // Summary labels
                $sheet->setCellValue("A{$totalCorrectRow}", 'Total Correct Responses per Item');
                $sheet->setCellValue("A{$actualExamineesRow}", 'Total Actual Examinees');
                $sheet->setCellValue("A{$itemAnalysisRow}", 'Item analysis (correct items/total examinees)');
                $sheet->setCellValue("A{$difficultyLevelRow}", 'Difficulty Level (per DO 8, s.2015)');
                $sheet->setCellValue("A{$difficultyIndexRow}", 'Difficulty Index');
                $sheet->setCellValue("A{$interpretationRow}", 'Interpretation (suggested)');

                // First column left align (names + labels)
                $sheet->getStyle("A1:A{$interpretationRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle("{$firstItemCol}{$firstStudentRow}:{$pctCol}{$lastStudentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Student score and percentage formulas with IFERROR safety.
                for ($row = $firstStudentRow; $row <= $lastStudentRow; $row++) {
                    $sheet->setCellValue(
                        "{$scoreCol}{$row}",
                        "=IF(COUNTA({$firstItemCol}{$row}:{$lastItemCol}{$row})=0,0,SUMPRODUCT(--({$firstItemCol}{$row}:{$lastItemCol}{$row}={$firstItemCol}$2:{$lastItemCol}$2),--({$firstItemCol}{$row}:{$lastItemCol}{$row}<>\"\"),--({$firstItemCol}$2:{$lastItemCol}$2<>\"\")))"
                    );
                    $sheet->setCellValue(
                        "{$pctCol}{$row}",
                        "=IFERROR(IF(COUNTA({$firstItemCol}$2:{$lastItemCol}$2)=0,0,{$scoreCol}{$row}/COUNTA({$firstItemCol}$2:{$lastItemCol}$2)),0)"
                    );
                }

                // Item-level analysis formulas per item column
                for ($col = 2; $col <= $this->totalItems + 1; $col++) {
                    $colLetter = Coordinate::stringFromColumnIndex($col);

                    $sheet->setCellValue(
                        "{$colLetter}{$totalCorrectRow}",
                        "=IF({$colLetter}$2=\"\",0,COUNTIF({$colLetter}{$firstStudentRow}:{$colLetter}{$lastStudentRow},{$colLetter}$2))"
                    );
                    $sheet->setCellValue(
                        "{$colLetter}{$actualExamineesRow}",
                        "=COUNTIF({$colLetter}{$firstStudentRow}:{$colLetter}{$lastStudentRow},\"<>\")"
                    );
                    $sheet->setCellValue(
                        "{$colLetter}{$itemAnalysisRow}",
                        "=IFERROR(IF({$colLetter}{$actualExamineesRow}=0,0,{$colLetter}{$totalCorrectRow}/{$colLetter}{$actualExamineesRow}),0)"
                    );
                    $sheet->setCellValue(
                        "{$colLetter}{$difficultyLevelRow}",
                        "=IF({$colLetter}{$itemAnalysisRow}<=0.20,\"Too Difficult\",IF({$colLetter}{$itemAnalysisRow}<=0.40,\"Difficult\",IF({$colLetter}{$itemAnalysisRow}<=0.60,\"Average\",IF({$colLetter}{$itemAnalysisRow}<=0.80,\"Easy\",\"Too Easy\"))))"
                    );
                    $sheet->setCellValue("{$colLetter}{$difficultyIndexRow}", "=IFERROR({$colLetter}{$itemAnalysisRow},0)");
                    $sheet->setCellValue(
                        "{$colLetter}{$interpretationRow}",
                        "=IF({$colLetter}{$difficultyIndexRow}<0.75,\"Revise\",\"Retain\")"
                    );
                }

                // Keep score/% summary columns non-error too
                $sheet->setCellValue("{$scoreCol}{$totalCorrectRow}", "=SUM({$scoreCol}{$firstStudentRow}:{$scoreCol}{$lastStudentRow})");
                $sheet->setCellValue("{$scoreCol}{$actualExamineesRow}", "=COUNTIF({$scoreCol}{$firstStudentRow}:{$scoreCol}{$lastStudentRow},\">=0\")");
                $sheet->setCellValue("{$pctCol}{$totalCorrectRow}", "=IFERROR(AVERAGE({$pctCol}{$firstStudentRow}:{$pctCol}{$lastStudentRow}),0)");
                $sheet->setCellValue("{$pctCol}{$actualExamineesRow}", "=COUNTIF({$pctCol}{$firstStudentRow}:{$pctCol}{$lastStudentRow},\">=0\")");

                // Number formats
                $sheet->getStyle("{$pctCol}{$firstStudentRow}:{$pctCol}{$lastStudentRow}")->getNumberFormat()->setFormatCode('0.00%');
                $sheet->getStyle("{$firstItemCol}{$itemAnalysisRow}:{$lastItemCol}{$itemAnalysisRow}")->getNumberFormat()->setFormatCode('0.00');
                $sheet->getStyle("{$firstItemCol}{$difficultyIndexRow}:{$lastItemCol}{$difficultyIndexRow}")->getNumberFormat()->setFormatCode('0.00');

                // Borders (simple black gridline look like the screenshot)
                $sheet->getStyle($range)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ]);

                // Extra styling for summary labels
                $sheet->getStyle("A{$totalCorrectRow}:A{$interpretationRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFF5F5F5'],
                    ],
                ]);

                // Instruction block
                $sheet->setCellValue("A{$instructionsTitleRow}", 'Instructions:');
                $sheet->setCellValue("A" . ($instructionsTitleRow + 1), '1. Input the letter of the Answer Key on row 2.');
                $sheet->setCellValue("A" . ($instructionsTitleRow + 2), '2. Provide each examinee\'s responses on student rows.');
                $sheet->setCellValue("A" . ($instructionsTitleRow + 3), '3. Score and %age columns compute automatically.');
                $sheet->setCellValue("A" . ($instructionsTitleRow + 4), '4. Item analysis and interpretation auto-update.');
                $sheet->setCellValue("A" . ($instructionsTitleRow + 5), '5. Blank responses are handled safely (no #DIV/0! errors).');
                $sheet->getStyle("A{$instructionsTitleRow}")->getFont()->setBold(true)->getColor()->setARGB('FFFF0000');
                $sheet->getStyle("A{$instructionsTitleRow}:A" . ($instructionsTitleRow + 5))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // Row heights for readability
                $sheet->getRowDimension(1)->setRowHeight(20);
                $sheet->getRowDimension(2)->setRowHeight(20);

                // Freeze panes: keep headers + first column visible
                $sheet->freezePane('B3');
            },
        ];
    }
}

