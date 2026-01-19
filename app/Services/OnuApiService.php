<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OnuApiService
{
    private const BASE_URL = 'http://172.16.105.3:3080';
    private const API_TOKEN = 'ce4b3eb42e6445a3993142856d325d75';
    private const TIMEOUT = 2;
    private const PARALLEL_LIMIT = 20;

    /**
     * Get Bearer token dengan caching
     */
    private function getBearerToken(): ?string
    {
        return Cache::remember('onu_bearer_token', 50 * 60, function () {
            try {
                $encoded = base64_encode('cms-web:cms-web');

                $response = Http::timeout(5)
                    ->withHeaders([
                        'Authorization' => "Basic {$encoded}",
                        'Content-Type' => 'application/x-www-form-urlencoded',
                        'TENANT-ID' => '100000',
                    ])
                    ->asForm()
                    ->post(self::BASE_URL . '/v1/oauth/token', [
                        'grant_type' => 'password',
                        'username' => 'admin',
                        'password' => 'oFuIt+U8Ugo==',
                        'scope' => 'server',
                    ]);

                if ($response->ok()) {
                    return $response->json()['access_token'] ?? null;
                }

                Log::warning('Failed to get bearer token', ['status' => $response->status()]);
                return null;
            } catch (\Throwable $e) {
                Log::error('Bearer token fetch error: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Get all ONU devices (simple data)
     */
    public function getAllOnu(): array
    {
        return Cache::remember('onu_devices_list', 60, function () {
            try {
                $response = Http::timeout(self::TIMEOUT)
                    ->withHeaders(['X-Token' => self::API_TOKEN])
                    ->post(self::BASE_URL . '/v1/openapi/cms/devices/page', [
                        'size' => 100,
                        'current' => 1,
                    ]);

                if ($response->ok()) {
                    $records = $response->json()['data']['page']['records'] ?? [];

                    return array_map(fn($o) => [
                        'sn' => $o['sn'] ?? null,
                        'model' => $o['model'] ?? null,
                        'deviceId' => $o['deviceId'] ?? null,
                        'state' => strtolower($o['tr069RunningState'] ?? 'offline'),
                    ], $records);
                }

                Log::warning('Failed to fetch ONU list', ['status' => $response->status()]);
                return [];
            } catch (\Throwable $e) {
                Log::error('ONU list fetch error: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Get WiFi clients for a single device
     */
    private function getWifiClients(string $deviceId, string $bearer): array
    {
        try {
            $url = self::BASE_URL . "/v1/cms/devices/terminal-conn-inform-wifi?deviceId={$deviceId}";

            $response = Http::timeout(self::TIMEOUT)
                ->withToken($bearer)
                ->get($url);

            if ($response->ok()) {
                return $response->json()['data'] ?? [];
            }

            return [];
        } catch (\Throwable $e) {
            // Silent fail untuk individual device
            return [];
        }
    }

    /**
     * Get all ONUs with WiFi client data (dengan cache super aggressive)
     */
    public function getAllOnuWithClients(): array
    {
        // Cache 5 menit untuk complete data
        return Cache::remember('onu_complete_data', 300, function () {
            $startTime = microtime(true);

            $bearer = $this->getBearerToken();
            if (!$bearer) {
                Log::warning('No bearer token available for WiFi fetch');
                return $this->getAllOnuWithoutClients();
            }

            $onus = $this->getAllOnu();

            // Pisahkan online dan offline
            $onlineOnus = array_filter($onus, fn($o) => $o['state'] === 'online');
            $offlineOnus = array_filter($onus, fn($o) => $o['state'] !== 'online');

            Log::info("Processing ONUs", [
                'online' => count($onlineOnus),
                'offline' => count($offlineOnus)
            ]);

            // Process online ONUs dengan parallel requests (PHP Pool)
            $onlineData = $this->fetchWifiDataParallel($onlineOnus, $bearer);

            // Offline ONUs tidak perlu fetch WiFi
            $offlineData = array_map(fn($o) => array_merge($o, ['wifiClients' => []]), $offlineOnus);

            $allData = array_merge($onlineData, $offlineData);

            $elapsed = round((microtime(true) - $startTime) * 1000, 2);
            Log::info("ONU data fetched", ['time_ms' => $elapsed, 'total' => count($allData)]);

            return $allData;
        });
    }

    /**
     * Parallel WiFi fetch menggunakan HTTP Pool
     */
    private function fetchWifiDataParallel(array $onus, string $bearer): array
    {
        if (empty($onus)) {
            return [];
        }

        // Gunakan HTTP Pool untuk parallel requests
        $responses = Http::pool(function ($pool) use ($onus, $bearer) {
            $requests = [];
            foreach ($onus as $index => $onu) {
                $url = self::BASE_URL . "/v1/cms/devices/terminal-conn-inform-wifi?deviceId={$onu['deviceId']}";
                $requests[$index] = $pool->withToken($bearer)->timeout(self::TIMEOUT)->get($url);
            }
            return $requests;
        });

        // Map responses back to ONU data
        $results = [];
        foreach ($onus as $index => $onu) {
            $wifiClients = [];

            // Check if response exists and is a Response (not an Exception)
            if (
                isset($responses[$index])
                && $responses[$index] instanceof \Illuminate\Http\Client\Response
                && $responses[$index]->successful()
            ) {
                $wifiClients = $responses[$index]->json()['data'] ?? [];
            }

            $results[] = array_merge($onu, ['wifiClients' => $wifiClients]);
        }

        return $results;
    }

    /**
     * Fallback: Get ONUs without WiFi data
     */
    private function getAllOnuWithoutClients(): array
    {
        $onus = $this->getAllOnu();
        return array_map(fn($o) => array_merge($o, ['wifiClients' => []]), $onus);
    }

    /**
     * Force refresh cache
     */
    public function refreshCache(): void
    {
        Cache::forget('onu_complete_data');
        Cache::forget('onu_devices_list');
        Cache::forget('onu_bearer_token');
    }
}
