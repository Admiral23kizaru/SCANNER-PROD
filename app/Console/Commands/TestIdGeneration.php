<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Test ID generation from CLI to debug PDF issues.
 * Run: php artisan scanup:test-id 1
 */
class TestIdGeneration extends Command
{
    protected $signature = 'scanup:test-id {id : Student ID}';

    protected $description = 'Test ID PDF generation (bypasses HTTP, uses same logic as IDController)';

    public function handle(): int
    {
        $id = (int) $this->argument('id');
        $student = Student::find($id);

        if (!$student) {
            $this->error('Student not found.');
            return 1;
        }

        $this->info('Student: ' . $student->first_name . ' ' . $student->last_name . ' (LRN: ' . $student->student_number . ')');

        $scanupDir = base_path('scanup');
        $ds = DIRECTORY_SEPARATOR;

        $themeFile = $scanupDir . $ds . 'theme' . $ds . 'step-up.png';
        $schoolDir = $scanupDir . $ds . 'school';
        $tcpdfPath = $scanupDir . $ds . 'TCPDF-main' . $ds . 'tcpdf.php';

        $this->line('Theme: ' . (is_file($themeFile) ? 'OK' : 'MISSING'));
        $this->line('School dir: ' . (is_dir($schoolDir) ? 'OK' : 'MISSING'));
        $this->line('TCPDF: ' . (is_file($tcpdfPath) ? 'OK' : 'MISSING'));

        $origCwd = getcwd();
        try {
            chdir($scanupDir);
            require_once $tcpdfPath;
            $pdf = new \TCPDF('L', 'mm', 'A6', true, 'UTF-8', false);
            $pdf->AddPage();
            $pdf->Image('./theme/step-up.png', 0, 0, 150, '', '', '', '', false, 300);
            $content = $pdf->Output('', 'S');
            $this->info('PDF generated successfully. Size: ' . strlen($content) . ' bytes.');
            return 0;
        } catch (\Throwable $e) {
            $this->error('Error: ' . $e->getMessage());
            $this->line($e->getTraceAsString());
            return 1;
        } finally {
            @chdir($origCwd);
        }
    }
}
