<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Chart\Axis;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\GridLines;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Properties;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Full item-analysis workbook: scores, %age, difficulty rows, instructions, interpretation guide, embedded chart.
 *
 * @phpstan-type StudentRow array{name: string, answers: list<string>, score: int, percentage: float}
 * @phpstan-type ItemStat array{item: int, total_correct: int, examinees: int, p_value: float|null, difficulty_pct: float|null, difficulty_level: string, interpretation: string, what_it_means?: string, recommended_action?: string}
 */
class LearningAssessmentAnalyzedExport implements FromArray, WithCharts, WithEvents, WithColumnWidths, WithTitle
{
    private int $firstAnalysisRow = 0;

    private int $chartTitleRow = 0;

    private int $lastDataColIndex = 0;

    private int $studentLastRow = 0;

    private int $instructionsTitleRow = 0;

    private int $instructionRedRow = 0;

    private int $guideTitleRow = 0;

    private int $guideHeaderRow = 0;

    private int $guideDataFirstRow = 0;

    private int $guideEndColIndex = 14;

    /**
     * @param  array{
     *     item_numbers: list<int>,
     *     answer_key: list<string>,
     *     students: list<StudentRow>,
     *     item_stats: list<ItemStat>
     * }  $payload
     */
    public function __construct(
        private array $payload,
        private readonly string $sheetTitle = 'Learning Assessment',
    ) {}

    public function title(): string
    {
        return LearningAssessmentTemplateExport::sanitizeSheetTitle($this->sheetTitle);
    }

