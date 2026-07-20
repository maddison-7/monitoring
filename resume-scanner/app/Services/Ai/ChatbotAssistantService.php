<?php

namespace App\Services\Ai;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChatbotAssistantService
{
    /**
     * Answer a question using retrieved context and recent conversation history.
     *
     * @param array<int, array{role: string, text: string}> $history
     */
    public function reply(string $context, string $question, array $history = []): ?string
    {
        $apiKey = (string) config('services.gemini.api_key');
        if ($apiKey === '') {
            return null;
        }

        $contents = [];
        foreach ($history as $turn) {
            $role = ($turn['role'] ?? 'user') === 'assistant' ? 'model' : 'user';
            $text = trim((string) ($turn['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            $contents[] = ['role' => $role, 'parts' => [['text' => $text]]];
        }

        $instructions = <<<PROMPT
            You are an experienced, knowledgeable recruitment assistant embedded in a hiring platform. A real
            person is asking you a real question about their own recruitment activity — read their question
            carefully and actually answer what they asked, the way a sharp, attentive human assistant would,
            not a scripted FAQ bot.

            How to think about every question:
            - Figure out what the person actually wants to know, including implied follow-ups (e.g. "am I
              competitive for this role?" means: look at their AI match score, strengths, and weaknesses for
              that specific job and give a real opinion, not a disclaimer).
            - Reason over the data below — cross-reference applications, AI scores, interviews, and saved jobs
              as needed rather than treating each fact in isolation.
            - Every specific claim you make (a score, a skill, a date, a status) must come from the data below.
              Never invent numbers, skills, or outcomes that are not present in it.
            - If the data needed to answer isn't in the context, say that plainly and suggest what the person
              could do next (e.g. complete their profile, upload a CV) — do not fabricate an answer to seem
              helpful.
            - Vary your phrasing naturally like a person would across a conversation; do not repeat the same
              sentence templates for similar questions. Match the tone to the question: brief for a quick
              factual question, more thorough for a question that asks for advice or analysis.
            - Be honest and direct, including when the news is not great (e.g. a low match score or a missing
              required skill) — a good human assistant does not sugarcoat to avoid discomfort, but stays
              respectful and constructive.
            - Respond in the same language the question was asked in.

            Applicant's data (applications, AI scores, interviews, saved jobs, resume excerpt):
            {$context}

            Question: {$question}
            PROMPT;

        $contents[] = ['role' => 'user', 'parts' => [['text' => $instructions]]];

        return $this->callGemini($contents, $apiKey);
    }

    /**
     * @param array<int, array<string, mixed>> $contents
     */
    private function callGemini(array $contents, string $apiKey): ?string
    {
        // A single Gemini call (with "thinking" enabled on 2.5-flash) can take 40s+.
        // PHP's own max_execution_time (often 30s under Apache/mod_php) would otherwise
        // kill this request before the HTTP client's own timeout ever gets a chance to.
        if (function_exists('set_time_limit')) {
            @set_time_limit(120);
        }

        $model = (string) config('services.gemini.model', 'gemini-2.5-flash');
        $baseUrl = rtrim((string) config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');

        $response = $this->sendGeminiRequest(
            $baseUrl . '/models/' . rawurlencode($model) . ':generateContent',
            $apiKey,
            [
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => 0.45,
                    'maxOutputTokens' => 2048,
                    'responseMimeType' => 'text/plain',
                ],
            ],
            'Chatbot'
        );

        if ($response === null) {
            return null;
        }

        $payload = $response->json();
        $parts = Arr::get($payload, 'candidates.0.content.parts', []);
        if (!is_array($parts)) {
            return null;
        }

        $text = collect($parts)
            ->map(fn ($part) => is_array($part) ? (string) Arr::get($part, 'text', '') : '')
            ->filter(fn (string $partText): bool => trim($partText) !== '')
            ->implode('');

        return trim($text) !== '' ? trim($text) : null;
    }

    /**
     * Sends the Gemini request, automatically retrying once if the API responds with a
     * transient 429 (rate limit) status — Gemini's free tier caps requests per minute, and
     * a short burst of chat/scoring activity can trip it. Honors the API's suggested
     * retry-after delay, capped to keep the overall request time reasonable.
     *
     * @param array<string, mixed> $body
     */
    private function sendGeminiRequest(string $url, string $apiKey, array $body, string $logLabel, int $maxAttempts = 2): ?\Illuminate\Http\Client\Response
    {
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = Http::timeout(60)
                    ->withOptions(['verify' => storage_path('certs/cacert.pem')])
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->withQueryParameters(['key' => $apiKey])
                    ->post($url, $body);
            } catch (Throwable $exception) {
                Log::warning("{$logLabel} Gemini request failed before response.", [
                    'attempt' => $attempt,
                    'exception' => $exception->getMessage(),
                ]);

                return null;
            }

            if ($response->successful()) {
                return $response;
            }

            if ($response->status() === 429 && $attempt < $maxAttempts) {
                $delaySeconds = $this->extractRetryDelaySeconds((string) $response->body());
                Log::warning("{$logLabel} Gemini request rate-limited, retrying shortly.", [
                    'attempt' => $attempt,
                    'retry_after_seconds' => $delaySeconds,
                ]);
                sleep($delaySeconds);
                continue;
            }

            Log::warning("{$logLabel} Gemini request returned non-success status.", [
                'attempt' => $attempt,
                'status' => $response->status(),
                'body' => mb_substr((string) $response->body(), 0, 600),
            ]);

            return null;
        }

        return null;
    }

    private function extractRetryDelaySeconds(string $responseBody): int
    {
        if (preg_match('/"retryDelay"\s*:\s*"(\d+(?:\.\d+)?)s"/', $responseBody, $matches) === 1) {
            return max(1, min(8, (int) ceil((float) $matches[1])));
        }

        return 4;
    }
}
