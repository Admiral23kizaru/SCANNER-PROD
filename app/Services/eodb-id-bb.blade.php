<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        body { margin: 0; padding: 0; font-family: helvetica, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        
        /* Container Scaling: PVC size high resolution (portrait) */
        .card-container {
            position: relative;
            width: 638px;
            height: 1011px;
            overflow: hidden;
            display: block;
        }

        /* Background */
        .bg-template {
            position: absolute;
            top: 0;
            left: 0;
            width: 638px;
            height: 1011px;
            z-index: 1;
        }

        /* Teacher Picture: Position on the right */
        .profile-photo {
            position: absolute;
            top: 150px;
            right: 60px;
            width: 310px;
            height: 310px;
            z-index: 2;
        }

        /* QR Code: Position it accurately Front/Center */
        .qr-code {
            position: absolute;
            bottom: 60px;
            left: 420px;
            width: 150px;
            height: 150px;
            z-index: 2;
        }

        /* Text Details */
        .text-group {
            position: absolute;
            top: 250px;
            left: 60px;
            width: 450px;
            z-index: 2;
        }

        /* Font: Bold, clear font for name */
        .teacher-name {
            font-size: 50px;
            font-weight: bold;
            color: #ffffff;
            line-height: 1.1;
        }

        .job-title {
            font-size: 32px;
            font-weight: bold;
            color: #ffffff;
            margin-top: 5px;
        }
        
        .employee-id {
            font-size: 26px;
            font-weight: bold;
            color: #ffffff;
            margin-top: 15px;
        }

        /* Print-specific fixes to make user info visible when the browser/printer omits background images */
        @media print {
            /* Ensure colors and images are printed as intended */
            body, .bg-template, img { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

            /* If background images are not printed by the browser, provide a solid backdrop for text */
            .text-group {
                background: #ffffff; /* white background so dark text remains visible */
                padding: 6px 10px;
                border-radius: 2px;
                box-sizing: border-box;
                z-index: 3;
            }

            /* Force text to dark color for legibility when backgrounds may not appear */
            .teacher-name, .job-title, .employee-id {
                color: #000000 !important;
                text-shadow: none !important;
            }

            /* Keep layout sizing consistent for print */
            .card-container { width: 638px; height: 1011px; }

            /* Prevent page breaks inside the card */
            .card-container { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="card-container">
        <!-- Background Image -->
        <img src="{{ $background }}" class="bg-template">
        
        <!-- Text details on the left -->
        <div class="text-group">
            <div class="teacher-name">{{ $name }}</div>
            <div class="job-title">{{ $job_title }}</div>
            <div class="employee-id">ID NO. {{ $employee_id }}</div>
        </div>

        <!-- Profile Picture on the right -->
        @if($photo)
            <img src="{{ $photo }}" class="profile-photo">
        @endif

        <!-- QR Code Front/Center -->
        @if($qr)
            <img src="{{ $qr }}" class="qr-code">
        @endif
    </div>
</body>
</html>
