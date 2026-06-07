<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajira Recruitment Dashboard</title>
    <style>
        :root {
            --bg: #f7f8f4;
            --card: #ffffff;
            --text: #12312b;
            --muted: #5b736d;
            --accent: #0f766e;
            --warning: #b45309;
            --danger: #b91c1c;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: radial-gradient(circle at top right, #d9eee9, var(--bg));
            color: var(--text);
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        .layout {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 12px;
            flex-wrap: wrap;
        }

        .title { margin: 0; font-size: 28px; }
        .subtitle { margin: 6px 0 0; color: var(--muted); }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }

        .card {
            background: var(--card);
            border-radius: 14px;
            padding: 14px;
            border: 1px solid #dbe7e4;
            box-shadow: 0 8px 24px rgba(10, 35, 31, 0.06);
        }

        .metric-label { color: var(--muted); font-size: 13px; }
        .metric-value { font-size: 28px; margin-top: 6px; font-weight: 700; }

        .panel {
            background: var(--card);
            border: 1px solid #dbe7e4;
            border-radius: 14px;
            padding: 14px;
            margin-bottom: 14px;
        }

        .panel h3 { margin: 0 0 10px; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        th, td {
            padding: 10px 8px;
            border-bottom: 1px solid #edf2f1;
            text-align: left;
        }

        th { color: var(--muted); font-weight: 600; }

        .tag {
            display: inline-block;
            padding: 4px 9px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        .tag.high { background: #dcfce7; color: #166534; }
        .tag.mid { background: #fef3c7; color: var(--warning); }
        .tag.low { background: #fee2e2; color: var(--danger); }

        @media (max-width: 700px) {
            .metric-value { font-size: 24px; }
            .title { font-size: 24px; }
        }
    </style>
</head>
<body>
<div class="layout">
    <header class="header">
        <div>
            <h1 class="title">Ajira-Style Recruitment Dashboard</h1>
            <p class="subtitle">AI scoring is decision-support only; HR makes final decisions.</p>
        </div>
    </header>

    <section class="grid">
        <article class="card"><div class="metric-label">Total Applicants</div><div class="metric-value">--</div></article>
        <article class="card"><div class="metric-label">Shortlisted</div><div class="metric-value">--</div></article>
        <article class="card"><div class="metric-label">Interviews Scheduled</div><div class="metric-value">--</div></article>
        <article class="card"><div class="metric-label">Hired</div><div class="metric-value">--</div></article>
    </section>

    <section class="panel">
        <h3>Top Ranked Applicants</h3>
        <table>
            <thead>
            <tr>
                <th>Candidate</th>
                <th>Job</th>
                <th>AI Score</th>
                <th>Recommendation</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td colspan="5" style="color:#5b736d;">Use GET /api/v1/hr/dashboard to populate this section.</td>
            </tr>
            </tbody>
        </table>
    </section>

    <section class="panel">
        <h3>Candidate Categories</h3>
        <p><span class="tag high">Highly Qualified</span> <span class="tag mid">Moderately Qualified</span> <span class="tag low">Unqualified</span></p>
    </section>
</div>
</body>
</html>
