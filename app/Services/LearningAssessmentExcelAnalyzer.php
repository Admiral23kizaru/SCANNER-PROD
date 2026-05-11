<?php

namespace App\Services;

use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Parses Learning Assessment roster-style XLSX (row 1 headers, row 2 answer key, row 3+ students)
 * and computes scores, percentages, and per-item difficulty (p = correct / examinees).
 */
class LearningAssessmentExcelAnalyzer
{
    private const MAX_STUDENT_ROWS = 500;

    /**
     * @return array{
     *     item_numbers: list<int>,
     *     answer_key: list<string>,
     *     students: list<array{name: string, answers: array<int, string>, score: int, percentage: float}>,
     *     item_stats: list<array{
     *         item: int,
     *         total_correct: int,
     *         examinees: int,
     *         p_value: float|null,
     *         difficulty_pct: float|null,
     *         difficulty_level: string,
     *         interpretation: string
     *     }>,
     *     total_keyed_items: int,
     *     student_count: int
     * }
     */
    public function analyze(string $absolutePath): array
    {
        $spreadsheet = IOFactory::load($absolutePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = (int) $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $highestColIndex = Coordinate::columnIndexFromString($highestColumn);

        $itemColIndexByNumber = [];
        for ($ci = 2; $ci <= $highestColIndex; $ci++) {
            $addr = Coordinate::stringFromColumnIndex($ci) . '1';
            $raw = $sheet->getCell($addr)->getValue();
            $val = is_string($raw) ? trim($raw) : trim((string) $raw);
            if ($val === '') {
                continue;
            }
            if (preg_match('/Item\s*(\d+)/iu', $val, $m)) {
                $itemColIndexByNumber[(int) $m[1]] = $ci;
            }
        }

        if ($itemColIndexByNumber === []) {
            throw new InvalidArgumentException(
                'No "Item 1" … "Item N" columns found in row 1. Use the exported roster template.'
            );
        }

        ksort($itemColIndexByNumber, SORT_NUMERIC);
        $itemNumbers = array_keys($itemColIndexByNumber);

        $answerKey = [];
        foreach ($itemNumbers as $num) {
            $ci = $itemColIndexByNumber[$num];
            $cellVal = $sheet->getCell(Coordinate::stringFromColumnIndex($ci) . '2')->getValue();
            $answerKey[$num] = is_string($cellVal) ? trim($cellVal) : trim((string) $cellVal);
        }

        $totalKeyedItems = count(array_filter($answerKey, fn ($v) => $v !== ''));

        $students = [];
        $lastDataRow = min($highestRow, self::MAX_STUDENT_ROWS + 2);

        for ($r = 3; $r <= $lastDataRow; $r++) {
            $nameRaw = $sheet->getCell('A' . $r)->getValue();
            $name = is_string($nameRaw) ? trim($nameRaw) : trim((string) $nameRaw);
            $answers = [];
            $anyAnswer = false;
            foreach ($itemNumbers as $num) {
                $ci = $itemColIndexByNumber[$num];
                $vRaw = $sheet->getCell(Coordinate::stringFromColumnIndex($ci) . $r)->getValue();
                $v = is_string($vRaw) ? trim($vRaw) : trim((string) $vRaw);
                $answers[$num] = $v;
                if ($v !== '') {
                    $anyAnswer = true;
                }
            }
            if ($name === '' && ! $anyAnswer) {
                continue;
            }
            if ($name === '') {
                $name = 'Student (row ' . $r . ')';
            }
            if (strcasecmp($name, 'Answer Key') === 0) {
                continue;
            }

            $score = 0;
            foreach ($itemNumbers as $num) {
                $key = $answerKey[$num] ?? '';
                if ($key === '') {
                    continue;
                }
                $a = $answers[$num] ?? '';
                if ($a !== '' && strcasecmp($a, $key) === 0) {
                    $score++;
                }
            }

            $percentage = $totalKeyedItems > 0
                ? round(100 * $score / $totalKeyedItems, 2)
                : 0.0;

            $students[] = [
                'name' => $name,
                'answers' => $answers,
                'score' => $score,
                'percentage' => $percentage,
            ];
        }

        if ($students === []) {
            throw new InvalidArgumentException('No student rows found below the answer key (row 3 onward).');
        }

        $itemStats = [];
        foreach ($itemNumbers as $num) {
            $key = $answerKey[$num] ?? '';
            $correct = 0;
            $examinees = 0;
            foreach ($students as $stu) {
                $a = $stu['answers'][$num] ?? '';
                if ($a === '') {
                    continue;
                }
                $examinees++;
                if ($key !== '' && strcasecmp($a, $key) === 0) {
                    $correct++;
                }
            }
            $p = $examinees > 0 ? $correct / $examinees : null;
            $band = self::bandForP($p);
            $itemStats[] = [
                'item' => $num,
                'total_correct' => $correct,
                'examinees' => $examinees,
                'p_value' => $p !== null ? round($p, 4) : null,
                'difficulty_pct' => $p !== null ? round($p * 100, 2) : null,
                'difficulty_level' => $band['difficulty_level'],
                'what_it_means' => $band['what_it_means'],
                'recommended_action' => $band['recommended_action'],
                'interpretation' => $band['recommended_action'],
            ];
        }

        foreach ($students as &$stu) {
            $ordered = [];
            foreach ($itemNumbers as $n) {
                $ordered[] = $stu['answers'][$n] ?? '';
            }
            $stu['answers'] = $ordered;
        }
        unset($stu);

        return [
            'item_numbers' => $itemNumbers,
            'answer_key' => array_map(fn ($n) => $answerKey[$n] ?? '', $itemNumbers),
            'students' => $students,
            'item_stats' => $itemStats,
            'total_keyed_items' => $totalKeyedItems,
            'student_count' => count($students),
        ];
    }

    /**
     * High p = easy item (many correct). Bands on difficulty index as percent correct.
     *
     * @return array{difficulty_level: string, what_it_means: string, recommended_action: string}
     */
    public static function bandForP(?float $p): array
    {
        if ($p === null) {
            return [
                'difficulty_level' => '—',
                'what_it_means' => '—',
                'recommended_action' => '—',
            ];
        }
        $pct = $p * 100;
        if ($pct >= 80) {
            return [
                'difficulty_level' => 'Too Easy',
                'what_it_means' => 'Most students got the item.',
                'recommended_action' => 'Consider revising or removing if appropriate.',
            ];
        }
        if ($pct >= 50) {
            return [
                'difficulty_level' => 'Ideal / Moderately Easy',
                'what_it_means' => 'Good item – well balanced.',
                'recommended_action' => 'Usually no change needed.',
            ];
        }
        if ($pct >= 30) {
            return [
                'difficulty_level' => 'Moderately Difficult',
                'what_it_means' => 'A bit challenging, but still fair.',
                'recommended_action' => 'Review for clarity or alignment.',
            ];
        }

        return [
            'difficulty_level' => 'Too Difficult',
            'what_it_means' => 'Very few students got it right.',
            'recommended_action' => 'Consider for remediation.',
        ];
    }
}
