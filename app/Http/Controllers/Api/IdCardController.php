<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\IdCardImageService;
use App\Models\Student;
use App\Models\User;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\URL;

class IdCardController extends Controller
{
    public function getSignedUrl(Request $request, int $id): JsonResponse
    {
        $student = Student::findOrFail($id);
        $user = $request->user();

        // Admin can access any student; Teacher restricted to own students
        if ($user?->role?->name === 'Teacher') {
            $allowed = ($student->teacher_id === $user->id || $student->created_by === $user->id);
            if (!$allowed) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
        }

        $hash = md5($student->student_number . config('app.key'));
        
        // Generate a signed URL that expires in 5 minutes (300 seconds)
        $url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'id.download',
            now()->addMinutes(5),
            ['hash' => $hash, 'id' => $student->id]
        );

        return response()->json(['url' => $url]);
    }

    /**
     * Admin-only: generate a signed URL for inline Teacher ID PDF preview.
     */
    public function getTeacherSignedUrl(Request $request, int $id): JsonResponse
    {
        $teacher = User::findOrFail($id);

        // Ensure target is a Teacher account
        if (!$teacher->role || strcasecmp((string) $teacher->role->name, 'Teacher') !== 0) {
            return response()->json(['message' => 'Teacher not found.'], 404);
        }

        if (!$teacher->job_title) {
            return response()->json(['message' => 'Please set the teacher job title first.'], 422);
        }

        $hash = md5($teacher->employee_id . '-teacher-' . config('app.key'));

        $url = URL::temporarySignedRoute(
            'teacher-id.download',
            now()->addMinutes(5),
            ['hash' => $hash, 'id' => $teacher->id]
        );

        return response()->json(['url' => $url]);
    }

    public function generateSecure(Request $request, string $hash): Response
    {
        // Require valid signature
        if (!$request->hasValidSignature()) {
            abort(401, 'Invalid or expired signature.');
        }

        $id = $request->query('id');
        $student = Student::findOrFail($id);

        $expectedHash = md5($student->student_number . config('app.key'));
        if ($hash !== $expectedHash) {
            abort(403, 'Invalid file reference.');
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
        $pdf->SetCreator('Ozamiz Schools QR-ID System');
        $pdf->SetAuthor('Ozamiz Schools QR-ID System');
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

        // ── Locate student photo ──────────────────────────────────────────────
        $photoPath = null;
        if ($student->photo_path) {
            $cleanStdPath = ltrim(str_replace(['public/', 'storage/'], '', (string)$student->photo_path), '/');
            $candidate = public_path('storage/' . $cleanStdPath);
            if (is_file($candidate) && is_readable($candidate)) {
                $photoPath = $candidate;
            }
        }
        
        if (!$photoPath) {
            // Fallback to legacy LRN-based path in public/school/
            $candidate = public_path('school/' . $student->student_number . '.png');
            if (is_file($candidate)) {
                $photoPath = $candidate;
            } else {
                $candidate = public_path('school/' . $student->student_number . '.jpg');
                if (is_file($candidate)) {
                    $photoPath = $candidate;
                }
            }
        }

        // ================================================================
        // PAGE 1 (FRONT) — All elements use strict absolute positioning.
        // No Ln(), no MultiCell(), no auto line breaks.
        // Card: 85.6mm wide × 54mm tall (landscape).
        // Left section (0–38mm): QR code, emergency contact
        // Right section (40–84mm): photo, name, barcode, LRN
        // ================================================================
        $pdf->AddPage();
        $pdf->Image($frontTemplate, 0, 0, $cardW, $cardH, 'PNG', '', '', false, 300);

        // ── QR Code ──────────────────────────────────────────────────────
        $pdf->write2DBarcode($qrPayload, 'QRCODE,M', 11, 12, 16, 16, $qrStyle, 'N');

        // ── Emergency Contact (left column) ──────────────────────────────
        $pdf->SetTextColor(0, 0, 0);

        $pdf->SetFont('helvetica', 'B', 6);
        $pdf->SetXY(4, 32);
        $pdf->Cell(34, 3, 'In case of emergency, contact:', 0, 0, 'C');

        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetXY(4, 36);
        $pdf->Cell(34, 3, $guardian !== '' ? mb_strtoupper($guardian) : 'N/A', 0, 0, 'C');

        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetXY(4, 40);
        $pdf->Cell(34, 3, $contact !== '' ? $contact : 'N/A', 0, 0, 'C');

       
        if ($photoPath) {
            $imgType = (strtolower(pathinfo($photoPath, PATHINFO_EXTENSION)) === 'png') ? 'PNG' : 'JPEG';
            $pdf->Image($photoPath, 49.0, 15.0, 15.0, 17.0, $imgType,
                        '', '', false, 300, '', false, false, 0, 'CT');
        }

        // ── Student Name (below photo, right column) ─────────────────────
        $lastNamePart    = mb_strtoupper(trim((string) ($student->last_name ?? ''))) . ',';
        $firstMiddlePart = mb_strtoupper(trim(implode(' ', array_filter([
            $student->first_name ?? null,
            $student->middle_name ?? null,
        ]))));

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetXY(40, 35);
        $pdf->Cell(44, 4, $lastNamePart, 0, 0, 'C');

        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetXY(40, 39);
        $pdf->Cell(43, 4, $firstMiddlePart, 0, 0, 'C');

        // ── Barcode (right column, below name) ───────────────────────────
        $barcodeW = 34.0;
        $barcodeX = 40.0 + ((44.0 - $barcodeW) / 2.0);
        $pdf->write1DBarcode($lrn, 'C128', $barcodeX, 44.5, $barcodeW, 5.0, 0.4, $barcodeStyle, 'N');

        // ── LRN text on bottom strip ─────────────────────────────────────
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetXY(0, 50.5);
        $pdf->Cell($cardW - 4, 3, 'LRN: ' . $lrn, 0, 0, 'R');


        // ---------------- PAGE 2 (BACK) ----------------
        // Completely clean block: just the blank template with no text, QR, or values.
        $pdf->AddPage();
        $pdf->Image($backTemplate, 0, 0, $cardW, $cardH, 'PNG', '', '', false, 300);

        if (ob_get_length()) {
            ob_end_clean();
        }

        $content = $pdf->Output('student_id.pdf', 'S');




        return response($content)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="student_id.pdf"');
    }

    public function generateTeacherSecure(Request $request, string $hash): Response
    {
        if (!$request->hasValidSignature()) {
            abort(401, 'Invalid or expired signature.');
        }

        $id = $request->query('id');
        $teacher = User::findOrFail($id);

        if (!$teacher->role || strcasecmp((string) $teacher->role->name, 'Teacher') !== 0) {
            abort(404, 'Teacher not found.');
        }

        $expectedHash = md5($teacher->employee_id . '-teacher-' . config('app.key'));
        if ($hash !== $expectedHash) {
            abort(403, 'Invalid file reference.');
        }

        if (!class_exists(\TCPDF::class)) {
            return response()->json(['message' => 'TCPDF is not installed. Install tecnickcom/tcpdf via Composer.'], 500);
        }

        $templatesDir = IdCardImageService::templatesPath();
        if ($templatesDir === null) {
            return response()->json(['message' => 'ID templates folder not found. Expected public/TEMPLATE.'], 500);
        }

        $photoPath = null;
        if ($teacher->profile_photo) {
            $cleanPath = ltrim(str_replace(['public/', 'storage/'], '', (string)$teacher->profile_photo), '/');
            $candidate = public_path('storage/' . $cleanPath);
            if (is_file($candidate) && is_readable($candidate)) {
                $photoPath = $candidate;
            }
        }

        // Get Background
        $backgroundPath = IdCardImageService::eodbTemplatePath(null, null, null, $teacher->job_title);
        if (!$backgroundPath || !is_file($backgroundPath)) {
            return response()->json([
                'message' => 'Failed to generate Teacher ID. Ensure template exists in TEMPLATE folder (e.g. ' . $teacher->job_title . '.jpg/.png).',
            ], 500);
        }
        $backgroundDataUri = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($backgroundPath));

        // Get Photo
        $photoDataUri = null;
        if ($photoPath) {
            $photoExt = strtolower(pathinfo($photoPath, PATHINFO_EXTENSION));
            $photoMime = $photoExt === 'png' ? 'image/png' : 'image/jpeg';
            $photoDataUri = 'data:' . $photoMime . ';base64,' . base64_encode(file_get_contents($photoPath));
        }

        // Get QR Code
        $qrDataUri = null;
        $employeeId = $teacher->employee_id ?? '';
        if ($employeeId !== '') {
            if (class_exists(QrCode::class) && class_exists(PngWriter::class)) {
                $qrCode = new QrCode(data: $employeeId, size: 200, margin: 0);
                $writer = new PngWriter();
                $result = $writer->write($qrCode);
                $qrDataUri = $result->getDataUri();
            } else {
                abort(500, 'Endroid QR Code packages not found');
            }
        }

        $html = view()->file(
            app_path('Http/Controllers/Api/eodb-id-bb.blade.php'),
            [
                'background' => $backgroundDataUri,
                'photo' => $photoDataUri,
                'qr' => $qrDataUri,
                'name' => mb_strtoupper((string) ($teacher->name ?? '')),
                'job_title' => mb_strtoupper((string) ($teacher->job_title ?? '')),
                'employee_id' => $employeeId,
                'school_name' => mb_strtoupper((string) ($teacher->school_name ?? '')),
            ]
        )->render();

        // 1011x638 pixels -> exactly proportionate to 85.6x54 PVC
        $cardW = 85.6; 
        $cardH = 54.0; 
        
        // Use 'L' for Landscape, supplying correct width x height
        $pdf = new \TCPDF('L', 'mm', [$cardW, $cardH], true, 'UTF-8', false);
        $pdf->SetCreator('Ozamiz Schools QR-ID System');
        $pdf->SetTitle('Teacher ID');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->setImageScale(1.53); // Helps map 300DPI HTML scale precisely
        $pdf->AddPage();
        
        // Output the HTML
        $pdf->writeHTML($html, true, false, true, false, '');

        if (ob_get_length()) {
            ob_end_clean();
        }

        $content = $pdf->Output('teacher_id.pdf', 'S');

        return response($content)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="teacher_id.pdf"');
    }
}

