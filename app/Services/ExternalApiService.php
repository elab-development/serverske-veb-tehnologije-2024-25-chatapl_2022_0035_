<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class ExternalApiService
{
    /**
     * Dobavi vremenske informacije za grad
     */
    public static function getWeather(string $city): ?array
    {
        try {
            $cacheKey = "weather_{$city}";
            
            return Cache::remember($cacheKey, 1800, function () use ($city) {
                $response = Http::get('https://api.openweathermap.org/data/2.5/weather', [
                    'q' => $city,
                    'appid' => config('services.openweathermap.key'),
                    'units' => 'metric',
                    'lang' => 'sr'
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return [
                        'city' => $data['name'],
                        'temperature' => round($data['main']['temp']),
                        'description' => $data['weather'][0]['description'],
                        'humidity' => $data['main']['humidity'],
                        'wind_speed' => $data['wind']['speed'],
                        'icon' => $data['weather'][0]['icon']
                    ];
                }
                
                return null;
            });
        } catch (Exception $e) {
            Log::error('Error fetching weather data: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Prevedi tekst
     */
    public static function translateText(string $text, string $from = 'auto', string $to = 'en'): ?string
    {
        try {
            $cacheKey = "translation_{$from}_{$to}_" . md5($text);
            
            return Cache::remember($cacheKey, 3600, function () use ($text, $from, $to) {
                $response = Http::post('https://translation.googleapis.com/language/translate/v2', [
                    'q' => $text,
                    'source' => $from,
                    'target' => $to,
                    'key' => config('services.google.translate_key')
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['data']['translations'][0]['translatedText'] ?? null;
                }
                
                return null;
            });
        } catch (Exception $e) {
            Log::error('Error translating text: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Dobavi vesti
     */
    public static function getNews(string $category = 'general', int $limit = 5): array
    {
        try {
            $cacheKey = "news_{$category}_{$limit}";
            
            return Cache::remember($cacheKey, 1800, function () use ($category, $limit) {
                $response = Http::get('https://newsapi.org/v2/top-headlines', [
                    'country' => 'rs',
                    'category' => $category,
                    'pageSize' => $limit,
                    'apiKey' => config('services.newsapi.key')
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return array_map(function ($article) {
                        return [
                            'title' => $article['title'],
                            'description' => $article['description'],
                            'url' => $article['url'],
                            'published_at' => $article['publishedAt'],
                            'source' => $article['source']['name']
                        ];
                    }, $data['articles'] ?? []);
                }
                
                return [];
            });
        } catch (Exception $e) {
            Log::error('Error fetching news: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Konvertuj valutu
     */
    public static function convertCurrency(float $amount, string $from, string $to): ?array
    {
        try {
            $cacheKey = "currency_{$from}_{$to}";
            
            $rate = Cache::remember($cacheKey, 3600, function () use ($from, $to) {
                $response = Http::get('https://api.exchangerate-api.com/v4/latest/' . strtoupper($from));
                
                if ($response->successful()) {
                    $data = $response->json();
                    return $data['rates'][strtoupper($to)] ?? null;
                }
                
                return null;
            });

            if ($rate) {
                return [
                    'from' => $from,
                    'to' => $to,
                    'amount' => $amount,
                    'converted_amount' => round($amount * $rate, 2),
                    'rate' => $rate
                ];
            }
            
            return null;
        } catch (Exception $e) {
            Log::error('Error converting currency: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Dobavi informacije o IP adresi
     */
    public static function getIpInfo(string $ip): ?array
    {
        try {
            $cacheKey = "ip_info_{$ip}";
            
            return Cache::remember($cacheKey, 86400, function () use ($ip) {
                $response = Http::get("http://ip-api.com/json/{$ip}");
                
                if ($response->successful()) {
                    $data = $response->json();
                    if ($data['status'] === 'success') {
                        return [
                            'country' => $data['country'],
                            'city' => $data['city'],
                            'region' => $data['regionName'],
                            'timezone' => $data['timezone'],
                            'isp' => $data['isp']
                        ];
                    }
                }
                
                return null;
            });
        } catch (Exception $e) {
            Log::error('Error fetching IP info: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Dobavi informacije o domenu
     */
    public static function getDomainInfo(string $domain): ?array
    {
        try {
            $cacheKey = "domain_info_{$domain}";
            
            return Cache::remember($cacheKey, 86400, function () use ($domain) {
                $response = Http::get("https://dns.google/resolve", [
                    'name' => $domain,
                    'type' => 'A'
                ]);
                
                if ($response->successful()) {
                    $data = $response->json();
                    return [
                        'domain' => $domain,
                        'status' => $data['Status'],
                        'answers' => $data['Answer'] ?? [],
                        'authority' => $data['Authority'] ?? []
                    ];
                }
                
                return null;
            });
        } catch (Exception $e) {
            Log::error('Error fetching domain info: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Dobavi informacije o kriptovaluti
     */
    public static function getCryptoPrice(string $symbol = 'BTC'): ?array
    {
        try {
            $cacheKey = "crypto_{$symbol}";
            
            return Cache::remember($cacheKey, 300, function () use ($symbol) {
                $response = Http::get("https://api.coingecko.com/api/v3/simple/price", [
                    'ids' => strtolower($symbol),
                    'vs_currencies' => 'usd,eur',
                    'include_24hr_change' => 'true'
                ]);
                
                if ($response->successful()) {
                    $data = $response->json();
                    $cryptoData = $data[strtolower($symbol)] ?? null;
                    
                    if ($cryptoData) {
                        return [
                            'symbol' => strtoupper($symbol),
                            'usd_price' => $cryptoData['usd'],
                            'eur_price' => $cryptoData['eur'],
                            'change_24h' => $cryptoData['usd_24h_change'] ?? 0
                        ];
                    }
                }
                
                return null;
            });
        } catch (Exception $e) {
            Log::error('Error fetching crypto price: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Dobavi informacije o YouTube videu
     */
    public static function getYouTubeInfo(string $videoId): ?array
    {
        try {
            $cacheKey = "youtube_{$videoId}";
            
            return Cache::remember($cacheKey, 3600, function () use ($videoId) {
                $response = Http::get('https://www.googleapis.com/youtube/v3/videos', [
                    'part' => 'snippet,statistics',
                    'id' => $videoId,
                    'key' => config('services.google.youtube_key')
                ]);
                
                if ($response->successful()) {
                    $data = $response->json();
                    $video = $data['items'][0] ?? null;
                    
                    if ($video) {
                        return [
                            'title' => $video['snippet']['title'],
                            'description' => $video['snippet']['description'],
                            'channel' => $video['snippet']['channelTitle'],
                            'published_at' => $video['snippet']['publishedAt'],
                            'view_count' => $video['statistics']['viewCount'] ?? 0,
                            'like_count' => $video['statistics']['likeCount'] ?? 0,
                            'thumbnail' => $video['snippet']['thumbnails']['medium']['url'] ?? null
                        ];
                    }
                }
                
                return null;
            });
        } catch (Exception $e) {
            Log::error('Error fetching YouTube info: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Dobavi informacije o GitHub repozitorijumu
     */
    public static function getGitHubRepo(string $owner, string $repo): ?array
    {
        try {
            $cacheKey = "github_{$owner}_{$repo}";
            
            return Cache::remember($cacheKey, 3600, function () use ($owner, $repo) {
                $response = Http::withHeaders([
                    'Accept' => 'application/vnd.github.v3+json',
                    'Authorization' => 'token ' . config('services.github.token')
                ])->get("https://api.github.com/repos/{$owner}/{$repo}");
                
                if ($response->successful()) {
                    $data = $response->json();
                    return [
                        'name' => $data['name'],
                        'full_name' => $data['full_name'],
                        'description' => $data['description'],
                        'language' => $data['language'],
                        'stars' => $data['stargazers_count'],
                        'forks' => $data['forks_count'],
                        'watchers' => $data['watchers_count'],
                        'open_issues' => $data['open_issues_count'],
                        'created_at' => $data['created_at'],
                        'updated_at' => $data['updated_at'],
                        'url' => $data['html_url']
                    ];
                }
                
                return null;
            });
        } catch (Exception $e) {
            Log::error('Error fetching GitHub repo: ' . $e->getMessage());
            return null;
        }
    }
} 