<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;

class IdCardController extends Controller
{
    public function getSignedUrl(Request $request, int $id): JsonResponse
    {
        $student = Student::findOrFail($id);
        $user = $request->user();

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

        // FRONT: QR Code (Optimized: ECC 'M', 16x16mm which is ~189px max limit, higher contrast)
        $pdf->write2DBarcode($qrPayload, 'QRCODE,M', 11, 12, 16, 16, $qrStyle, 'N');

        // FRONT: Emergency Contact
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', 'B', 6);
        $pdf->SetXY(4, 32);
        $pdf->Cell(34, 0, 'In case of emergency, contact:', 0, 1, 'C');
        
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetXY(4, 36);
        $pdf->Cell(34, 0, $guardian !== '' ? mb_strtoupper($guardian) : 'N/A', 0, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetXY(4, 40);
        $pdf->Cell(34, 0, $contact !== '' ? $contact : 'N/A', 0, 1, 'C');

        // FRONT: Photo — look for .png first (canonical), fallback .jpg
        $photoPath = public_path('school/' . $student->student_number . '.png');
        if (!is_file($photoPath)) {
            $photoPath = public_path('school/' . $student->student_number . '.jpg');
            if (!is_file($photoPath)) {
                $photoPath = null;
            }
        }

        if ($photoPath) {
            // Use GD to remove white/near-white background so photo blends with ID template
            $ext = strtolower(pathinfo($photoPath, PATHINFO_EXTENSION));
            $srcImg = null;
            if ($ext === 'png' && function_exists('imagecreatefrompng')) {
                $srcImg = @imagecreatefrompng($photoPath);
            } elseif (in_array($ext, ['jpg', 'jpeg']) && function_exists('imagecreatefromjpeg')) {
                $srcImg = @imagecreatefromjpeg($photoPath);
            }

            $finalPhotoPath = $photoPath; // fallback: use original
            $tempFile = null;

            if ($srcImg) {
                $w = imagesx($srcImg);
                $h = imagesy($srcImg);
                // Create output canvas with alpha support
                $outImg = imagecreatetruecolor($w, $h);
                imagealphablending($outImg, false);
                imagesavealpha($outImg, true);
                $transparent = imagecolorallocatealpha($outImg, 255, 255, 255, 127);
                imagefill($outImg, 0, 0, $transparent);
                imagealphablending($outImg, true);

                // Copy source, then flood-fill near-white pixels with transparency
                imagecopy($outImg, $srcImg, 0, 0, 0, 0, $w, $h);

                // Remove near-white background: scan edges + flood-fill
                $threshold = 30; // tolerance: pixels within 30 of white (225-255 per channel) become transparent
                for ($y = 0; $y < $h; $y++) {
                    for ($x = 0; $x < $w; $x++) {
                        $rgb = imagecolorat($outImg, $x, $y);
                        $r = ($rgb >> 16) & 0xFF;
                        $g = ($rgb >> 8) & 0xFF;
                        $b = $rgb & 0xFF;
                        $a = ($rgb >> 24) & 0x7F;
                        // Skip already transparent pixels
                        if ($a > 100) continue;
                        // If near-white, make transparent
                        if ($r >= (255 - $threshold) && $g >= (255 - $threshold) && $b >= (255 - $threshold)) {
                            imagesetpixel($outImg, $x, $y, $transparent);
                        }
                    }
                }

                imagealphablending($outImg, false);
                imagesavealpha($outImg, true);

                // Save cleaned image to temp file
                $tempFile = sys_get_temp_dir() . '/id_photo_' . $student->student_number . '_' . time() . '.png';
                if (imagepng($outImg, $tempFile)) {
                    $finalPhotoPath = $tempFile;
                }
                imagedestroy($srcImg);
                imagedestroy($outImg);
            }

            // Place photo: adjusted position & size to match reference layout
            // x=41, y=3.5 → width=22mm, height=27mm (portrait photo over the ribbon area)
            $pdf->Image($finalPhotoPath, 41.0, 3.5, 22.0, 27.0, 'PNG', '', '', false, 300, '', false, false, 0, 'CT');

            // Clean up temp file after PDF is built
            if ($tempFile && is_file($tempFile)) {
                @unlink($tempFile);
            }
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
}

