<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

class IdCardController extends Controller
{
    public function generate(Request $request, int $id): Response
    {
        $student = Student::findOrFail($id);

        $user = $request->user();
        if ($user?->role?->name === 'Teacher') {
            $allowed = ($student->teacher_id === $user->id || $student->created_by === $user->id);
            if (!$allowed) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
        }

        $hasGdPng = function_exists('imagecreatefrompng') && function_exists('imagepng');
        $hasImagick = class_exists('Imagick');
        if (!$hasGdPng && !$hasImagick) {
            return response()->json(['message' => 'PHP GD (PNG) or Imagick is required for PNG templates.'], 500);
        }

        if (!class_exists(\TCPDF::class)) {
            return response()->json(['message' => 'TCPDF is not installed. Install tecnickcom/tcpdf via Composer.'], 500);
        }

        $frontTemplate = base_path('ID' . DIRECTORY_SEPARATOR . '1.png');
        $backTemplate = base_path('ID' . DIRECTORY_SEPARATOR . '2.png');
        if (!is_file($frontTemplate) || !is_readable($frontTemplate)) {
            return response()->json(['message' => 'Front ID template missing in ID/1.png.'], 500);
        }
        if (!is_file($backTemplate) || !is_readable($backTemplate)) {
            return response()->json(['message' => 'Back ID template missing in ID/2.png.'], 500);
        }

        $pdf = new \TCPDF('L', 'mm', [54.0, 85.6], true, 'UTF-8', false);
        $pdf->SetCreator('ScanUp');
        $pdf->SetAuthor('ScanUp');
        $pdf->SetTitle('Student ID');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false, 0);

        $cardW = 85.6;
        $cardH = 54.0;

        // Page 1 (Front)
        $pdf->AddPage();
        $pdf->Image($frontTemplate, 0, 0, $cardW, $cardH, 'PNG', '', '', false, 300);

        $fullName = trim(implode(' ', array_filter([
            $student->first_name ?? null,
            $student->middle_name ?? null,
            $student->last_name ?? null,
        ])));
        $fullName = $fullName !== '' ? $fullName : '—';

        $lrn = (string) ($student->student_number ?? '');

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetXY(0, 35);
        $pdf->Cell($cardW, 0, mb_strtoupper($fullName), 0, 1, 'C', false, '', 0, false, 'T', 'M');

        $barcodeStyle = [
            'position' => '',
            'align' => 'C',
            'stretch' => false,
            'fitwidth' => true,
            'cellfitalign' => '',
            'border' => false,
            'hpadding' => 'auto',
            'vpadding' => 'auto',
            'fgcolor' => [0, 0, 0],
            'bgcolor' => false,
            'text' => false,
            'font' => 'helvetica',
            'fontsize' => 8,
            'stretchtext' => 4,
        ];

        $barcodeW = 60.0;
        $barcodeX = ($cardW - $barcodeW) / 2.0;
        $pdf->write1DBarcode($lrn, 'C128', $barcodeX, 40.5, $barcodeW, 8.0, 0.4, $barcodeStyle, 'N');

        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetXY(0, 50);
        $pdf->Cell($cardW, 0, 'LRN: ' . $lrn, 0, 1, 'C');

        // Page 2 (Back)
        $pdf->AddPage();
        $pdf->Image($backTemplate, 0, 0, $cardW, $cardH, 'PNG', '', '', false, 300);

        $guardian = (string) ($student->guardian ?? '');
        $contact = (string) ($student->contact_number ?? '');

        $qrPayload = "Name: " . $fullName . "\nLRN: " . $lrn . "\nGrade/Section: " . ($student->grade ?? '') . " " . ($student->section ?? '') . "\nGuardian: " . $guardian . "\nContact: " . $contact;
        $qrStyle = [
            'border' => 0,
            'vpadding' => 0,
            'hpadding' => 0,
            'fgcolor' => [0, 0, 0],
            'bgcolor' => false,
            'module_width' => 1,
            'module_height' => 1,
        ];
        $pdf->write2DBarcode($qrPayload, 'QRCODE,H', 60.5, 8.5, 22.0, 22.0, $qrStyle, 'N');

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetXY(6, 32);
        $pdf->MultiCell(54, 0, "Guardian:\n" . ($guardian !== '' ? $guardian : '—'), 0, 'L', false, 1);
        $pdf->SetXY(6, 43);
        $pdf->MultiCell(54, 0, "Contact:\n" . ($contact !== '' ? $contact : '—'), 0, 'L', false, 1);

        if (ob_get_length()) {
            ob_end_clean();
        }

        $content = $pdf->Output('student_id.pdf', 'S');

        return response($content)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="student_id.pdf"');
    }
}

