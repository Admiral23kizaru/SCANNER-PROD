<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        body { margin: 0; padding: 0; font-family: helvetica, sans-serif; }
        
        /* Container Scaling: PVC size high resolution */
        .card-container {
            position: relative;
            width: 1011px;
            height: 638px;
            overflow: hidden;
            display: block;
        }

        /* Background */
        .bg-template {
            position: absolute;
            top: 0;
            left: 0;
            width: 1011px;
            height: 638px;
            z-index: 1;
        }

        /* Teacher Picture: Position on the right */
        .profile-photo {
            position: absolute;
            top: 180px;
            right: 80px;
            width: 250px;
            height: 250px;
            z-index: 2;
        }

        /* QR Code: Position on the front side */
        .qr-code {
            position: absolute;
            bottom: 50px;
            left: 50px;
            width: 150px;
            height: 150px;
            z-index: 2;
        }

        /* Text: Place Name and Job Title on the left */
        .text-group {
            position: absolute;
            top: 250px;
            left: 50px;
            width: 550px;
            z-index: 2;
        }

        /* Font: Bold, clear font for name */
        .teacher-name {
            font-size: 55px;
            font-weight: bold;
            color: #000;
        }

        .job-title {
            font-size: 35px;
            color: #333;
            margin-top: 15px;
        }
        
        .employee-id {
            font-size: 30px;
            color: #333;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="card-container">
        <!-- Background Image -->
        <img src="{{ $background }}" class="bg-template">
        
        <!-- Profile Picture on the right -->
        @if($photo)
            <img src="{{ $photo }}" class="profile-photo">
        @endif
        
        <!-- QR Code -->
        @if($qr)
            <img src="{{ $qr }}" class="qr-code">
        @endif

        <!-- Absolute positioned text on left -->
        <div class="text-group">
            <div class="teacher-name">{{ $name }}</div>
            <div class="job-title">{{ $job_title }}</div>
            <div class="employee-id">ID NO. {{ $employee_id }}</div>
        </div>
    </div>
</body>
</html>
