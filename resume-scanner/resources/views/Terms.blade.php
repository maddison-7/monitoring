<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Terms & Conditions - Resume Screener</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #F8FAFC;
            color: #0F172A;
        }
    </style>
</head>
<body>
    <main class="max-w-4xl mx-auto px-4 py-10 sm:py-14">
        <a href="{{ route('home') }}" class="inline-flex items-center text-sm font-semibold text-blue-700 hover:text-blue-800">Back to Home</a>

        <div class="mt-6 rounded-3xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
            <h1 class="text-3xl sm:text-4xl font-bold tracking-tight">Terms & Conditions</h1>
            <p class="mt-3 text-sm text-slate-500">Effective date: {{ now()->format('Y-m-d') }}</p>

            <div class="mt-8 space-y-6 text-slate-700 leading-7">
                <section>
                    <h2 class="text-xl font-semibold text-slate-900">1. Platform Use</h2>
                    <p class="mt-2">Resume Screener is intended for authorized recruitment workflows. Users must use the platform in compliance with applicable employment, privacy, and anti-discrimination laws.</p>
                </section>

                <section>
                    <h2 class="text-xl font-semibold text-slate-900">2. Account Responsibility</h2>
                    <p class="mt-2">Users are responsible for maintaining credential security and ensuring all actions performed under their account are authorized.</p>
                </section>

                <section>
                    <h2 class="text-xl font-semibold text-slate-900">3. Candidate Data</h2>
                    <p class="mt-2">You must have legal authority to upload and process candidate resumes. Sensitive information should be handled according to your organizational policy.</p>
                </section>

                <section>
                    <h2 class="text-xl font-semibold text-slate-900">4. AI-Assisted Screening</h2>
                    <p class="mt-2">Match scores and recommendations are decision-support outputs and should not be used as the sole basis for hiring decisions without human review.</p>
                </section>

                <section>
                    <h2 class="text-xl font-semibold text-slate-900">5. Availability and Changes</h2>
                    <p class="mt-2">Features may evolve over time. The organization may update workflows, scoring behavior, and these terms as needed.</p>
                </section>

                <section>
                    <h2 class="text-xl font-semibold text-slate-900">6. Limitation of Liability</h2>
                    <p class="mt-2">The platform is provided as configured by your organization. To the extent permitted by law, liability is limited for indirect, incidental, or consequential damages.</p>
                </section>
            </div>
        </div>
    </main>
</body>
</html>
