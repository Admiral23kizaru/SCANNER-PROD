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

        // Prepare Data
        $fullName = trim(implode(' ', array_filter([
            $student->first_name ?? null,
            $student->middle_name ?? null,
            $student->last_name ?? null,
        ])));
        $fullName = $fullName !== '' ? $fullName : '—';
        $lrn = (string) ($student->student_number ?? '');
        $guardian = (string) ($student->guardian ?? '');
        $contact = (string) ($student->contact_number ?? '');

        $qrPayload = $lrn; // Minimal payload: numeric ID only
        
        $qrStyle = [
            'border' => 0,
            'vpadding' => 0,
            'hpadding' => 0,
            'fgcolor' => [0, 0, 0],
            'bgcolor' => false,
            'module_width' => 1,
            'module_height' => 1,
        ];
        
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

        // ---------------- PAGE 1 (FRONT) ----------------
        $pdf->AddPage();
        $pdf->Image($frontTemplate, 0, 0, $cardW, $cardH, 'PNG', '', '', false, 300);

        // FRONT: QR Code (Optimized: ECC 'M', smaller size, centered vertically)
        // 18x18 limits the size to about 180px in 300dpi. Vertically centered around y=10.
        $pdf->write2DBarcode($qrPayload, 'QRCODE,M', 11, 10, 18, 18, $qrStyle, 'N');

        // FRONT: Emergency Contact
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetXY(4, 32);
        $pdf->Cell(34, 0, 'In case of emergency, contact:', 0, 1, 'C');
        
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetXY(4, 36);
        $pdf->Cell(34, 0, $guardian !== '' ? mb_strtoupper($guardian) : 'N/A', 0, 1, 'C');
        
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetXY(4, 40);
        $pdf->Cell(34, 0, $contact !== '' ? $contact : 'N/A', 0, 1, 'C');

        // FRONT: Photo
        $photoPath = public_path('school/' . $student->student_number . '.jpg');
        if (!is_file($photoPath)) {
            $photoPath = public_path('school/' . $student->student_number . '.png');
        }
        if (is_file($photoPath)) {
            $pdf->Image($photoPath, 50.0, 14.0, 21.0, 21.0, '', '', '', false, 300, '', false, false, 0, 'CT');
        }

        // FRONT: Student Name
        $lastNamePart = mb_strtoupper(trim((string) ($student->last_name ?? ''))) . ',';
        $firstMiddlePart = mb_strtoupper(trim(implode(' ', array_filter([$student->first_name ?? null, $student->middle_name ?? null]))));
        
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetXY(40, 36);
        $pdf->Cell(44, 0, $lastNamePart, 0, 1, 'C');
        
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetXY(40, 40);
        $pdf->Cell(44, 0, $firstMiddlePart, 0, 1, 'C');

        // FRONT: Barcode
        $barcodeW = 34.0;
        $barcodeX = 40 + ((44 - $barcodeW) / 2.0);
        $pdf->write1DBarcode($lrn, 'C128', $barcodeX, 44.5, $barcodeW, 5.0, 0.4, $barcodeStyle, 'N');

        // FRONT: LRN Strip
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetXY(0, 50.5);
        $pdf->Cell($cardW - 4, 0, 'LRN: ' . $lrn, 0, 1, 'R');

        // ---------------- PAGE 2 (BACK) ----------------
        $pdf->AddPage();
        $pdf->Image($backTemplate, 0, 0, $cardW, $cardH, 'PNG', '', '', false, 300);

        // BACK: Guardian Details
        // Center vertically on the page and use left alignment.
        $pdf->SetTextColor(0, 0, 0);
        
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetXY(15, 15);
        $pdf->MultiCell(55, 5, "In case of emergency,\nplease contact:", 0, 'L', false, 1);
        
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetXY(15, 26);
        $pdf->Cell(55, 5, 'Guardian:', 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY(15, 31);
        $pdf->Cell(55, 5, $guardian !== '' ? $guardian : 'N/A', 0, 1, 'L');
        
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetXY(15, 38);
        $pdf->Cell(55, 5, 'Contact No:', 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY(15, 43);
        $pdf->Cell(55, 5, $contact !== '' ? $contact : 'N/A', 0, 1, 'L');

        if (ob_get_length()) {
            ob_end_clean();
        }

        $content = $pdf->Output('student_id.pdf', 'S');

        return response($content)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="student_id.pdf"');
    }
}

