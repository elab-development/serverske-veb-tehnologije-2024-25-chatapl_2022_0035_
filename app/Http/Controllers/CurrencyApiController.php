<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CurrencyApiController extends Controller
{
    protected $apiKey;
    protected $baseUrl = 'https://api.exchangerate-api.com/v4/latest';

    public function __construct()
    {
        $this->apiKey = config('services.exchangerate.key', env('EXCHANGERATE_API_KEY'));
    }

    /**
     * Convert currency from one to another.
     */
    public function convertCurrency(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'nullable|date|before_or_equal:today'
        ]);

        $from = strtoupper($request->from);
        $to = strtoupper($request->to);
        $amount = $request->amount;
        $date = $request->date ?? now()->format('Y-m-d');

        $cacheKey = "currency_{$from}_{$to}_{$date}";

        try {
            // Check cache first
            if (Cache::has($cacheKey)) {
                $rateData = Cache::get($cacheKey);
                $convertedAmount = $amount * $rateData['rate'];
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'from' => $from,
                        'to' => $to,
                        'amount' => $amount,
                        'converted_amount' => round($convertedAmount, 2),
                        'rate' => $rateData['rate'],
                        'date' => $date,
                        'cached' => true
                    ]
                ]);
            }

            // Fetch from API
            $response = Http::get("{$this->baseUrl}/{$from}");

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch exchange rate',
                    'error' => $response->json()
                ], 400);
            }

            $data = $response->json();
            $rate = $data['rates'][$to] ?? null;

            if (!$rate) {
                return response()->json([
                    'success' => false,
                    'message' => "Exchange rate not available for {$to}"
                ], 400);
            }

            $convertedAmount = $amount * $rate;

            // Cache for 1 hour
            Cache::put($cacheKey, [
                'rate' => $rate,
                'date' => $data['date']
            ], 3600);

            return response()->json([
                'success' => true,
                'data' => [
                    'from' => $from,
                    'to' => $to,
                    'amount' => $amount,
                    'converted_amount' => round($convertedAmount, 2),
                    'rate' => $rate,
                    'date' => $data['date'],
                    'cached' => false
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Currency API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Currency service temporarily unavailable'
            ], 500);
        }
    }

    /**
     * Get all available currencies.
     */
    public function getAvailableCurrencies(): JsonResponse
    {
        $cacheKey = 'available_currencies';

        try {
            // Check cache first
            if (Cache::has($cacheKey)) {
                $currencies = Cache::get($cacheKey);
                return response()->json([
                    'success' => true,
                    'data' => $currencies,
                    'cached' => true
                ]);
            }

            // Fetch from API
            $response = Http::get("{$this->baseUrl}/USD");

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch currencies'
                ], 400);
            }

            $data = $response->json();
            $currencies = $this->formatCurrencies($data['rates']);

            // Cache for 24 hours
            Cache::put($cacheKey, $currencies, 86400);

            return response()->json([
                'success' => true,
                'data' => $currencies,
                'cached' => false
            ]);

        } catch (\Exception $e) {
            Log::error('Currency list API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Currency service temporarily unavailable'
            ], 500);
        }
    }

    /**
     * Get exchange rates for multiple currencies.
     */
    public function getMultipleRates(Request $request): JsonResponse
    {
        $request->validate([
            'base' => 'required|string|size:3',
            'currencies' => 'required|array|min:1|max:10',
            'currencies.*' => 'string|size:3'
        ]);

        $base = strtoupper($request->base);
        $currencies = array_map('strtoupper', $request->currencies);

        $cacheKey = "rates_{$base}_" . implode('_', $currencies);

        try {
            // Check cache first
            if (Cache::has($cacheKey)) {
                $rates = Cache::get($cacheKey);
                return response()->json([
                    'success' => true,
                    'data' => $rates,
                    'cached' => true
                ]);
            }

            // Fetch from API
            $response = Http::get("{$this->baseUrl}/{$base}");

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch exchange rates'
                ], 400);
            }

            $data = $response->json();
            $rates = [];

            foreach ($currencies as $currency) {
                if (isset($data['rates'][$currency])) {
                    $rates[$currency] = [
                        'code' => $currency,
                        'rate' => $data['rates'][$currency],
                        'inverse_rate' => 1 / $data['rates'][$currency]
                    ];
                }
            }

            // Cache for 1 hour
            Cache::put($cacheKey, $rates, 3600);

            return response()->json([
                'success' => true,
                'data' => [
                    'base' => $base,
                    'date' => $data['date'],
                    'rates' => $rates
                ],
                'cached' => false
            ]);

        } catch (\Exception $e) {
            Log::error('Multiple rates API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Currency service temporarily unavailable'
            ], 500);
        }
    }

    /**
     * Get historical exchange rates.
     */
    public function getHistoricalRates(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3',
            'start_date' => 'required|date|before_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date|before_or_equal:today'
        ]);

        $from = strtoupper($request->from);
        $to = strtoupper($request->to);
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $cacheKey = "historical_{$from}_{$to}_{$startDate}_{$endDate}";

        try {
            // Check cache first
            if (Cache::has($cacheKey)) {
                $historicalData = Cache::get($cacheKey);
                return response()->json([
                    'success' => true,
                    'data' => $historicalData,
                    'cached' => true
                ]);
            }

            // Fetch historical data
            $historicalData = $this->fetchHistoricalData($from, $to, $startDate, $endDate);

            // Cache for 24 hours
            Cache::put($cacheKey, $historicalData, 86400);

            return response()->json([
                'success' => true,
                'data' => $historicalData,
                'cached' => false
            ]);

        } catch (\Exception $e) {
            Log::error('Historical rates API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Historical data service temporarily unavailable'
            ], 500);
        }
    }

    /**
     * Get currency statistics.
     */
    public function getCurrencyStats(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3',
            'days' => 'nullable|integer|min:7|max:365'
        ]);

        $from = strtoupper($request->from);
        $to = strtoupper($request->to);
        $days = $request->days ?? 30;

        $endDate = now()->format('Y-m-d');
        $startDate = now()->subDays($days)->format('Y-m-d');

        try {
            $historicalData = $this->fetchHistoricalData($from, $to, $startDate, $endDate);
            
            $stats = [
                'average_rate' => $this->calculateAverageRate($historicalData['rates']),
                'highest_rate' => $this->findHighestRate($historicalData['rates']),
                'lowest_rate' => $this->findLowestRate($historicalData['rates']),
                'volatility' => $this->calculateVolatility($historicalData['rates']),
                'trend' => $this->calculateTrend($historicalData['rates'])
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'from' => $from,
                    'to' => $to,
                    'period' => $days . ' days',
                    'statistics' => $stats,
                    'rates' => $historicalData['rates']
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate currency statistics'
            ], 500);
        }
    }

    /**
     * Format currencies for response.
     */
    protected function formatCurrencies(array $rates): array
    {
        $currencies = [];
        
        foreach ($rates as $code => $rate) {
            $currencies[] = [
                'code' => $code,
                'name' => $this->getCurrencyName($code),
                'rate_to_usd' => $rate
            ];
        }

        // Sort by code
        usort($currencies, function($a, $b) {
            return $a['code'] <=> $b['code'];
        });

        return $currencies;
    }

    /**
     * Get currency name by code.
     */
    protected function getCurrencyName(string $code): string
    {
        $names = [
            'USD' => 'US Dollar',
            'EUR' => 'Euro',
            'GBP' => 'British Pound',
            'JPY' => 'Japanese Yen',
            'AUD' => 'Australian Dollar',
            'CAD' => 'Canadian Dollar',
            'CHF' => 'Swiss Franc',
            'CNY' => 'Chinese Yuan',
            'SEK' => 'Swedish Krona',
            'NZD' => 'New Zealand Dollar',
            'RSD' => 'Serbian Dinar',
            'BAM' => 'Bosnia and Herzegovina Convertible Mark',
            'HRK' => 'Croatian Kuna',
            'MKD' => 'Macedonian Denar',
            'ALL' => 'Albanian Lek'
        ];

        return $names[$code] ?? $code;
    }

    /**
     * Fetch historical data.
     */
    protected function fetchHistoricalData(string $from, string $to, string $startDate, string $endDate): array
    {
        $rates = [];
        $currentDate = $startDate;

        while ($currentDate <= $endDate) {
            $cacheKey = "currency_{$from}_{$to}_{$currentDate}";
            
            if (Cache::has($cacheKey)) {
                $rateData = Cache::get($cacheKey);
                $rates[$currentDate] = $rateData['rate'];
            } else {
                // Fetch for specific date
                $response = Http::get("https://api.exchangerate-api.com/v4/latest/{$from}");
                
                if ($response->successful()) {
                    $data = $response->json();
                    $rate = $data['rates'][$to] ?? null;
                    
                    if ($rate) {
                        $rates[$currentDate] = $rate;
                        Cache::put($cacheKey, ['rate' => $rate, 'date' => $currentDate], 86400);
                    }
                }
            }

            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
        }

        return [
            'from' => $from,
            'to' => $to,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'rates' => $rates
        ];
    }

    /**
     * Calculate average rate.
     */
    protected function calculateAverageRate(array $rates): float
    {
        return round(array_sum($rates) / count($rates), 4);
    }

    /**
     * Find highest rate.
     */
    protected function findHighestRate(array $rates): array
    {
        $maxRate = max($rates);
        $date = array_search($maxRate, $rates);
        
        return [
            'rate' => $maxRate,
            'date' => $date
        ];
    }

    /**
     * Find lowest rate.
     */
    protected function findLowestRate(array $rates): array
    {
        $minRate = min($rates);
        $date = array_search($minRate, $rates);
        
        return [
            'rate' => $minRate,
            'date' => $date
        ];
    }

    /**
     * Calculate volatility.
     */
    protected function calculateVolatility(array $rates): float
    {
        $mean = array_sum($rates) / count($rates);
        $variance = 0;
        
        foreach ($rates as $rate) {
            $variance += pow($rate - $mean, 2);
        }
        
        $variance /= count($rates);
        
        return round(sqrt($variance), 4);
    }

    /**
     * Calculate trend.
     */
    protected function calculateTrend(array $rates): string
    {
        $values = array_values($rates);
        $first = $values[0];
        $last = end($values);
        
        $change = (($last - $first) / $first) * 100;
        
        if ($change > 1) {
            return 'increasing';
        } elseif ($change < -1) {
            return 'decreasing';
        } else {
            return 'stable';
        }
    }
} 