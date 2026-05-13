$ErrorActionPreference = 'Stop'
$LogFile = Join-Path $env:TEMP 'scanup_ui_launcher_error.log'
try { Remove-Item -Path $LogFile -Force -ErrorAction SilentlyContinue } catch {}

# ==================================================
# ScanUp Local Test Configuration
# Change BaseUrl only when switching local/live.
# ==================================================
$BaseUrl = 'http://localhost/SCANNER_PROD1/SCANNER-PROD/public'
$SecureOrigin = 'http://localhost'
$ConfigDir = Join-Path $env:APPDATA 'ScanUp'
$SavedFile = Join-Path $ConfigDir 'school.cfg'

if (-not (Test-Path $ConfigDir)) {
    New-Item -ItemType Directory -Path $ConfigDir -Force | Out-Null
}

Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName System.Drawing
[System.Windows.Forms.Application]::EnableVisualStyles()

function Get-ScanUpApiErrorMessage($ErrorRecord, $DefaultMessage) {
    $msg = $DefaultMessage

    try {
        if ($ErrorRecord.ErrorDetails -and $ErrorRecord.ErrorDetails.Message) {
            $parsed = $ErrorRecord.ErrorDetails.Message | ConvertFrom-Json
            if ($parsed.error) { return [string]$parsed.error }
            if ($parsed.message) { return [string]$parsed.message }
        }
    } catch {}

    try {
        $response = $ErrorRecord.Exception.Response
        if ($response) {
            $stream = $response.GetResponseStream()
            if ($stream) {
                $reader = New-Object System.IO.StreamReader($stream)
                $rawBody = $reader.ReadToEnd()
                if (-not [string]::IsNullOrWhiteSpace($rawBody)) {
                    try {
                        $parsed = $rawBody | ConvertFrom-Json
                        if ($parsed.error) { return [string]$parsed.error }
                        if ($parsed.message) { return [string]$parsed.message }
                    } catch {
                        return [string]$rawBody
                    }
                }
            }
        }
    } catch {}

    if ($ErrorRecord.Exception.Message) {
        return [string]$ErrorRecord.Exception.Message
    }

    return $msg
}
function Show-ScanUpSplash {
    $splash = New-Object System.Windows.Forms.Form
    $splash.Size = New-Object System.Drawing.Size(520, 340)
    $splash.StartPosition = 'CenterScreen'
    $splash.FormBorderStyle = 'None'
    $splash.BackColor = [System.Drawing.Color]::FromArgb(8, 18, 42)
    $splash.TopMost = $true
    $splash.Opacity = 0

    $logoScan = New-Object System.Windows.Forms.Label
    $logoScan.Text = 'SCAN'
    $logoScan.Font = New-Object System.Drawing.Font('Segoe UI', 48, [System.Drawing.FontStyle]::Bold)
    $logoScan.ForeColor = [System.Drawing.Color]::FromArgb(74, 159, 255)
    $logoScan.AutoSize = $true
    $logoScan.Location = New-Object System.Drawing.Point(100, 55)
    $splash.Controls.Add($logoScan)

    $logoUp = New-Object System.Windows.Forms.Label
    $logoUp.Text = 'UP'
    $logoUp.Font = New-Object System.Drawing.Font('Segoe UI', 48, [System.Drawing.FontStyle]::Bold)
    $logoUp.ForeColor = [System.Drawing.Color]::FromArgb(74, 255, 170)
    $logoUp.AutoSize = $true
    $logoUp.Location = New-Object System.Drawing.Point(290, 55)
    $splash.Controls.Add($logoUp)

    $line = New-Object System.Windows.Forms.Panel
    $line.BackColor = [System.Drawing.Color]::FromArgb(30, 60, 100)
    $line.Size = New-Object System.Drawing.Size(420, 1)
    $line.Location = New-Object System.Drawing.Point(50, 150)
    $splash.Controls.Add($line)

    $sub = New-Object System.Windows.Forms.Label
    $sub.Text = 'DepEd Ozamiz City Division'
    $sub.Font = New-Object System.Drawing.Font('Segoe UI', 11)
    $sub.ForeColor = [System.Drawing.Color]::FromArgb(140, 180, 230)
    $sub.TextAlign = 'MiddleCenter'
    $sub.Size = New-Object System.Drawing.Size(520, 24)
    $sub.Location = New-Object System.Drawing.Point(0, 162)
    $splash.Controls.Add($sub)

    $sub2 = New-Object System.Windows.Forms.Label
    $sub2.Text = 'QR Attendance System'
    $sub2.Font = New-Object System.Drawing.Font('Segoe UI', 9)
    $sub2.ForeColor = [System.Drawing.Color]::FromArgb(80, 120, 170)
    $sub2.TextAlign = 'MiddleCenter'
    $sub2.Size = New-Object System.Drawing.Size(520, 22)
    $sub2.Location = New-Object System.Drawing.Point(0, 190)
    $splash.Controls.Add($sub2)

    $barBg = New-Object System.Windows.Forms.Panel
    $barBg.Location = New-Object System.Drawing.Point(50, 245)
    $barBg.Size = New-Object System.Drawing.Size(420, 8)
    $barBg.BackColor = [System.Drawing.Color]::FromArgb(20, 40, 70)
    $splash.Controls.Add($barBg)

    $barFill = New-Object System.Windows.Forms.Panel
    $barFill.Location = New-Object System.Drawing.Point(50, 245)
    $barFill.Size = New-Object System.Drawing.Size(0, 8)
    $barFill.BackColor = [System.Drawing.Color]::FromArgb(74, 159, 255)
    $splash.Controls.Add($barFill)

    $status = New-Object System.Windows.Forms.Label
    $status.Text = 'Initializing ScanUp...'
    $status.Font = New-Object System.Drawing.Font('Segoe UI', 9)
    $status.ForeColor = [System.Drawing.Color]::FromArgb(80, 130, 190)
    $status.Size = New-Object System.Drawing.Size(420, 22)
    $status.Location = New-Object System.Drawing.Point(50, 260)
    $splash.Controls.Add($status)

    $ver = New-Object System.Windows.Forms.Label
    $ver.Text = 'v2.0 | Ozamiz City Schools'
    $ver.Font = New-Object System.Drawing.Font('Segoe UI', 7)
    $ver.ForeColor = [System.Drawing.Color]::FromArgb(40, 70, 110)
    $ver.TextAlign = 'MiddleCenter'
    $ver.Size = New-Object System.Drawing.Size(520, 18)
    $ver.Location = New-Object System.Drawing.Point(0, 305)
    $splash.Controls.Add($ver)

    $splash.Show()

    for ($i = 0; $i -le 10; $i++) {
        $splash.Opacity = $i / 10
        $splash.Refresh()
        Start-Sleep -Milliseconds 35
    }

    $steps = @(
        @{ w = 42;  t = 'Initializing ScanUp...' },
        @{ w = 105; t = 'Connecting to local server...' },
        @{ w = 168; t = 'Loading school directory...' },
        @{ w = 252; t = 'Preparing launcher...' },
        @{ w = 336; t = 'Verifying configuration...' },
        @{ w = 420; t = 'Welcome to ScanUp!' }
    )

    foreach ($step in $steps) {
        $current = $barFill.Width
        while ($current -lt $step.w) {
            $current += 5
            if ($current -gt $step.w) { $current = $step.w }
            $barFill.Width = $current
            $barFill.Refresh()
            Start-Sleep -Milliseconds 10
        }
        $status.Text = $step.t
        $status.Refresh()
        Start-Sleep -Milliseconds 230
    }

    for ($i = 10; $i -ge 0; $i--) {
        $splash.Opacity = $i / 10
        $splash.Refresh()
        Start-Sleep -Milliseconds 25
    }

    $splash.Close()
}

