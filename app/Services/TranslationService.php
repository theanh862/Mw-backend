<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TranslationService
{
    public function translate(string $text, string $from = 'vi', string $to = 'en'): ?string
    {
        if (empty(trim($text))) {
            return null;
        }

        try {
            $url = 'https://translate.googleapis.com/translate_a/single';
            $response = Http::timeout(10)->get($url, [
                'client' => 'gtx',
                'sl'     => $from,
                'tl'     => $to,
                'dt'     => 't',
                'q'      => $text,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                // Response format: [[[translated, original, ...],...],...]
                $translated = collect($data[0] ?? [])
                    ->pluck(0)
                    ->filter()
                    ->implode('');

                if ($translated && strtolower($translated) !== strtolower($text)) {
                    return $translated;
                }
            }
        } catch (\Exception $e) {
            Log::warning('TranslationService failed: ' . $e->getMessage());
        }

        return null;
    }
}
