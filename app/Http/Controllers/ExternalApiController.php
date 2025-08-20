<?php

namespace App\Http\Controllers;

use App\Services\ExternalApiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ExternalApiController extends Controller
{
    /**
     * Dobavi vremenske informacije
     */
    public function weather(Request $request): JsonResponse
    {
        $request->validate([
            'city' => 'required|string|max:100'
        ]);

        $weather = ExternalApiService::getWeather($request->city);

        if ($weather) {
            return response()->json([
                'success' => true,
                'data' => $weather
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Nije moguće dobiti vremenske informacije'
        ], 400);
    }

    /**
     * Prevedi tekst
     */
    public function translate(Request $request): JsonResponse
    {
        $request->validate([
            'text' => 'required|string|max:1000',
            'from' => 'nullable|string|size:2',
            'to' => 'nullable|string|size:2'
        ]);

        $translated = ExternalApiService::translateText(
            $request->text,
            $request->from ?? 'auto',
            $request->to ?? 'en'
        );

        if ($translated) {
            return response()->json([
                'success' => true,
                'data' => [
                    'original' => $request->text,
                    'translated' => $translated,
                    'from' => $request->from ?? 'auto',
                    'to' => $request->to ?? 'en'
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Nije moguće prevesti tekst'
        ], 400);
    }

    /**
     * Dobavi vesti
     */
    public function news(Request $request): JsonResponse
    {
        $request->validate([
            'category' => 'nullable|string|in:general,business,technology,sports,entertainment,health,science',
            'limit' => 'nullable|integer|min:1|max:20'
        ]);

        $news = ExternalApiService::getNews(
            $request->category ?? 'general',
            $request->limit ?? 5
        );

        return response()->json([
            'success' => true,
            'data' => $news
        ]);
    }

    /**
     * Konvertuj valutu
     */
    public function currency(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3'
        ]);

        $conversion = ExternalApiService::convertCurrency(
            $request->amount,
            $request->from,
            $request->to
        );

        if ($conversion) {
            return response()->json([
                'success' => true,
                'data' => $conversion
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Nije moguće konvertovati valutu'
        ], 400);
    }

    /**
     * Dobavi informacije o IP adresi
     */
    public function ipInfo(Request $request): JsonResponse
    {
        $request->validate([
            'ip' => 'required|ip'
        ]);

        $info = ExternalApiService::getIpInfo($request->ip);

        if ($info) {
            return response()->json([
                'success' => true,
                'data' => $info
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Nije moguće dobiti informacije o IP adresi'
        ], 400);
    }

    /**
     * Dobavi informacije o domenu
     */
    public function domainInfo(Request $request): JsonResponse
    {
        $request->validate([
            'domain' => 'required|string|max:255'
        ]);

        $info = ExternalApiService::getDomainInfo($request->domain);

        if ($info) {
            return response()->json([
                'success' => true,
                'data' => $info
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Nije moguće dobiti informacije o domenu'
        ], 400);
    }

    /**
     * Dobavi cenu kriptovalute
     */
    public function cryptoPrice(Request $request): JsonResponse
    {
        $request->validate([
            'symbol' => 'nullable|string|max:10'
        ]);

        $price = ExternalApiService::getCryptoPrice($request->symbol ?? 'BTC');

        if ($price) {
            return response()->json([
                'success' => true,
                'data' => $price
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Nije moguće dobiti cenu kriptovalute'
        ], 400);
    }

    /**
     * Dobavi informacije o YouTube videu
     */
    public function youtubeInfo(Request $request): JsonResponse
    {
        $request->validate([
            'video_id' => 'required|string|max:20'
        ]);

        $info = ExternalApiService::getYouTubeInfo($request->video_id);

        if ($info) {
            return response()->json([
                'success' => true,
                'data' => $info
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Nije moguće dobiti informacije o YouTube videu'
        ], 400);
    }

    /**
     * Dobavi informacije o GitHub repozitorijumu
     */
    public function githubRepo(Request $request): JsonResponse
    {
        $request->validate([
            'owner' => 'required|string|max:100',
            'repo' => 'required|string|max:100'
        ]);

        $info = ExternalApiService::getGitHubRepo($request->owner, $request->repo);

        if ($info) {
            return response()->json([
                'success' => true,
                'data' => $info
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Nije moguće dobiti informacije o GitHub repozitorijumu'
        ], 400);
    }

    /**
     * Dobavi sve dostupne API-je
     */
    public function availableApis(): JsonResponse
    {
        $apis = [
            [
                'name' => 'Weather API',
                'endpoint' => '/api/external/weather',
                'method' => 'GET',
                'parameters' => ['city' => 'string'],
                'description' => 'Dobavi vremenske informacije za grad'
            ],
            [
                'name' => 'Translation API',
                'endpoint' => '/api/external/translate',
                'method' => 'POST',
                'parameters' => [
                    'text' => 'string',
                    'from' => 'string (optional)',
                    'to' => 'string (optional)'
                ],
                'description' => 'Prevedi tekst'
            ],
            [
                'name' => 'News API',
                'endpoint' => '/api/external/news',
                'method' => 'GET',
                'parameters' => [
                    'category' => 'string (optional)',
                    'limit' => 'integer (optional)'
                ],
                'description' => 'Dobavi vesti'
            ],
            [
                'name' => 'Currency API',
                'endpoint' => '/api/external/currency',
                'method' => 'POST',
                'parameters' => [
                    'amount' => 'numeric',
                    'from' => 'string',
                    'to' => 'string'
                ],
                'description' => 'Konvertuj valutu'
            ],
            [
                'name' => 'IP Info API',
                'endpoint' => '/api/external/ip-info',
                'method' => 'GET',
                'parameters' => ['ip' => 'string'],
                'description' => 'Dobavi informacije o IP adresi'
            ],
            [
                'name' => 'Domain Info API',
                'endpoint' => '/api/external/domain-info',
                'method' => 'GET',
                'parameters' => ['domain' => 'string'],
                'description' => 'Dobavi informacije o domenu'
            ],
            [
                'name' => 'Crypto Price API',
                'endpoint' => '/api/external/crypto-price',
                'method' => 'GET',
                'parameters' => ['symbol' => 'string (optional)'],
                'description' => 'Dobavi cenu kriptovalute'
            ],
            [
                'name' => 'YouTube Info API',
                'endpoint' => '/api/external/youtube-info',
                'method' => 'GET',
                'parameters' => ['video_id' => 'string'],
                'description' => 'Dobavi informacije o YouTube videu'
            ],
            [
                'name' => 'GitHub Repo API',
                'endpoint' => '/api/external/github-repo',
                'method' => 'GET',
                'parameters' => [
                    'owner' => 'string',
                    'repo' => 'string'
                ],
                'description' => 'Dobavi informacije o GitHub repozitorijumu'
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $apis
        ]);
    }
} 