    public function array(): array
    {
        $items = $this->payload['item_numbers'];
        $n = count($items);
        $lastCol = max(1 + $n + 2, $this->guideEndColIndex);
        $this->lastDataColIndex = $lastCol;

        $header = array_merge(
            ['Student ID/Name'],
            array_map(fn ($i) => 'Item ' . $i, $items),
            ['Score', '%age'],
        );

        $answerRow = array_merge(
            ['Answer Key'],
            $this->payload['answer_key'],
            ['', ''],
        );

        $rows = [$header, $answerRow];
        foreach ($this->payload['students'] as $stu) {
            $pct = $stu['percentage'] . '%';
            $rows[] = array_merge(
                [$stu['name']],
                $stu['answers'],
                [$stu['score'], $pct],
            );
        }

        $this->studentLastRow = 2 + count($this->payload['students']);
        $rows[] = [];
        $this->firstAnalysisRow = count($rows) + 1;

        $byLabel = function (string $label, callable $valueForStat): array {
            $out = [$label];
            foreach ($this->payload['item_stats'] as $idx => $stat) {
                $out[] = $valueForStat($stat, $idx);
            }
            $out[] = '';
            $out[] = '';

            return $out;
        };

        $rows[] = $byLabel('Total Correct Responses per Item', fn ($s) => (string) $s['total_correct']);
        $rows[] = $byLabel('Total Actual Examinees', fn ($s) => (string) $s['examinees']);
        $rows[] = $byLabel('Item analysis (correct items/total)', function ($s) {
            if ($s['examinees'] > 0) {
                return (string) round($s['total_correct'] / $s['examinees'], 2);
            }

            return '0.00';
        });
        $rows[] = $byLabel('Difficulty Level (per DO 8, s.2015)', fn ($s) => $s['difficulty_level']);
        $rows[] = $byLabel('Difficulty Index', function ($s) {
            return $s['difficulty_pct'] !== null ? (string) $s['difficulty_pct'] . '%' : '—';
        });
        $rows[] = $byLabel('Interpretation (suggested)', function ($s) {
            return $s['recommended_action'] ?? $s['interpretation'] ?? '—';
        });

        $rows[] = [];
        $this->instructionsTitleRow = count($rows) + 1;
        $rows[] = ['Instructions:'];
        $this->instructionRedRow = count($rows) + 1;
        $rows[] = ['1. Input the letter of the Answer Key.'];
        $rows[] = ['2. Provide names of the examinees. Fill row 2 with the Key Answers.'];
        $rows[] = ['3. Encode the learners\' answers per item.'];
        $rows[] = ['4. The system automatically provides correct responses, total scores per learner, and item difficulty index.'];
        $rows[] = ['5. The cells without entry will not be scored.'];
        $rows[] = ['6. Empty cells will not be counted. Check your entries.'];

        $rows[] = [];
        $this->guideTitleRow = count($rows) + 1;
        $rows[] = ['Difficulty Index (Percentage-Based) Interpretation Guide'];
        $this->guideHeaderRow = count($rows) + 1;
        $rows[] = ['Difficulty Index (%)', '', 'Interpretation', '', '', 'What It Means', '', '', '', 'Recommended Action'];
        $this->guideDataFirstRow = count($rows) + 1;
        $rows[] = ['80% – 100%', 'Too Easy', 'Most students got the item.', 'Consider revising or removing if appropriate.'];
        $rows[] = ['50% – 79%', 'Ideal / Moderately Easy', 'Good item – well balanced.', 'Usually no change needed.'];
        $rows[] = ['30% – 49%', 'Moderately Difficult', 'A bit challenging, but still fair.', 'Review for clarity or alignment.'];
        $rows[] = ['0% – 29%', 'Too Difficult', 'Very few students got it right.', 'Consider for remediation.'];

        $rows[] = [];
        $this->chartTitleRow = count($rows) + 1;
        $rows[] = array_pad(['Difficulty Index Chart'], $lastCol, '');

        foreach ($rows as $i => $row) {
            $rows[$i] = array_pad($row, $lastCol, '');
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        $widths = ['A' => 36];
        $items = $this->payload['item_numbers'];
        $n = count($items);
        for ($i = 2; $i <= 1 + $n; $i++) {
            $widths[Coordinate::stringFromColumnIndex($i)] = 7;
        }
        $widths[Coordinate::stringFromColumnIndex($n + 2)] = 9;
        $widths[Coordinate::stringFromColumnIndex($n + 3)] = 10;

        return $widths;
    }

    public function charts(): array
    {
        return [];
    }

    private function quotedSheetTitle(Worksheet $sheet): string
    {
        return "'" . str_replace("'", "''", $sheet->getTitle()) . "'";
    }

    private function attachDifficultyChart(Worksheet $sheet): void
    {
        $n = count($this->payload['item_numbers']);
        if ($n === 0 || $this->chartTitleRow < 1) {
            return;
        }

        $itemCol = Coordinate::stringFromColumnIndex($this->lastDataColIndex + 1);
        $pCol = Coordinate::stringFromColumnIndex($this->lastDataColIndex + 2);
        $r0 = $this->chartTitleRow + 24;
        $i = 0;
        foreach ($this->payload['item_stats'] as $stat) {
            $r = $r0 + $i++;
            $sheet->setCellValue($itemCol . $r, $stat['item']);
            $pv = $stat['p_value'];
            $sheet->setCellValue($pCol . $r, $pv !== null ? round((float) $pv, 4) : 0.0);
        }
        $lastR = $r0 + $n - 1;
        $sheet->getColumnDimension($itemCol)->setWidth(2.5)->setVisible(false);
        $sheet->getColumnDimension($pCol)->setWidth(2.5)->setVisible(false);

        $q = $this->quotedSheetTitle($sheet);
        $catRef = "{$q}!\${$itemCol}\${$r0}:\${$itemCol}\${$lastR}";
        $valRef = "{$q}!\${$pCol}\${$r0}:\${$pCol}\${$lastR}";

        $plotLabels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, null, null, 1, ['Difficulty Index']),
        ];
        $plotCategories = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, $catRef, null, $n),
        ];
        $plotValues = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, $valRef, Properties::FORMAT_CODE_NUMBER, $n),
        ];

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            range(0, count($plotValues) - 1),
            $plotLabels,
            $plotCategories,
            $plotValues,
            DataSeries::DIRECTION_COL,
        );

        $plotArea = new PlotArea(null, [$series]);

        $xAxis = new Axis();
        $xAxis->setAxisType(Axis::AXIS_TYPE_CATEGORY);

        $yAxis = new Axis();
        $yAxis->setAxisType(Axis::AXIS_TYPE_VALUE);
        $yAxis->setAxisNumberProperties('#,##0.00', true, 0);
        $yAxis->setAxisOptionsProperties(
            Axis::AXIS_LABELS_NEXT_TO,
            null,
            null,
            null,
            null,
            null,
            '0',
            '1',
            '0.1',
            null
        );
        $yAxis->setMajorGridlines(new GridLines());

        $chart = new Chart(
            'difficulty_index_chart',
            new Title('Difficulty Index Chart'),
            null,
            $plotArea,
            false,
            DataSeries::EMPTY_AS_GAP,
            new Title('Item Number'),
            new Title('Difficulty Index'),
            $xAxis,
            $yAxis,
            new GridLines(),
            null,
        );

        $topRow = $this->chartTitleRow + 1;
        $chart->setTopLeftPosition('A' . $topRow, 0, 10);
        $chartRight = Coordinate::stringFromColumnIndex(max(20, $this->lastDataColIndex));
        $chart->setBottomRightPosition($chartRight . ($topRow + 20), 0, -10);
        for ($r = $topRow; $r <= $topRow + 20; $r++) {
            $sheet->getRowDimension($r)->setRowHeight(18);
        }
        $sheet->addChart($chart);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $n = count($this->payload['item_numbers']);
                $lastItemCi = 1 + $n;
                $scoreCi = $n + 2;
                $pctCi = $n + 3;
                $lastLetter = Coordinate::stringFromColumnIndex($this->lastDataColIndex);
                $guideEndLetter = Coordinate::stringFromColumnIndex($this->guideEndColIndex);
                $lastItemLetter = Coordinate::stringFromColumnIndex($lastItemCi);
                $scoreLetter = Coordinate::stringFromColumnIndex($scoreCi);
                $pctLetter = Coordinate::stringFromColumnIndex($pctCi);

                $sheet->getStyle("A1:{$lastLetter}1")->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle("{$scoreLetter}1")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFFFE699'],
                    ],
                ]);
                $sheet->getStyle("{$pctLetter}1")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FF99FFFF'],
                    ],
                ]);

                $sheet->getStyle("A2:{$lastLetter}2")->applyFromArray([
                    'font' => ['bold' => true],
                ]);
                $sheet->getStyle("B2:{$lastItemLetter}2")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFFFF2CC'],
                    ],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle('A2')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFFFF2CC'],
                    ],
                ]);
                $sheet->getStyle("{$scoreLetter}2")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFFFC000'],
                    ],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle("{$pctLetter}2")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FF99FFFF'],
                    ],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                if ($this->studentLastRow >= 3) {
                    $sheet->getStyle("{$scoreLetter}3:{$scoreLetter}{$this->studentLastRow}")->applyFromArray([
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                    $sheet->getStyle("{$pctLetter}3:{$pctLetter}{$this->studentLastRow}")->applyFromArray([
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                    $sheet->getStyle("B3:{$lastItemLetter}{$this->studentLastRow}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                $lastAnalysisRow = $this->firstAnalysisRow + 5;
                $sheet->getStyle("A{$this->firstAnalysisRow}:A{$lastAnalysisRow}")->applyFromArray([
                    'font' => ['bold' => true],
                ]);
                $sheet->getStyle("B{$this->firstAnalysisRow}:{$lastLetter}{$lastAnalysisRow}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $instrEnd = $this->instructionsTitleRow + 6;
                $sheet->getStyle("A{$this->instructionsTitleRow}:A{$instrEnd}")->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_TOP],
                ]);
                $sheet->getStyle("A{$this->instructionsTitleRow}")->getFont()->setBold(true);
                if ($this->instructionRedRow > 0) {
                    $sheet->getStyle("A{$this->instructionRedRow}")->getFont()->getColor()->setARGB('FFFF0000');
                }

                $sheet->mergeCells("A{$this->guideTitleRow}:{$guideEndLetter}{$this->guideTitleRow}");
                $sheet->getStyle("A{$this->guideTitleRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension($this->guideTitleRow)->setRowHeight(24);

                $sheet->getStyle("A{$this->guideHeaderRow}:{$guideEndLetter}{$this->guideHeaderRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFF2F2F2'],
                    ],
                ]);
                $sheet->getRowDimension($this->guideHeaderRow)->setRowHeight(24);

                $interpretationBg = ['FFE6D9F2', 'FFFFF9C4', 'FFC6EFCE', 'FF6BB6FF'];
                foreach ($interpretationBg as $idx => $argb) {
                    $r = $this->guideDataFirstRow + $idx;
                    $interpretation = $sheet->getCell("B{$r}")->getValue();
                    $meaning = $sheet->getCell("C{$r}")->getValue();
                    $action = $sheet->getCell("D{$r}")->getValue();
                    $sheet->setCellValue("C{$r}", $interpretation);
                    $sheet->setCellValue("F{$r}", $meaning);
                    $sheet->setCellValue("J{$r}", $action);
                    $sheet->setCellValue("B{$r}", '');
                    $sheet->setCellValue("D{$r}", '');

                    $sheet->getStyle("C{$r}:E{$r}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['argb' => $argb],
                        ],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
                    ]);
                    $sheet->getStyle("A{$r}:{$guideEndLetter}{$r}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                    $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("F{$r}:{$guideEndLetter}{$r}")->getAlignment()->setWrapText(true);
                    $sheet->getRowDimension($r)->setRowHeight(28);
                }

                foreach ([$this->guideHeaderRow, $this->guideDataFirstRow, $this->guideDataFirstRow + 1, $this->guideDataFirstRow + 2, $this->guideDataFirstRow + 3] as $r) {
                    $sheet->mergeCells("A{$r}:B{$r}");
                    $sheet->mergeCells("C{$r}:E{$r}");
                    $sheet->mergeCells("F{$r}:I{$r}");
                    $sheet->mergeCells("J{$r}:{$guideEndLetter}{$r}");
                }

                $sheet->getStyle("A{$this->guideHeaderRow}:{$guideEndLetter}" . ($this->guideDataFirstRow + 3))->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FFD9D9D9'],
                        ],
                    ],
                ]);

                if ($this->chartTitleRow > 0) {
                    $sheet->mergeCells("A{$this->chartTitleRow}:{$lastLetter}{$this->chartTitleRow}");
                    $sheet->getStyle("A{$this->chartTitleRow}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 12],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'FFF5F5F5'],
                        ],
                    ]);
                    $sheet->getRowDimension($this->chartTitleRow)->setRowHeight(24);
                }

                $borderEndRow = $this->chartTitleRow;
                $sheet->getStyle("A1:{$lastLetter}{$borderEndRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ]);

                $this->attachDifficultyChart($sheet);

                if ($this->studentLastRow >= 3) {
                    $sheet->freezePane('B3');
                }
            },
        ];
    }
}