function Read-SavedSession {
    if (-not (Test-Path $SavedFile)) { return $null }

    $raw = Get-Content -Path $SavedFile -Raw -ErrorAction SilentlyContinue
    if ([string]::IsNullOrWhiteSpace($raw)) { return $null }

    $parts = $raw.Trim() -split '\|', 3
    if ($parts.Count -lt 3) { return $null }

    return [pscustomobject]@{
        SchoolName = $parts[0]
        DepedId = $parts[1]
        Token = $parts[2]
    }
}

function Save-Session($schoolName, $depedId, $token) {
    Set-Content -Path $SavedFile -Value ($schoolName + '|' + $depedId + '|' + $token) -Encoding ASCII
}

function Show-SavedSessionDialog($session) {
    $choice = [System.Windows.Forms.MessageBox]::Show(
        "Saved school login found:`n`nSchool: $($session.SchoolName)`nDepEd ID: $($session.DepedId)`n`nOpen scanner with this saved login?",
        'ScanUp',
        [System.Windows.Forms.MessageBoxButtons]::YesNo,
        [System.Windows.Forms.MessageBoxIcon]::Question
    )

    return $choice -eq [System.Windows.Forms.DialogResult]::Yes
}

function Show-LoginForm {
    $form = New-Object System.Windows.Forms.Form
    $form.Text = 'ScanUp - School Login'
    $form.Size = New-Object System.Drawing.Size(450, 465)
    $form.StartPosition = 'CenterScreen'
    $form.FormBorderStyle = 'FixedDialog'
    $form.MaximizeBox = $false
    $form.MinimizeBox = $false
    $form.BackColor = [System.Drawing.Color]::FromArgb(8, 18, 42)
    $form.TopMost = $true

    $title = New-Object System.Windows.Forms.Label
    $title.Text = 'SCANUP'
    $title.Font = New-Object System.Drawing.Font('Segoe UI', 30, [System.Drawing.FontStyle]::Bold)
    $title.ForeColor = [System.Drawing.Color]::FromArgb(74, 159, 255)
    $title.TextAlign = 'MiddleCenter'
    $title.Size = New-Object System.Drawing.Size(420, 54)
    $title.Location = New-Object System.Drawing.Point(10, 18)
    $form.Controls.Add($title)

    $subtitle = New-Object System.Windows.Forms.Label
    $subtitle.Text = 'DepEd Ozamiz City Division'
    $subtitle.Font = New-Object System.Drawing.Font('Segoe UI', 9)
    $subtitle.ForeColor = [System.Drawing.Color]::FromArgb(100, 145, 200)
    $subtitle.TextAlign = 'MiddleCenter'
    $subtitle.Size = New-Object System.Drawing.Size(420, 20)
    $subtitle.Location = New-Object System.Drawing.Point(10, 72)
    $form.Controls.Add($subtitle)

    $schoolLabel = New-Object System.Windows.Forms.Label
    $schoolLabel.Text = 'DepEd School ID'
    $schoolLabel.Font = New-Object System.Drawing.Font('Segoe UI', 9)
    $schoolLabel.ForeColor = [System.Drawing.Color]::FromArgb(140, 180, 230)
    $schoolLabel.Size = New-Object System.Drawing.Size(390, 20)
    $schoolLabel.Location = New-Object System.Drawing.Point(25, 108)
    $form.Controls.Add($schoolLabel)

    $schoolInput = New-Object System.Windows.Forms.TextBox
    $schoolInput.Font = New-Object System.Drawing.Font('Segoe UI', 12)
    $schoolInput.BackColor = [System.Drawing.Color]::FromArgb(12, 30, 58)
    $schoolInput.ForeColor = [System.Drawing.Color]::FromArgb(210, 230, 255)
    $schoolInput.BorderStyle = 'FixedSingle'
    $schoolInput.Size = New-Object System.Drawing.Size(390, 32)
    $schoolInput.Location = New-Object System.Drawing.Point(25, 130)
    $form.Controls.Add($schoolInput)

    $checkButton = New-Object System.Windows.Forms.Button
    $checkButton.Text = 'Check School'
    $checkButton.Font = New-Object System.Drawing.Font('Segoe UI', 10, [System.Drawing.FontStyle]::Bold)
    $checkButton.BackColor = [System.Drawing.Color]::FromArgb(0, 90, 160)
    $checkButton.ForeColor = [System.Drawing.Color]::White
    $checkButton.FlatStyle = 'Flat'
    $checkButton.Size = New-Object System.Drawing.Size(390, 38)
    $checkButton.Location = New-Object System.Drawing.Point(25, 174)
    $form.Controls.Add($checkButton)

    $statusPanel = New-Object System.Windows.Forms.Panel
    $statusPanel.Size = New-Object System.Drawing.Size(390, 78)
    $statusPanel.Location = New-Object System.Drawing.Point(25, 224)
    $statusPanel.BackColor = [System.Drawing.Color]::FromArgb(12, 30, 58)
    $statusPanel.Visible = $false
    $form.Controls.Add($statusPanel)

    $schoolFound = New-Object System.Windows.Forms.Label
    $schoolFound.Font = New-Object System.Drawing.Font('Segoe UI', 9, [System.Drawing.FontStyle]::Bold)
    $schoolFound.ForeColor = [System.Drawing.Color]::FromArgb(74, 255, 170)
    $schoolFound.Size = New-Object System.Drawing.Size(370, 22)
    $schoolFound.Location = New-Object System.Drawing.Point(10, 7)
    $statusPanel.Controls.Add($schoolFound)

    $schoolIdText = New-Object System.Windows.Forms.Label
    $schoolIdText.Font = New-Object System.Drawing.Font('Segoe UI', 8)
    $schoolIdText.ForeColor = [System.Drawing.Color]::FromArgb(110, 155, 210)
    $schoolIdText.Size = New-Object System.Drawing.Size(370, 18)
    $schoolIdText.Location = New-Object System.Drawing.Point(10, 28)
    $statusPanel.Controls.Add($schoolIdText)

    $principalText = New-Object System.Windows.Forms.Label
    $principalText.Font = New-Object System.Drawing.Font('Segoe UI', 8)
    $principalText.ForeColor = [System.Drawing.Color]::FromArgb(130, 190, 240)
    $principalText.Size = New-Object System.Drawing.Size(370, 18)
    $principalText.Location = New-Object System.Drawing.Point(10, 48)
    $statusPanel.Controls.Add($principalText)

    $errorText = New-Object System.Windows.Forms.Label
    $errorText.Font = New-Object System.Drawing.Font('Segoe UI', 9)
    $errorText.ForeColor = [System.Drawing.Color]::FromArgb(255, 105, 105)
    $errorText.Size = New-Object System.Drawing.Size(390, 20)
    $errorText.Location = New-Object System.Drawing.Point(25, 304)
    $form.Controls.Add($errorText)

    $passwordPanel = New-Object System.Windows.Forms.Panel
    $passwordPanel.Size = New-Object System.Drawing.Size(400, 125)
    $passwordPanel.Location = New-Object System.Drawing.Point(20, 325)
    $passwordPanel.BackColor = [System.Drawing.Color]::FromArgb(8, 18, 42)
    $passwordPanel.Visible = $false
    $form.Controls.Add($passwordPanel)

    $passwordLabel = New-Object System.Windows.Forms.Label
    $passwordLabel.Text = 'Password'
    $passwordLabel.Font = New-Object System.Drawing.Font('Segoe UI', 9)
    $passwordLabel.ForeColor = [System.Drawing.Color]::FromArgb(140, 180, 230)
    $passwordLabel.Size = New-Object System.Drawing.Size(390, 18)
    $passwordLabel.Location = New-Object System.Drawing.Point(5, 0)
    $passwordPanel.Controls.Add($passwordLabel)

    $passwordInput = New-Object System.Windows.Forms.TextBox
    $passwordInput.Font = New-Object System.Drawing.Font('Segoe UI', 12)
    $passwordInput.BackColor = [System.Drawing.Color]::FromArgb(12, 30, 58)
    $passwordInput.ForeColor = [System.Drawing.Color]::FromArgb(210, 230, 255)
    $passwordInput.BorderStyle = 'FixedSingle'
    $passwordInput.UseSystemPasswordChar = $true
    $passwordInput.Size = New-Object System.Drawing.Size(390, 32)
    $passwordInput.Location = New-Object System.Drawing.Point(5, 22)
    $passwordPanel.Controls.Add($passwordInput)

    $passwordError = New-Object System.Windows.Forms.Label
    $passwordError.Font = New-Object System.Drawing.Font('Segoe UI', 8)
    $passwordError.ForeColor = [System.Drawing.Color]::FromArgb(255, 105, 105)
    $passwordError.Size = New-Object System.Drawing.Size(390, 18)
    $passwordError.Location = New-Object System.Drawing.Point(5, 57)
    $passwordPanel.Controls.Add($passwordError)

    $loginButton = New-Object System.Windows.Forms.Button
    $loginButton.Text = 'Login and Open Scanner'
    $loginButton.Font = New-Object System.Drawing.Font('Segoe UI', 11, [System.Drawing.FontStyle]::Bold)
    $loginButton.BackColor = [System.Drawing.Color]::FromArgb(0, 120, 212)
    $loginButton.ForeColor = [System.Drawing.Color]::White
    $loginButton.FlatStyle = 'Flat'
    $loginButton.Size = New-Object System.Drawing.Size(390, 42)
    $loginButton.Location = New-Object System.Drawing.Point(5, 78)
    $passwordPanel.Controls.Add($loginButton)

    $state = [ordered]@{
        DepedId = ''
        SchoolName = ''
        Token = ''
    }

    $checkButton.Add_Click({
        $errorText.Text = ''
        $passwordError.Text = ''
        $principalText.Text = ''
        $statusPanel.Visible = $false
        $passwordPanel.Visible = $false

        $sid = $schoolInput.Text.Trim()
        if ($sid -eq '') {
            $errorText.Text = 'Please enter your School ID.'
            return
        }

        $checkButton.Text = 'Checking...'
        $checkButton.Enabled = $false
        $form.Refresh()

        try {
            $response = Invoke-RestMethod -Uri ($BaseUrl + '/api/school/check/' + $sid) -Method Get -UseBasicParsing -TimeoutSec 15
            if ($response.exists -eq $true) {
                $state.DepedId = $sid
                $state.SchoolName = [string]$response.school_name
                $schoolFound.Text = ([char]0x2713) + ' ' + $state.SchoolName
                $schoolIdText.Text = 'DepEd ID: ' + $sid
                $principalName = ''
                if ($response.PSObject.Properties.Name -contains 'principal_name') {
                    $principalName = [string]$response.principal_name
                }
                if ([string]::IsNullOrWhiteSpace($principalName) -and ($response.PSObject.Properties.Name -contains 'reporting_manager_name')) {
                    $principalName = [string]$response.reporting_manager_name
                }
                if (-not [string]::IsNullOrWhiteSpace($principalName)) {
                    $principalText.Text = 'Principal: ' + $principalName
                } elseif (($response.PSObject.Properties.Name -contains 'principal_found') -and ($response.principal_found -eq $false)) {
                    $principalText.Text = 'Principal: Not mapped in EHRIS yet'
                } else {
                    $principalText.Text = 'Principal: Unavailable'
                }
                $statusPanel.BackColor = [System.Drawing.Color]::FromArgb(8, 40, 24)
                $statusPanel.Visible = $true
                $passwordPanel.Visible = $true
                $schoolInput.Enabled = $false
                $checkButton.Text = 'School Verified'
                $form.AcceptButton = $loginButton
                $passwordInput.Focus()
            } else {
                $errorText.Text = 'School ID not found. Contact your administrator.'
                $checkButton.Text = 'Check School'
                $checkButton.Enabled = $true
            }
        } catch {
            $msg = Get-ScanUpApiErrorMessage $_ 'Cannot reach server. Check XAMPP/Apache and BASE_URL.'
            $errorText.Text = $msg
            $checkButton.Text = 'Check School'
            $checkButton.Enabled = $true
        }
    })

    $loginButton.Add_Click({
        $passwordError.Text = ''
        if ($passwordInput.Text -eq '') {
            $passwordError.Text = 'Please enter your password.'
            return
        }

        $loginButton.Text = 'Logging in...'
        $loginButton.Enabled = $false
        $form.Refresh()

        try {
            $payload = @{
                deped_school_id = $state.DepedId
                password = $passwordInput.Text
            } | ConvertTo-Json -Compress

            $response = Invoke-RestMethod -Uri ($BaseUrl + '/api/guard/login') -Method Post -Body $payload -ContentType 'application/json' -UseBasicParsing -TimeoutSec 20
            if ($response.token) {
                $state.SchoolName = [string]$response.school_name
                $state.DepedId = [string]$response.deped_id
                $state.Token = [string]$response.token
                Save-Session $state.SchoolName $state.DepedId $state.Token
                $form.DialogResult = [System.Windows.Forms.DialogResult]::OK
                $form.Close()
            } else {
                $passwordError.Text = 'Login failed.'
                $loginButton.Text = 'Login and Open Scanner'
                $loginButton.Enabled = $true
            }
        } catch {
            $msg = Get-ScanUpApiErrorMessage $_ 'Cannot reach server.'
            $passwordError.Text = $msg
            $loginButton.Text = 'Login and Open Scanner'
            $loginButton.Enabled = $true
        }
    })

    $form.AcceptButton = $checkButton
    $result = $form.ShowDialog()

    if ($result -eq [System.Windows.Forms.DialogResult]::OK) {
        return [pscustomobject]@{
            SchoolName = $state.SchoolName
            DepedId = $state.DepedId
            Token = $state.Token
        }
    }

    return $null
}

