<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * ID generation matches scanup/learner-id.php layout:
 * A6 landscape, theme/step-up.png, school logo & photo, img/ or school/ for photos,
 * QR + barcode, HTML table for name and emergency contact.
 *
 * Scanup flow: photo (behind) -> theme -> school logo -> QR -> barcode -> text table.
 * Assets: scanup/theme/step-up.png, scanup/school/*.png, img/{lrn}.jpg or school/{lrn}.jpg
 */
class IDController extends Controller
{
    public function generate(Request $request, int $id)
    {
        $student = Student::find($id);

        if (!$student) {
            return response()->json(['message' => 'Student not found.'], 404);
        }

        $user = $request->user();
        $roleName = $user?->role?->name;
        if ($roleName === 'Teacher') {
            $allowed = ($student->teacher_id === $user->id || $student->created_by === $user->id);
            if (!$allowed) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
        }

        $scanupDir = base_path('scanup');
        $ds = DIRECTORY_SEPARATOR;

        $gdInfo = function_exists('gd_info') ? gd_info() : [];
        $hasPng = isset($gdInfo['PNG Support']) && $gdInfo['PNG Support'] === true;
        $hasGd = extension_loaded('gd')
            && function_exists('gd_info')
            && function_exists('imagecreatetruecolor')
            && function_exists('imagepng')
            && $hasPng;

        $hasImagick = extension_loaded('imagick')
            && class_exists('Imagick');

        if (!$hasGd && !$hasImagick) {
            throw new \RuntimeException(
                'PHP GD (with PNG support) or Imagick required for ID generation.'
            );
        }

        // Verify scanup assets exist (same structure as learner-id.php)
        $themeFile = $scanupDir . $ds . 'theme' . $ds . 'step-up.png';
        $schoolDir = $scanupDir . $ds . 'school';
        $tcpdfPath = $scanupDir . $ds . 'TCPDF-main' . $ds . 'tcpdf.php';

        if (!is_file($themeFile) || !is_readable($themeFile)) {
            return response()->json(['message' => 'Theme not found. Ensure scanup/theme/step-up.png exists.'], 404);
        }
        if (!is_dir($schoolDir)) {
            return response()->json(['message' => 'School folder not found. Ensure scanup/school/ exists.'], 404);
        }
        if (!is_file($tcpdfPath) || !is_readable($tcpdfPath)) {
            return response()->json(['message' => 'TCPDF not found. Ensure scanup/TCPDF-main/tcpdf.php exists.'], 500);
        }

        $lrn = $student->student_number;

        // Find school logo: logo.png or first *.png (learner-id.php: ./school/Ozamiz City CS.png)
        $schoolLogo = $schoolDir . $ds . 'logo.png';
        if (!is_file($schoolLogo) || !is_readable($schoolLogo)) {
            $logos = glob($schoolDir . $ds . '*.png');
            $schoolLogo = ($logos && is_file($logos[0])) ? $logos[0] : null;
        }

        // Find photo: img/{lrn}, school/{lrn}, public/school/{lrn} (learner-id.php: img/ only)
        $photoPath = null;
        foreach ([$scanupDir . $ds . 'img', $schoolDir, public_path('school')] as $d) {
            if (!is_dir($d)) continue;
            foreach (['.jpg', '.png'] as $ext) {
                $p = $d . $ds . $lrn . $ext;
                if (is_file($p) && is_readable($p)) {
                    $photoPath = $p;
                    break 2;
                }
            }
        }

        $origCwd = getcwd();
        try {
            // Run from scanup dir so paths match learner-id.php exactly (./theme, ./school)
            chdir($scanupDir);

            require_once $tcpdfPath;

            $pdf = new \TCPDF('L', 'mm', 'A6', true, 'UTF-8', false);
            $pdf->SetCreator('ScanUp');
            $pdf->SetAuthor('DepEd ScanUp');
            $pdf->SetTitle('Learner ID: ' . $student->first_name . ' ' . $student->last_name);
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(2, 2, 2);
            $pdf->SetAutoPageBreak(false);
            $pdf->SetFont('helvetica', '', 11);
            $pdf->AddPage();

            set_time_limit(0);
            ini_set('memory_limit', '-1');

            // Exact order from learner-id.php: photo (behind) -> theme -> school logo
            // Use relative paths (./) since we chdir'd to scanup
            if ($photoPath && is_file($photoPath) && is_readable($photoPath)) {
                $pdf->Image($photoPath, 80, 33, 0, 32, '', '', '', true);
            }
            $pdf->Image('./theme/step-up.png', 0, 0, 150, '', '', '', '', false, 300);
            if ($schoolLogo && is_file($schoolLogo) && is_readable($schoolLogo)) {
                $pdf->Image('./school/' . basename($schoolLogo), 121.2, 33.5, 21, 21, '', '', '', false, 150);
            }

            $qrStyle = [
                'border' => 0,
                'vpadding' => 0,
                'hpadding' => 0,
                'fgcolor' => [0, 0, 0],
                'bgcolor' => false,
                'module_width' => 1,
                'module_height' => 1,
            ];
            $pdf->write2DBarcode($lrn, 'QRCODE,L', 22, 22, 32, 32, $qrStyle, 'N');

            $barcodeStyle = [
                'position' => '',
                'align' => 'C',
                'stretchtext' => 4,
                'fgcolor' => [0, 0, 0],
                'bgcolor' => false,
            ];
            $pdf->write1DBarcode($lrn, 'C39', 84, 86, 50, 10, '', $barcodeStyle, 'N');

            $lastName = '<span style="font-size:16px; color:#000000;"><b>' . str_replace('ñ', 'Ñ', strtoupper($student->last_name)) . ',</b></span>';
            $firstName = '<span style="font-size:14px; color:#000000;"><b>'
                . str_replace('ñ', 'Ñ', strtoupper($student->first_name))
                . (!empty($student->middle_name) ? ' ' . mb_substr($student->middle_name, 0, 1) . '.' : '')
                . '</b></span>';
            $lrnText = '<span style="font-size:12px; color:#fff;"><b>LRN: ' . $lrn . '</b></span>';
            $guardian = $student->guardian ?? $student->emergency_contact ?? '';
            $contact = ($student->contact_number === '0' || $student->contact_number === null || $student->contact_number === '') ? '' : ($student->contact_number ?? '');

            $html = '
            <table cellpadding="1">
            <tr><th width="100%" style="font-size: 145px"></th></tr>
            <tr>
                <td width="50%" align="center">In case of emergency, contact:<br><span style="font-size:10px;">' . $guardian . '</span></td>
                <td width="50%" align="center">' . $lastName . '</td>
            </tr>
            <tr>
                <td width="50%" align="center"><span style="font-size:10px;">' . $contact . '</span></td>
                <td width="50%" align="center">' . $firstName . '</td>
            </tr>
            <tr><th width="100%" style="font-size: 30px"></th></tr>
            <tr>
                <td width="50%"></td>
                <td width="50%" align="center">' . $lrnText . '</td>
            </tr>
            </table>';

            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->SetXY(0, 2);
            $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, 'C', true);

            $pdfContent = $pdf->Output('', 'S');

            if (!is_string($pdfContent) || strlen($pdfContent) < 100) {
                return response()->json(['message' => 'PDF generation produced no output.'], 500);
            }

            return new HttpResponse($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $lrn . '-id.pdf"',
                'Cache-Control' => 'private, max-age=0, must-revalidate',
                'Pragma' => 'public',
            ]);
        } catch (\Throwable $e) {
            report($e);
            $msg = $e->getMessage();
            if (str_contains($msg, 'Imagick or GD extension')) {
                $msg = 'PNG with alpha requires GD (imagecreatefrompng) or Imagick. Web server PHP may lack GD—enable extension=gd in php.ini and restart the web server (not only CLI).';
            }
            return response()->json(['message' => 'ID generation failed: ' . $msg], 500);
        } finally {
            @chdir($origCwd);
        }
    }
}
