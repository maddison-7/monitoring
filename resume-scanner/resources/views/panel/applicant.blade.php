<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Panel</title>
    <style>
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(140deg, #eef7f6, #f8fafc);
            color: #0f172a;
        }

        .container {
            max-width: 900px;
            margin: 36px auto;
            padding: 0 16px;
        }

        .card {
            background: #ffffff;
            border: 1px solid #dbeafe;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        }

        h1 {
            margin: 0 0 8px;
            color: #0f766e;
        }

        p {
            margin: 0 0 16px;
            color: #334155;
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            text-decoration: none;
            border-radius: 10px;
            padding: 10px 14px;
            font-weight: 600;
            border: 1px solid transparent;
        }

        .btn-primary {
            background: #0f766e;
            color: #fff;
        }

        .btn-outline {
            border-color: #0f766e;
            color: #0f766e;
            background: #fff;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1>Applicant Panel</h1>
        <p>You are logged in as applicant. Use API profile/application endpoints or open dashboard views below.</p>
        <div class="actions">
            <a class="btn btn-primary" href="{{ route('ajira.dashboard') }}">Open Ajira Dashboard</a>
            <a class="btn btn-outline" href="{{ route('home') }}">Go Home</a>
        </div>
    </div>
</div>
</body>
</html>
