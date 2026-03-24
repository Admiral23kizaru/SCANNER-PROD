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
    private function formatNameWithMiddleInitial(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '';
        }

        $parts = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($parts) < 3) {
            return $name;
        }

        $first = array_shift($parts);
        $last = array_pop($parts);
        $middle = array_shift($parts);
        $middleInitial = $middle !== null && $middle !== ''
            ? strtoupper(function_exists('mb_substr') ? mb_substr($middle, 0, 1) : substr($middle, 0, 1)).'.'
            : '';

        return trim(implode(' ', array_filter([$first, $middleInitial, $last])));
    }

    private function formatEmergencyContactNumber(?string $number): string
    {
        $number = trim((string) $number);
        if ($number === '') {
            return '';
        }

        $digits = preg_replace('/\D+/', '', $number) ?? '';
        if (strlen($digits) === 11) {
            return substr($digits, 0, 4).'-'.substr($digits, 4, 3).'-'.substr($digits, 7, 4);
        }

        return $number;
    }

    private function getTemplatePageSize(string $templatePath, float $fallbackW, float $fallbackH): array
    {
        $imageSize = @getimagesize($templatePath);
        if (!is_array($imageSize) || empty($imageSize[0]) || empty($imageSize[1])) {
            return [$fallbackW, $fallbackH];
        }

        $imageW = (float) $imageSize[0];
        $imageH = (float) $imageSize[1];
        $dpiX = !empty($imageSize['resolution_x']) ? (float) $imageSize['resolution_x'] : 96.0;
        $dpiY = !empty($imageSize['resolution_y']) ? (float) $imageSize['resolution_y'] : 96.0;

        if ($dpiX <= 0) {
            $dpiX = 96.0;
        }
        if ($dpiY <= 0) {
            $dpiY = 96.0;
        }

        $pageW = $imageW * 25.4 / $dpiX;
        $pageH = $imageH * 25.4 / $dpiY;

        return [$pageW, $pageH];
    }

    private function renderEodbIdBbHtml(array $data): string
    {
        // Prefer the existing non-standard location used by this project, but allow fallbacks.
        $candidates = [
            app_path('Services/eodb-id-bb.blade.php'),
            resource_path('views/id-cards/eodb-id-bb.blade.php'),
            resource_path('views/eodb-id-bb.blade.php'),
        ];

        foreach ($candidates as $path) {
            if (is_string($path) && $path !== '' && is_file($path)) {
                return view()->file($path, $data)->render();
            }
        }

        // If the view exists in the standard Laravel view locations, use it.
        if (view()->exists('id-cards.eodb-id-bb')) {
            return view('id-cards.eodb-id-bb', $data)->render();
        }
        if (view()->exists('eodb-id-bb')) {
            return view('eodb-id-bb', $data)->render();
        }

        abort(500, 'Teacher ID template view not found. Expected app/Services/eodb-id-bb.blade.php or resources/views/id-cards/eodb-id-bb.blade.php');
    }

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

        $baseCardW = 85.6;
        $baseCardH = 54.0;
        [$templateW, $templateH] = $this->getTemplatePageSize($frontTemplate, $baseCardW, $baseCardH);
        $pageW = 148.0;
        $pageH = 105.0;
        $pageInsetScale = 0.97;
        $fitScale = min($pageW / $templateW, $pageH / $templateH) * $pageInsetScale;
        $cardW = $templateW * $fitScale;
        $cardH = $templateH * $fitScale;
        $templateX = ($pageW - $cardW) / 2.0;
        $templateY = ($pageH - $cardH) / 2.0;

        $pdf = new \TCPDF('L', 'mm', [$pageH, $pageW], true, 'UTF-8', false);
        $pdf->SetCreator('Ozamiz Schools QR-ID System');
        $pdf->SetAuthor('Ozamiz Schools QR-ID System');
        $pdf->SetTitle('Student ID');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false, 0);
        $scaleX = $cardW / $baseCardW;
        $scaleY = $cardH / $baseCardH;

        // Prepare Data
        $fullName = trim(implode(' ', array_filter([
            $student->first_name ?? null,
            $student->middle_name ?? null,
            $student->last_name ?? null,
        ])));
        $fullName = $fullName !== '' ? $fullName : '—';
        $lrn = (string) ($student->student_number ?? '');
        $guardian = $this->formatNameWithMiddleInitial($student->guardian);
        $contact = $this->formatEmergencyContactNumber($student->contact_number);

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
            'stretchtext' => 4,
            'fgcolor' => [0, 0, 0],
            'bgcolor' => false,
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
        $pdf->Image($frontTemplate, $templateX, $templateY, $cardW, $cardH, 'PNG', '', '', false, 300);
        $separatorX = $templateX + ($cardW / 2.0);
        $leftBlockX = $templateX + (2.0 * $scaleX);
        $leftBlockW = ($separatorX - $leftBlockX) - (2.0 * $scaleX);
        $rightBlockX = $separatorX + (2.0 * $scaleX);
        $rightBlockW = (($templateX + $cardW) - $rightBlockX) - (2.0 * $scaleX);

        // ── QR Code ──────────────────────────────────────────────────────
        $qrW = 20 * $scaleX;
        $qrH = 20 * $scaleY;
        $qrX = $leftBlockX + (($leftBlockW - $qrW) / 2.0);
        $pdf->write2DBarcode($qrPayload, 'QRCODE,M', $qrX, $templateY + (9.5 * $scaleY), $qrW, $qrH, $qrStyle, 'N');

        // ── Emergency Contact (left column) ──────────────────────────────
        $pdf->SetTextColor(0, 0, 0);

        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetXY($leftBlockX, $templateY + (33 * $scaleY));
        $pdf->Cell($leftBlockW, 4.2 * $scaleY, 'Incase of emergency, contact:', 0, 0, 'C');

        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY($leftBlockX, $templateY + (36 * $scaleY));
        $pdf->Cell($leftBlockW, 4.2 * $scaleY, $guardian !== '' ? $guardian : 'N/A', 0, 0, 'C');

        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY($leftBlockX, $templateY + (39 * $scaleY));
        $pdf->Cell($leftBlockW, 4.2 * $scaleY, $contact !== '' ? $contact : 'N/A', 0, 0, 'C');

       
        if ($photoPath) {
            $imgType = (strtolower(pathinfo($photoPath, PATHINFO_EXTENSION)) === 'png') ? 'PNG' : 'JPEG';
            $pdf->Image($photoPath, $templateX + (47.5 * $scaleX), $templateY + (15.5 * $scaleY), 16 * $scaleX, 17.5 * $scaleY, $imgType,
                        '', '', false, 300, '', false, false, 0, 'CT');
        }

        // ── Student Name (below photo, right column) ─────────────────────
        $lastNamePart    = mb_strtoupper(trim((string) ($student->last_name ?? ''))) . ',';
        $firstMiddlePart = mb_strtoupper(trim(implode(' ', array_filter([
            $student->first_name ?? null,
            $student->middle_name ?? null,
        ]))));

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->SetXY($rightBlockX, $templateY + (32.5 * $scaleY));
        $pdf->Cell($rightBlockW, 6.2 * $scaleY, $lastNamePart, 0, 0, 'C');

        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetXY($rightBlockX, $templateY + (37.3 * $scaleY));
        $pdf->Cell($rightBlockW, 5.0 * $scaleY, $firstMiddlePart, 0, 0, 'C');

        // ── Barcode (right column, below name) ───────────────────────────
        $barcodeW = $rightBlockW * 0.75;
        $barcodeX = $rightBlockX + (($rightBlockW - $barcodeW) / 2.0);
        $pdf->write1DBarcode($lrn, 'C39', $barcodeX, $templateY + (42.5 * $scaleY), $barcodeW, 5.5 * $scaleY, '', $barcodeStyle, 'N');

        // ── LRN text on bottom strip ─────────────────────────────────────
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetXY($rightBlockX, $templateY + (50.1 * $scaleY));
        $pdf->Cell($rightBlockW, 3.5 * $scaleY, 'LRN:' . $lrn, 0, 0, 'C');


        // ---------------- PAGE 2 (BACK) ----------------
        // Completely clean block: just the blank template with no text, QR, or values.
        $pdf->AddPage();
        $pdf->Image($backTemplate, $templateX, $templateY, $cardW, $cardH, 'PNG', '', '', false, 300);

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

        $html = $this->renderEodbIdBbHtml([
            'background' => $backgroundDataUri,
            'photo' => $photoDataUri,
            'qr' => $qrDataUri,
            'name' => mb_strtoupper((string) ($teacher->name ?? '')),
            'job_title' => mb_strtoupper((string) ($teacher->job_title ?? '')),
            'employee_id' => $employeeId,
            'school_name' => mb_strtoupper((string) ($teacher->school_name ?? '')),
        ]);

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
