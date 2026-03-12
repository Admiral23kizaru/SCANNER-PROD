<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class IdCardImageService
{
    /**
     * Resolve the templates directory (same logic as IdCardController).
     * Tries: config path, public/id-card-templates, project/TEMPLATE ID/TEMPLATE, sibling ../TEMPLATE ID/TEMPLATE.
     */
    public static function templatesPath(): ?string
    {
        $baseTemplates = base_path('TEMPLATE');
        if (is_dir($baseTemplates)) {
            return $baseTemplates;
        }

        return null;
    }

    /**
     * Get the full path to the EODB ID BB template PNG.
     * Selection: role (so System Admin etc. get correct template) → job → employment status → fallback.
     *
     * @param  string|null  $employStatus  e.g. "Casual", "Permanent"
     * @param  string|null  $jobShorten  e.g. "TRAINEE" — if a file {jobShorten}.png exists, use it
     * @param  string|null  $role  e.g. "System Admin" — if mapped in role_to_template, that template is used first
     */
    public static function eodbTemplatePath(?string $employStatus = null, ?string $jobShorten = null, ?string $role = null, ?string $jobTitle = null): ?string
    {
        $dir = self::templatesPath();
        if ($dir === null) {
            return null;
        }

        $tryFile = static function (string $filename) use ($dir): ?string {
            $path = $dir.DIRECTORY_SEPARATOR.$filename;

            return File::isFile($path) ? $path : null;
        };

        // 1) Role-based: so "System Admin" / "Admin System" etc. get the right template
        if ($role !== null && $role !== '') {
            $roleMap = config('id-card.role_to_template', []);
            $filename = $roleMap[$role] ?? null;
            if ($filename === null && is_array($roleMap)) {
                foreach ($roleMap as $key => $val) {
                    if (strcasecmp($key, $role) === 0) {
                        $filename = $val;
                        break;
                    }
                }
            }
            if ($filename !== null) {
                $path = $tryFile($filename);
                if ($path !== null) {
                    return $path;
                }
            }

            $roleMatched = self::findTemplateByLabel($dir, $role);
            if ($roleMatched !== null) {
                return $roleMatched;
            }
        }

        // 2) Job-based override: if job_shorten template exists (e.g. TRAINEE.png), use it
        if ($jobShorten !== null && $jobShorten !== '') {
            $path = $tryFile($jobShorten.'.jpg')
                ?? $tryFile($jobShorten.'.jpeg')
                ?? $tryFile($jobShorten.'.png')
                ?? $tryFile($jobShorten);
            if ($path !== null) {
                return $path;
            }
        }

        // 2b) Job-title override: allow explicit DB short-code mapping (e.g. ADASIII → ADASIII.jpg/png)
        if ($jobTitle !== null && $jobTitle !== '') {
            $path = $tryFile($jobTitle.'.jpg')
                ?? $tryFile($jobTitle.'.jpeg')
                ?? $tryFile($jobTitle.'.png')
                ?? $tryFile($jobTitle);
            if ($path !== null) {
                return $path;
            }
        }

        // 3) By employment status: Casual → contractual, Permanent → regular, else official
        $byStatus = config('id-card.eodb_by_status', []);
        if ($employStatus !== null && $employStatus !== '' && is_array($byStatus)) {
            $filename = $byStatus[$employStatus] ?? $byStatus['default'] ?? null;
            if ($filename !== null) {
                $path = $tryFile($filename);
                if ($path !== null) {
                    return $path;
                }
            }
        }
        if (is_array($byStatus) && isset($byStatus['default'])) {
            $path = $tryFile($byStatus['default']);
            if ($path !== null) {
                return $path;
            }
        }

        // 4) Config fallback filename
        $preferred = config('id-card.eodb_id_bb_template', 'EODBBB.png');
        $path = $tryFile($preferred);
        if ($path !== null) {
            return $path;
        }

        // 5) First PNG in directory
        $files = File::files($dir);
        foreach ($files as $file) {
            $path = is_string($file) ? $file : $file->getPathname();
            if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'png') {
                return $path;
            }
        }

        return null;
    }

    private static function findTemplateByLabel(string $dir, string $label): ?string
    {
        $needle = self::normalizeTemplateToken($label);
        if ($needle === '') {
            return null;
        }

        foreach (File::files($dir) as $file) {
            $path = is_string($file) ? $file : $file->getPathname();
            if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'png') {
                continue;
            }

            $base = pathinfo($path, PATHINFO_FILENAME);
            $token = self::normalizeTemplateToken($base);
            if ($token === $needle || str_contains($token, $needle) || str_contains($needle, $token)) {
                return $path;
            }
        }

        return null;
    }

    private static function normalizeTemplateToken(string $value): string
    {
        $upper = strtoupper(trim($value));

        return preg_replace('/[^A-Z0-9]+/', '', $upper) ?? '';
    }

    /**
     * Build filename candidates from a role label.
     * Example: "Accounting III" -> ["ACCOUNTINGIII", "ACTIII"].
     *
     * @return array<int, string>
     */
    private static function roleTemplateCandidates(string $role): array
    {
        $role = trim($role);
        if ($role === '') {
            return [];
        }

        $normalized = self::normalizeTemplateToken($role);
        $parts = preg_split('/\s+/', strtoupper($role), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($parts === []) {
            return $normalized !== '' ? [$normalized] : [];
        }

        $suffix = '';
        $last = preg_replace('/[^A-Z0-9]/', '', (string) end($parts)) ?? '';
        if ($last !== '' && preg_match('/^(?:[IVXLCDM]+|\d+)$/', $last) === 1) {
            $suffix = $last;
            array_pop($parts);
        }

        $firstWord = preg_replace('/[^A-Z]/', '', (string) ($parts[0] ?? '')) ?? '';
        $abbr = $firstWord !== '' ? substr($firstWord, 0, 3).$suffix : '';
        $abbr = preg_replace('/[^A-Z0-9]/', '', $abbr) ?? '';

        $candidates = [];
        if ($normalized !== '') {
            $candidates[] = $normalized;
        }
        if ($abbr !== '' && ! in_array($abbr, $candidates, true)) {
            $candidates[] = $abbr;
        }

        return $candidates;
    }

    /**
     * Check whether the GD extension is available (required for image generation).
     */
    public static function gdAvailable(): bool
    {
        return \function_exists('imagecreatefrompng');
    }

    /**
     * Build EODB ID card image (same approach as legacy eodb_idBB.php):
     * 1. Load template PNG (by role → job → employment status).
     * 2. Overlay variable data: name, division, ID no, role label, photo, QR code.
     * The card design (seal, colors, layout) comes from the template; PHP only overlays data.
     *
     * @param  array{fullname: string, employee_id: string, division: string, photo_path: string|null, signature_path?: string|null, employ_status?: string|null, job_shorten?: string|null, job_title?: string|null, role?: string|null}  $data
     */
    public static function buildEodbCard(array $data): ?string
    {
        if (! self::gdAvailable()) {
            throw new \RuntimeException(
                'The PHP GD extension is required to generate ID card images. Enable it in php.ini (e.g. extension=gd) and restart the web server.'
            );
        }

        $templatePath = self::eodbTemplatePath(
            $data['employ_status'] ?? null,
            $data['job_shorten'] ?? null,
            $data['role'] ?? null,
            $data['job_title'] ?? null
        );
        if ($templatePath === null || ! File::isFile($templatePath)) {
            return null;
        }

        $img = self::loadImage($templatePath);
        if ($img === null) {
            return null;
        }

        $w = \imagesx($img);
        $h = \imagesy($img);
        if ($w <= 0 || $h <= 0) {
            \imagedestroy($img);

            return null;
        }

        $black = \imagecolorallocate($img, 0, 0, 0);
        if ($black === false) {
            $black = \imagecolorallocate($img, 1, 1, 1);
        }
        $white = \imagecolorallocate($img, 255, 255, 255);

        $lastName = strtoupper(trim((string) ($data['lastname'] ?? '')));
        $firstName = strtoupper(trim((string) ($data['firstname'] ?? '')));
        $middleName = strtoupper(trim((string) ($data['middlename'] ?? '')));
        $extension = strtoupper(trim((string) ($data['extension'] ?? '')));
        $middleInitial = '';
        if ($middleName !== '') {
            $middleInitial = (function_exists('mb_substr') ? mb_substr($middleName, 0, 1) : substr($middleName, 0, 1)).'.';
        }
        $firstMiddle = trim(implode(' ', array_filter([$firstName, $middleInitial])));

        // Fallback for older callers that still pass fullname only.
        if ($lastName === '' && $firstMiddle === '') {
            $fullname = trim((string) ($data['fullname'] ?? ''));
            if ($fullname !== '') {
                $parts = preg_split('/\s+/', $fullname, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                if (count($parts) >= 2) {
                    $lastName = strtoupper((string) array_pop($parts));
                    $firstMiddle = strtoupper(implode(' ', $parts));
                } else {
                    $lastName = strtoupper($fullname);
                }
            }
        }
        // Display as "LASTNAME," like the TRAINEE ID
        if ($lastName !== '') {
            $lastName .= ',';
        }
        if ($extension !== '') {
            $firstMiddle = trim($firstMiddle.' '.$extension);
        }

        $employeeId = trim($data['employee_id'] ?? '');
        $departmentAbbrev = strtoupper(trim((string) ($data['department_abbrev'] ?? '')));
        $division = trim($data['division'] ?? 'DIVISION OFFICE');
        $photoPath = isset($data['photo_path']) && $data['photo_path'] !== '' ? $data['photo_path'] : null;
        $signaturePath = isset($data['signature_path']) && $data['signature_path'] !== '' ? $data['signature_path'] : null;
        $roleLabel = trim($data['role'] ?? '');
        if ($roleLabel !== '') {
            $roleLabel = strtoupper(preg_replace('/\s+/', ' ', $roleLabel));
        }
        // Legacy: Casual = black text, else (Permanent/official/red template) = white text so it's visible
        $employStatus = isset($data['employ_status']) ? (string) $data['employ_status'] : null;
        $textColor = (strcasecmp($employStatus ?? '', 'Casual') === 0) ? $black : $white;

        // Name text style (TTF): separate sizes for LASTNAME and FIRSTNAME/MI.
        $lastNameFontSize = 85;
        $firstMiddleFontSize = 64;
        $employeeIdFontSize = 40;
        $departmentAbbrevFontSize = 30;
        // Simulated bold weight for LASTNAME (draw count). Increase for heavier bold.
        $lastNameBoldWeight = 8;
        // Simulated bold weight for FIRSTNAME/MI and EMPLOYEE ID.
        $firstMiddleBoldWeight = 2;
        $employeeIdBoldWeight = 4;
        $departmentAbbrevBoldWeight = 4;
        // Vertical gap between LASTNAME and FIRSTNAME/MI lines.
        $nameLineGap = 115;
        // Font path priority: config -> public/fonts -> Windows Arial.
        $ttfFontPath = config('id-card.name_ttf_font');
        if (! is_string($ttfFontPath) || trim($ttfFontPath) === '') {
            $ttfFontPath = public_path('fonts/arial.ttf');
        } elseif (! str_starts_with($ttfFontPath, '/') && ! preg_match('#^[A-Za-z]:#', $ttfFontPath)) {
            $ttfFontPath = base_path($ttfFontPath);
        }
        if (! is_string($ttfFontPath) || ! File::isFile($ttfFontPath)) {
            $windowsArial = 'C:\\Windows\\Fonts\\arial.ttf';
            $ttfFontPath = File::isFile($windowsArial) ? $windowsArial : null;
        }

        // Two cards side-by-side: template width is often 2× single card
        $twoCardRatioThreshold = (float) config('id-card.two_card_ratio_threshold', 1.4);
        $autoTwoCards = $w >= (int) round($h * $twoCardRatioThreshold);
        $forceTwoCardsRaw = $data['force_two_cards'] ?? config('id-card.force_two_cards');
        $forceTwoCards = null;
        if (is_bool($forceTwoCardsRaw)) {
            $forceTwoCards = $forceTwoCardsRaw;
        } elseif (is_string($forceTwoCardsRaw)) {
            $flag = strtolower(trim($forceTwoCardsRaw));
            if (in_array($flag, ['1', 'true', 'yes', 'on'], true)) {
                $forceTwoCards = true;
            } elseif (in_array($flag, ['0', 'false', 'no', 'off'], true)) {
                $forceTwoCards = false;
            }
        } elseif (is_int($forceTwoCardsRaw)) {
            $forceTwoCards = $forceTwoCardsRaw !== 0;
        }
        $twoCards = $forceTwoCards ?? $autoTwoCards;
        $cardWidth = $twoCards ? (int) round($w / 2) : $w;
        $detectedCenterDividerWidth = $twoCards ? self::detectCenterDividerWidth($img, $w, $h) : 0;
        // MANUAL POSITION: fine-tune all overlays on the 2nd/right card (positive = move right, negative = move left).
        $secondCardOffsetAdjustX = 0;
        $secondCardOffsetX = $cardWidth + (int) round($detectedCenterDividerWidth / 2) + $secondCardOffsetAdjustX;

        $drawCard = static function (int $offsetX) use (
            $img, $cardWidth, $h, $textColor, $lastName, $firstMiddle, $employeeId, $departmentAbbrev,
            $lastNameFontSize, $firstMiddleFontSize, $employeeIdFontSize, $departmentAbbrevFontSize,
            $lastNameBoldWeight, $firstMiddleBoldWeight, $employeeIdBoldWeight, $departmentAbbrevBoldWeight, $nameLineGap, $ttfFontPath
        ) {
            // MANUAL POSITION: adjust horizontal start of the employee name text.
            $marginLeft = $offsetX + (int) round($cardWidth * 0.056);
            // MANUAL POSITION: adjust vertical position of employee name text.
            $nameY = (int) round($h * 0.45);
            // MANUAL POSITION: adjust employee ID X/Y placement.
            $employeeIdX = $offsetX + (int) round($cardWidth * 0.056);
            $employeeIdY = (int) round($h * 0.63);
            // MANUAL POSITION: adjust department abbreviation X/Y placement.
            $departmentAbbrevX = $offsetX + (int) round($cardWidth * 0.056);
            $departmentAbbrevY = (int) round($h * 0.55);

            // Use TrueType rendering so name size can be controlled freely.
            if ($ttfFontPath !== null && function_exists('imagettftext')) {
                if ($lastName !== '') {
                    for ($i = 0; $i < max(1, $lastNameBoldWeight); $i++) {
                        \imagettftext($img, $lastNameFontSize, 0, $marginLeft + $i, $nameY, $textColor, $ttfFontPath, $lastName);
                    }
                }
                if ($firstMiddle !== '') {
                    for ($i = 0; $i < max(1, $firstMiddleBoldWeight); $i++) {
                        \imagettftext($img, $firstMiddleFontSize, 0, $marginLeft + $i, $nameY + $nameLineGap, $textColor, $ttfFontPath, $firstMiddle);
                    }
                }
                if ($employeeId !== '') {
                    for ($i = 0; $i < max(1, $employeeIdBoldWeight); $i++) {
                        \imagettftext($img, $employeeIdFontSize, 0, $employeeIdX + $i, $employeeIdY, $textColor, $ttfFontPath, 'ID NO. '.$employeeId);
                    }
                }
                if ($departmentAbbrev !== '') {
                    for ($i = 0; $i < max(1, $departmentAbbrevBoldWeight); $i++) {
                        \imagettftext($img, $departmentAbbrevFontSize, 0, $departmentAbbrevX + $i, $departmentAbbrevY, $textColor, $ttfFontPath, $departmentAbbrev);
                    }
                }

                return;
            }

            // Fallback if no TTF font is available.
            if ($lastName !== '') {
                \imagestring($img, 5, $marginLeft, $nameY, $lastName, $textColor);
            }
            if ($firstMiddle !== '') {
                \imagestring($img, 5, $marginLeft, $nameY + 12, $firstMiddle, $textColor);
            }
            if ($employeeId !== '') {
                \imagestring($img, 5, $employeeIdX, $employeeIdY, 'ID NO. '.$employeeId, $textColor);
            }
            if ($departmentAbbrev !== '') {
                \imagestring($img, 5, $departmentAbbrevX, $departmentAbbrevY, $departmentAbbrev, $textColor);
            }
        };

        $pocketOverlayPath = isset($data['pocket_overlay_path']) && $data['pocket_overlay_path'] !== '' ? $data['pocket_overlay_path'] : null;
        $hasPocketOverlay = $pocketOverlayPath !== null && File::isFile($pocketOverlayPath) && $twoCards;

        $drawCard(0);
        if ($twoCards && ! $hasPocketOverlay) {
            $drawCard($secondCardOffsetX);
        }

        // Photo: draw once centered on first card, and on second card if two-card layout (unless pocket overlay).
        if ($photoPath !== null && File::isFile($photoPath)) {
            $photo = self::loadImage($photoPath);
            if ($photo !== null) {
                $photoW = \imagesx($photo);
                $photoH = \imagesy($photo);
                // MANUAL SIZE: adjust employee photo width and height.
                $dstW = (int) round($cardWidth * 0.37);
                $dstH = (int) round($h * 0.29);
                // MANUAL POSITION: adjust employee photo X/Y placement.
                $dstX = (int) round($cardWidth * 0.61);
                $dstY = (int) round($h * 0.6375);
                \imagecopyresampled($img, $photo, $dstX, $dstY, 0, 0, $dstW, $dstH, $photoW, $photoH);
                if ($twoCards && ! $hasPocketOverlay) {
                    \imagecopyresampled($img, $photo, $secondCardOffsetX + $dstX, $dstY, 0, 0, $dstW, $dstH, $photoW, $photoH);
                }
                \imagedestroy($photo);
            }
        }

        // QR code: employee_id, one per card (unless pocket overlay on 2nd card).
        if ($employeeId !== '') {
            // MANUAL SIZE: adjust QR size.
            $qrSize = (int) round(min($cardWidth, $h) * 0.15);
            // MANUAL POSITION: adjust QR X/Y placement.
            $qrX1 = (int) round($cardWidth * 0.14);
            $qrY = (int) round($h * 0.711);

            $qrImg = self::createQrCodeImage($employeeId, $qrSize);
            if ($qrImg !== null) {
                $qrW = \imagesx($qrImg);
                $qrH = \imagesy($qrImg);
                \imagecopyresampled($img, $qrImg, $qrX1, $qrY, 0, 0, $qrSize, $qrSize, $qrW, $qrH);
                if ($twoCards && ! $hasPocketOverlay) {
                    \imagecopyresampled($img, $qrImg, $secondCardOffsetX + $qrX1, $qrY, 0, 0, $qrSize, $qrSize, $qrW, $qrH);
                }
                \imagedestroy($qrImg);
            }
        }

        // Pocket overlay: replace the 2nd card with pocket.png, aligned to the same offset.
        if ($hasPocketOverlay) {
            $pocket = @\imagecreatefrompng($pocketOverlayPath);
            if ($pocket !== false) {
                $pocketW = \imagesx($pocket);
                $pocketH = \imagesy($pocket);
                // MANUAL POSITION: adjust pocket overlay X/Y placement (positive = right/down).
                $pocketAdjustX = 5;
                $pocketAdjustY = -5;
                // MANUAL SIZE: adjust pocket overlay width/height (0 = use card dimensions).
                $pocketAdjustW = -36;
                $pocketAdjustH = -90;
                $pocketDstX = $secondCardOffsetX + $pocketAdjustX;
                $pocketDstY = $pocketAdjustY;
                $pocketDstW = $cardWidth + $pocketAdjustW;
                $pocketDstH = $h + $pocketAdjustH;
                \imagecopyresampled($img, $pocket, $pocketDstX, $pocketDstY, 0, 0, $pocketDstW, $pocketDstH, $pocketW, $pocketH);
                \imagedestroy($pocket);

                // Pocket back data overlay (does not affect front placement).
                $pocketBackFields = (isset($data['pocket_back_fields']) && is_array($data['pocket_back_fields'])) ? $data['pocket_back_fields'] : [];
                $pocketBackLogoPath = isset($data['pocket_back_logo_path']) && is_string($data['pocket_back_logo_path']) ? $data['pocket_back_logo_path'] : null;

                // MANUAL POSITION (POCKET BACK): grouped employee data block style and anchor.
                $infoColor = $black;
                $infoX = $pocketDstX + (int) round($pocketDstW * 0.11);
                $infoY = $pocketDstY + (int) round($pocketDstH * 0.55);
                $lineGap = (int) round($pocketDstH * 0.036);
                $infoFontSize = max(10, (int) round($pocketDstH * 0.0165));
                $infoFallbackFont = 3;

                // MANUAL POSITION (POCKET BACK): superintendent signature (above name).
                $superintendentSignX = $pocketDstX + (int) round($pocketDstW * 0.3);
                $superintendentSignY = $pocketDstY + (int) round($pocketDstH * 0.20);
                $superintendentSignW = (int) round($pocketDstW * 0.44);
                $superintendentSignH = (int) round($pocketDstH * 0.085);

                // MANUAL POSITION (POCKET BACK): superintendent name.
                $superintendentX = $pocketDstX + (int) round($pocketDstW * 0.30);
                $superintendentY = $pocketDstY + (int) round($pocketDstH * 0.30);
                $superintendentFontSize = max(10, (int) round($pocketDstH * 0.021));
                $superintendentBoldWeight = 4;
                $superintendentFallbackFont = 3;

                // MANUAL POSITION (POCKET BACK): superintendent title (below name).
                $superintendentTitleX = $pocketDstX + (int) round($pocketDstW * 0.21);
                $superintendentTitleY = $pocketDstY + (int) round($pocketDstH * 0.325);
                $superintendentTitleFontSize = max(10, (int) round($pocketDstH * 0.015));
                $superintendentTitleFallbackFont = 2;

                // MANUAL POSITION (POCKET BACK): emergency-contact line (separate from grouped list).
                $emergencyX = $pocketDstX + (int) round($pocketDstW * 0.3667);
                $emergencyY = $pocketDstY + (int) round($pocketDstH * 0.43);
                $emergencyFontSize = max(10, (int) round($pocketDstH * 0.022));
                $emergencyFallbackFont = 3;

                // MANUAL POSITION (POCKET BACK): cardholder signature (requesting employee).
                $cardholderSignY = $pocketDstY + (int) round($pocketDstH * 0.845);
                $cardholderSignW = (int) round($pocketDstW * 0.32);
                $cardholderSignH = (int) round($pocketDstH * 0.06);
                // Centered horizontally within the pocket card.
                $cardholderSignX = $pocketDstX + (int) round(($pocketDstW - $cardholderSignW) / 2);

                // MANUAL POSITION (POCKET BACK): cardholder name (below cardholder signature).
                $cardholderNameY = $pocketDstY + (int) round($pocketDstH * 0.915);
                $cardholderNameFontSize = max(10, (int) round($pocketDstH * 0.019));
                $cardholderNameFallbackFont = 2;
                // MANUAL STYLE (POCKET BACK): increase for heavier cardholder name.
                $cardholderNameBoldWeight = 4;

                $superintendentLine = trim((string) ($pocketBackFields['superintendent_name'] ?? ''));
                $superintendentTitleLine = trim((string) ($pocketBackFields['superintendent_job_title'] ?? ''));
                $superintendentSignPath = trim((string) ($pocketBackFields['superintendent_signature_path'] ?? ''));
                // Emergency contact should render as raw value only (number only, no label).
                $emergencyLine = trim((string) ($pocketBackFields['emergency_contact'] ?? ''));
                $cardholderNameLine = trim((string) implode(' ', array_filter([
                    (string) ($data['firstname'] ?? ''),
                    (string) ($data['middlename'] ?? ''),
                    (string) ($data['lastname'] ?? ''),
                    (string) ($data['extension'] ?? ''),
                ], static fn ($v) => trim($v) !== '')));
                if ($cardholderNameLine === '') {
                    $cardholderNameLine = trim((string) ($data['fullname'] ?? ''));
                }
                if ($cardholderNameLine !== '') {
                    $cardholderNameLine = strtoupper($cardholderNameLine);
                }

                $infoLines = [
                    ['label' => 'STATION NO.', 'value' => trim((string) ($pocketBackFields['station_no'] ?? ''))],
                    ['label' => 'TIN', 'value' => trim((string) ($pocketBackFields['tin'] ?? ''))],
                    ['label' => 'GSIS', 'value' => trim((string) ($pocketBackFields['gsis'] ?? ''))],
                    ['label' => 'PAG-IBIG', 'value' => trim((string) ($pocketBackFields['pag_ibig'] ?? ''))],
                    ['label' => 'PHILHEALTH', 'value' => trim((string) ($pocketBackFields['philhealth'] ?? ''))],
                    ['label' => 'BIRTHDATE', 'value' => trim((string) ($pocketBackFields['birth_date'] ?? ''))],
                    ['label' => 'BLOOD TYPE', 'value' => trim((string) ($pocketBackFields['blood_type'] ?? ''))],
                ];
                // MANUAL POSITION: X offset for the value column (": <data>"), relative to pocket left.
                $infoValueX = $pocketDstX + (int) round($pocketDstW * 0.42);

                if ($superintendentSignPath !== '' && is_file($superintendentSignPath)) {
                    $superintendentSign = self::loadImage($superintendentSignPath);
                    if ($superintendentSign !== null) {
                        $superintendentSignSrcW = \imagesx($superintendentSign);
                        $superintendentSignSrcH = \imagesy($superintendentSign);
                        if ($superintendentSignSrcW > 0 && $superintendentSignSrcH > 0) {
                            \imagealphablending($img, true);
                            \imagesavealpha($img, true);
                            \imagecopyresampled(
                                $img,
                                $superintendentSign,
                                $superintendentSignX,
                                $superintendentSignY,
                                0,
                                0,
                                $superintendentSignW,
                                $superintendentSignH,
                                $superintendentSignSrcW,
                                $superintendentSignSrcH
                            );
                        }
                        \imagedestroy($superintendentSign);
                    }
                }
                if ($signaturePath !== null && is_file($signaturePath)) {
                    $cardholderSign = self::loadImage($signaturePath);
                    if ($cardholderSign !== null) {
                        $cardholderSignSrcW = \imagesx($cardholderSign);
                        $cardholderSignSrcH = \imagesy($cardholderSign);
                        if ($cardholderSignSrcW > 0 && $cardholderSignSrcH > 0) {
                            \imagealphablending($img, true);
                            \imagesavealpha($img, true);
                            \imagecopyresampled(
                                $img,
                                $cardholderSign,
                                $cardholderSignX,
                                $cardholderSignY,
                                0,
                                0,
                                $cardholderSignW,
                                $cardholderSignH,
                                $cardholderSignSrcW,
                                $cardholderSignSrcH
                            );
                        }
                        \imagedestroy($cardholderSign);
                    }
                }

                if ($ttfFontPath !== null && function_exists('imagettftext')) {
                    if ($superintendentLine !== '') {
                        for ($i = 0; $i < max(1, $superintendentBoldWeight); $i++) {
                            \imagettftext($img, $superintendentFontSize, 0, $superintendentX + $i, $superintendentY, $infoColor, $ttfFontPath, strtoupper($superintendentLine));
                        }
                    }
                    if ($superintendentTitleLine !== '') {
                        \imagettftext($img, $superintendentTitleFontSize, 0, $superintendentTitleX, $superintendentTitleY, $infoColor, $ttfFontPath, strtoupper($superintendentTitleLine));
                    }
                    if ($emergencyLine !== '') {
                        \imagettftext($img, $emergencyFontSize, 0, $emergencyX, $emergencyY, $infoColor, $ttfFontPath, $emergencyLine);
                    }
                    foreach ($infoLines as $idx => $entry) {
                        $y = $infoY + ($idx * $lineGap);
                        \imagettftext($img, $infoFontSize, 0, $infoX, $y, $infoColor, $ttfFontPath, $entry['label']);
                        \imagettftext($img, $infoFontSize, 0, $infoValueX, $y, $infoColor, $ttfFontPath, ':  '.$entry['value']);
                    }
                    if ($cardholderNameLine !== '') {
                        $bbox = \imagettfbbox($cardholderNameFontSize, 0, $ttfFontPath, $cardholderNameLine);
                        $textWidth = ($bbox !== false) ? abs($bbox[2] - $bbox[0]) : 0;
                        $cardholderNameX = $pocketDstX + (int) round(($pocketDstW - $textWidth) / 2);
                        for ($i = 0; $i < max(1, $cardholderNameBoldWeight); $i++) {
                            \imagettftext($img, $cardholderNameFontSize, 0, $cardholderNameX + $i, $cardholderNameY, $infoColor, $ttfFontPath, $cardholderNameLine);
                        }
                    }
                } else {
                    if ($superintendentLine !== '') {
                        for ($i = 0; $i < max(1, $superintendentBoldWeight); $i++) {
                            \imagestring($img, $superintendentFallbackFont, $superintendentX + $i, $superintendentY, strtoupper($superintendentLine), $infoColor);
                        }
                    }
                    if ($superintendentTitleLine !== '') {
                        \imagestring($img, $superintendentTitleFallbackFont, $superintendentTitleX, $superintendentTitleY, strtoupper($superintendentTitleLine), $infoColor);
                    }
                    if ($emergencyLine !== '') {
                        \imagestring($img, $emergencyFallbackFont, $emergencyX, $emergencyY, $emergencyLine, $infoColor);
                    }
                    foreach ($infoLines as $idx => $entry) {
                        $y = $infoY + ($idx * $lineGap);
                        \imagestring($img, $infoFallbackFont, $infoX, $y, $entry['label'], $infoColor);
                        \imagestring($img, $infoFallbackFont, $infoValueX, $y, ':  '.$entry['value'], $infoColor);
                    }
                    if ($cardholderNameLine !== '') {
                        $textWidth = \imagefontwidth($cardholderNameFallbackFont) * strlen($cardholderNameLine);
                        $cardholderNameX = $pocketDstX + (int) round(($pocketDstW - $textWidth) / 2);
                        for ($i = 0; $i < max(1, $cardholderNameBoldWeight); $i++) {
                            \imagestring($img, $cardholderNameFallbackFont, $cardholderNameX + $i, $cardholderNameY, $cardholderNameLine, $infoColor);
                        }
                    }
                }

                // MANUAL POSITION (POCKET BACK): logo placement/size.
                if ($pocketBackLogoPath !== null && File::isFile($pocketBackLogoPath)) {
                    $logo = self::loadImage($pocketBackLogoPath);
                    if ($logo !== null) {
                        $logoSrcW = \imagesx($logo);
                        $logoSrcH = \imagesy($logo);
                        $logoDstW = (int) round($pocketDstW * 0.56);
                        $logoDstH = (int) round($pocketDstH * 0.066);
                        $logoDstX = $pocketDstX + (int) round($pocketDstW * 0.22);
                        $logoDstY = $pocketDstY + (int) round($pocketDstH * 0.125);
                        \imagecopyresampled($img, $logo, $logoDstX, $logoDstY, 0, 0, $logoDstW, $logoDstH, $logoSrcW, $logoSrcH);
                        \imagedestroy($logo);
                    }
                }
            }
        }

        ob_start();
        \imagepng($img);
        $png = ob_get_clean();
        \imagedestroy($img);

        return $png ?: null;
    }

    private static function loadImage(string $path)
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'png') {
            return @\imagecreatefrompng($path) ?: null;
        }
        if (in_array($ext, ['jpg', 'jpeg'], true)) {
            return @\imagecreatefromjpeg($path) ?: null;
        }
        if ($ext === 'gif') {
            return @\imagecreatefromgif($path) ?: null;
        }

        return null;
    }

    /**
     * Create a QR code as a GD image resource (for pasting on the card).
     * Uses endroid/qr-code if available; returns null otherwise.
     */
    private static function createQrCodeImage(string $data, int $size): mixed
    {
        if (! class_exists(\Endroid\QrCode\QrCode::class)) {
            return null;
        }
        try {
            $qrCode = new \Endroid\QrCode\QrCode(
                data: $data,
                size: $size,
                margin: 2
            );
            $writer = new \Endroid\QrCode\Writer\PngWriter;
            $result = $writer->write($qrCode);
            $png = $result->getString();
            $res = @\imagecreatefromstring($png);

            return $res ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Detect approximate central divider width (light vertical band around center).
     */
    private static function detectCenterDividerWidth($img, int $w, int $h): int
    {
        if ($w <= 0 || $h <= 0) {
            return 0;
        }

        $centerX = (int) floor($w / 2);
        $searchRadius = max(6, (int) round($w * 0.07));
        $startX = max(0, $centerX - $searchRadius);
        $endX = min($w - 1, $centerX + $searchRadius);
        $minBrightRatio = 0.70;

        $isBrightColumn = static function (int $x) use ($img, $h, $minBrightRatio): bool {
            $bright = 0;
            for ($y = 0; $y < $h; $y++) {
                $rgb = @\imagecolorat($img, $x, $y);
                if ($rgb === false) {
                    continue;
                }
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                if ($r >= 240 && $g >= 240 && $b >= 240) {
                    $bright++;
                }
            }

            return ($bright / max(1, $h)) >= $minBrightRatio;
        };

        if (! $isBrightColumn($centerX)) {
            return 0;
        }

        $left = $centerX;
        while ($left > $startX && $isBrightColumn($left - 1)) {
            $left--;
        }

        $right = $centerX;
        while ($right < $endX && $isBrightColumn($right + 1)) {
            $right++;
        }

        return max(0, $right - $left + 1);
    }
}
