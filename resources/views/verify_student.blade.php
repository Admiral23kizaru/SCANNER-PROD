<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Learner Verification</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #0f172a;
            color: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .card {
            background: #020617;
            border-radius: 1rem;
            border: 1px solid #1e293b;
            box-shadow: 0 20px 40px rgba(15,23,42,0.75);
            padding: 1.75rem 2rem;
            max-width: 480px;
            width: 100%;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.75rem;
            padding: 0.25rem 0.6rem;
            border-radius: 999px;
            border: 1px solid #22c55e33;
            background: #022c22;
            color: #bbf7d0;
            margin-bottom: 0.75rem;
        }
        h1 {
            font-size: 1.35rem;
            margin: 0 0 0.75rem;
            color: #e5e7eb;
        }
        p {
            margin: 0;
            font-size: 0.9rem;
            color: #9ca3af;
        }
        dl {
            margin: 1.5rem 0 0;
        }
        dt {
            font-size: 0.75rem;
            color: #6b7280;
        }
        dd {
            margin: 0.1rem 0 0.75rem;
            font-size: 0.95rem;
            color: #e5e7eb;
        }
        .lrn {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            letter-spacing: 0.05em;
        }
        .footer {
            margin-top: 1.5rem;
            font-size: 0.75rem;
            color: #6b7280;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
        }
        .pill {
            padding: 0.2rem 0.6rem;
            border-radius: 999px;
            border: 1px solid #1e293b;
            background: #020617;
            color: #9ca3af;
        }
        a {
            color: #60a5fa;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <main class="card">
        <div class="badge">
            <span>&#x2705;</span>
            <span>Learner verified</span>
        </div>
        <h1>{{ $student->first_name }} {{ $student->middle_name }} {{ $student->last_name }}</h1>
        <p>This QR code links to the official learner verification page for this student.</p>

        <dl>
            <div>
                <dt>Full name</dt>
                <dd>{{ $student->first_name }} {{ $student->middle_name }} {{ $student->last_name }}</dd>
            </div>
            <div>
                <dt>LRN</dt>
                <dd class="lrn">{{ $student->student_number }}</dd>
            </div>
            <div>
                <dt>Grade / Section</dt>
                <dd>{{ $student->grade_section ?? ($student->grade ? ($student->grade . ($student->section ? ('-' . $student->section) : '')) : '—') }}</dd>
            </div>
            <div>
                <dt>Guardian</dt>
                <dd>{{ $student->guardian ?? '—' }}</dd>
            </div>
            <div>
                <dt>Parent email</dt>
                <dd>{{ $student->parent_email ?? '—' }}</dd>
            </div>
            <div>
                <dt>Contact number</dt>
                <dd>{{ $student->contact_number ?? '—' }}</dd>
            </div>
        </dl>

        <div class="footer">
            <span class="pill">{{ url()->current() }}</span>
            <span>ScanUp · Localhost verification</span>
        </div>
    </main>
</body>
</html>

