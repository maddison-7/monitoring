<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Privacy Policy - Resume Screener</title>
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
            <h1 class="text-3xl sm:text-4xl font-bold tracking-tight">Privacy Policy</h1>
            <p class="mt-3 text-sm text-slate-500">Effective date: {{ now()->format('Y-m-d') }}</p>

            <div class="mt-8 space-y-6 text-slate-700 leading-7">
                <section>
                    <h2 class="text-xl font-semibold text-slate-900">1. Information We Collect</h2>
                    <p class="mt-2">Resume Screener may collect account information, job descriptions, uploaded CV files, and system usage data needed to provide screening and ranking functionality.</p>
                </section>

                <section>
                    <h2 class="text-xl font-semibold text-slate-900">2. How We Use Information</h2>
                    <p class="mt-2">We use collected data to analyze resumes, generate candidate match scores, support recruiter workflows, and improve platform reliability and auditability.</p>
                </section>

                <section>
                    <h2 class="text-xl font-semibold text-slate-900">3. Data Storage and Retention</h2>
                    <p class="mt-2">Uploaded documents and extracted profile data are stored in the application database and storage paths. Retention duration depends on your organization policy and legal obligations.</p>
                </section>

                <section>
                    <h2 class="text-xl font-semibold text-slate-900">4. Security</h2>
                    <p class="mt-2">We apply authentication, authorization, and controlled access to reduce unauthorized access risks. You are responsible for protecting account credentials and session access.</p>
                </section>

                <section>
                    <h2 class="text-xl font-semibold text-slate-900">5. Third-Party Services</h2>
                    <p class="mt-2">When AI scoring is enabled, data may be processed through configured AI service providers according to your system settings and provider terms.</p>
                </section>

                <section>
                    <h2 class="text-xl font-semibold text-slate-900">6. Contact</h2>
                    <p class="mt-2">For privacy concerns, contact your system administrator or organization compliance owner.</p>
                </section>
            </div>
        </div>
    </main>
</body>
</html>