function Open-Scanner($session) {
    # Keep URL school-scoped by DepEd ID only (no token in visible query string).
    $url = $BaseUrl + '/scanner?deped_id=' + [uri]::EscapeDataString($session.DepedId)
    $profileDir = 'C:\ScanUpTemp\' + $session.DepedId

    $chrome64 = 'C:\Program Files\Google\Chrome\Application\chrome.exe'
    $chrome86 = 'C:\Program Files (x86)\Google\Chrome\Application\chrome.exe'
    $edge = 'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe'

    $args = @(
        '--unsafely-treat-insecure-origin-as-secure=' + $SecureOrigin,
        '--user-data-dir=' + $profileDir,
        '--no-first-run',
        '--no-default-browser-check',
        $url
    )

    if (Test-Path $chrome64) {
        Start-Process -FilePath $chrome64 -ArgumentList $args
        return
    }

    if (Test-Path $chrome86) {
        Start-Process -FilePath $chrome86 -ArgumentList $args
        return
    }

    if (Test-Path $edge) {
        Start-Process -FilePath $edge -ArgumentList $args
        return
    }

    [System.Windows.Forms.MessageBox]::Show('No browser found. Please install Google Chrome.', 'ScanUp Error', 0, 16) | Out-Null
}

try {
    Show-ScanUpSplash

    $session = Read-SavedSession
    if ($session -and (Show-SavedSessionDialog $session)) {
        Open-Scanner $session
        exit 0
    }

    if ($session) {
        Remove-Item -Path $SavedFile -Force -ErrorAction SilentlyContinue
    }

    $newSession = Show-LoginForm
    if ($newSession) {
        Open-Scanner $newSession
    }
} catch {
    $message = $_.Exception.Message
    try { Set-Content -Path $LogFile -Value ($_.Exception.ToString()) -Encoding ASCII } catch {}
    [System.Windows.Forms.MessageBox]::Show("ScanUp launcher error:`n`n$message`n`nLog: $LogFile", 'ScanUp Error', 0, 16) | Out-Null
    exit 1
}
