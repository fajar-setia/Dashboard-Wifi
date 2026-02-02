<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\DashboardController;

class CollectRealtimeStats extends Command
{
    protected $signature = 'stats:collect-realtime';
    protected $description = 'Collect realtime location statistics every 5 minutes';

    public function handle()
    {
        $this->info('🔄 [' . now()->toDateTimeString() . '] Starting realtime stats collection...');
        
        try {
            $startTime = microtime(true);
            
            // Get ONU service and connections
            $this->info('📡 Fetching ONU connections...');
            $onuService = app(\App\Services\OnuApiService::class);
            $connections = $onuService->getAllOnuWithClients();
            
            if (!is_array($connections)) {
                $this->error('❌ Connections is not an array: ' . gettype($connections));
                return 1;
            }
            
            if (empty($connections)) {
                $this->warn('⚠️  No connections data available');
                Log::warning('CollectRealtimeStats: No connections data');
                return 0;
            }
            
            $this->info("📡 Found " . count($connections) . " ONU connections");
            
            // Get ONT mapping (cached)
            $this->info('📍 Loading ONT mapping...');
            
            $ontMap = cache()->remember(
                'ont_map_paket_all',
                86400,
                function() {
                    try {
                        // Read CSV directly instead of using reflection
                        return $this->readOntMapDirect();
                    } catch (\Throwable $e) {
                        $this->error('Error reading ONT map: ' . $e->getMessage());
                        Log::error('ONT map error: ' . $e->getMessage());
                        return [];
                    }
                }
            );

            if (!is_array($ontMap)) {
                $this->error('❌ ONT map is not an array: ' . gettype($ontMap));
                return 1;
            }

            if (empty($ontMap)) {
                $this->error('❌ ONT mapping not available');
                Log::error('CollectRealtimeStats: ONT mapping empty');
                return 1;
            }

            $this->info("📍 Loaded " . count($ontMap) . " ONT mappings");

            // Build stats with unique MAC tracking per location
            $this->info('🔢 Building statistics...');
            $stats = $this->buildLocationStats($connections, $ontMap);
            
            if (empty($stats)) {
                $this->warn('⚠️  No stats to save');
                Log::warning('CollectRealtimeStats: No stats generated');
                return 0;
            }

            $this->info("📊 Generated " . count($stats) . " location stats");

            // Save to database
            $today = now('Asia/Jakarta')->toDateString();
            $saved = 0;
            $updated = 0;
            
            DB::beginTransaction();
            
            try {
                foreach ($stats as $stat) {
                    // Validate stat data
                    if (!isset($stat['location']) || !isset($stat['kemantren']) || !isset($stat['sn'])) {
                        $this->warn('⚠️  Skipping invalid stat: ' . json_encode($stat));
                        continue;
                    }

                    $existing = DB::table('daily_location_stats')
                        ->where('date', $today)
                        ->where('location', $stat['location'])
                        ->where('kemantren', $stat['kemantren'])
                        ->where('sn', $stat['sn'])
                        ->first();
                    
                    if ($existing) {
                        DB::table('daily_location_stats')
                            ->where('id', $existing->id)
                            ->update([
                                'user_count' => $stat['user_count'],
                                'updated_at' => now(),
                            ]);
                        $updated++;
                    } else {
                        DB::table('daily_location_stats')->insert([
                            'date' => $today,
                            'location' => $stat['location'],
                            'kemantren' => $stat['kemantren'],
                            'sn' => $stat['sn'],
                            'user_count' => $stat['user_count'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $saved++;
                    }
                }
                
                DB::commit();
                
                $duration = round(microtime(true) - $startTime, 2);
                $totalUsers = array_sum(array_column($stats, 'user_count'));
                
                $this->info("✅ Stats collection completed successfully!");
                $this->table(
                    ['Metric', 'Value'],
                    [
                        ['New records', $saved],
                        ['Updated records', $updated],
                        ['Total locations', $saved + $updated],
                        ['Total users', $totalUsers],
                        ['Duration', $duration . 's'],
                        ['Timestamp', now()->toDateTimeString()],
                    ]
                );
                
                Log::info('Realtime stats collected successfully', [
                    'date' => $today,
                    'new' => $saved,
                    'updated' => $updated,
                    'total_users' => $totalUsers,
                    'duration' => $duration,
                ]);
                
                return 0;
                
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }
            
        } catch (\Throwable $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            $this->error('File: ' . $e->getFile() . ':' . $e->getLine());
            Log::error('CollectRealtimeStats failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return 1;
        }
    }

    /**
     * Read ONT map directly from CSV file
     */
    private function readOntMapDirect(): array
    {
        $path = public_path('storage/ACSfiks.csv');
        
        if (!is_file($path)) {
            $this->error('❌ CSV file not found at: ' . $path);
            return [];
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            $this->error('❌ Unable to open CSV file');
            return [];
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            $this->error('❌ Unable to read CSV header');
            return [];
        }

        // Build column index map
        $indexes = [];
        foreach ($header as $idx => $name) {
            $key = strtolower(trim(preg_replace('/\s+/', '_', $name)));
            $indexes[$key] = $idx;
        }

        $map = [];
        $rowNum = 1;
        
        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            
            try {
                $sn = $this->getCsvValue($row, 'sn', $indexes);
                
                if (empty($sn)) {
                    continue;
                }

                $map[$sn] = [
                    'location' => $this->getCsvValue($row, 'nama_lokasi', $indexes),
                    'kemantren' => $this->getCsvValue($row, 'kemantren', $indexes),
                    'kelurahan' => $this->getCsvValue($row, 'kelurahan', $indexes),
                    'rt' => $this->getCsvValue($row, 'rt', $indexes),
                    'rw' => $this->getCsvValue($row, 'rw', $indexes),
                    'ip' => $this->getCsvValue($row, 'ip', $indexes),
                    'pic' => $this->getCsvValue($row, 'pic', $indexes),
                    'coordinate' => $this->getCsvValue($row, 'titik_koordinat', $indexes),
                ];
            } catch (\Throwable $e) {
                $this->warn("⚠️  Error parsing row {$rowNum}: " . $e->getMessage());
                continue;
            }
        }

        fclose($handle);
        
        $this->info("✅ Loaded " . count($map) . " records from CSV");
        
        return $map;
    }

    /**
     * Safely get value from CSV row
     */
    private function getCsvValue(array $row, string $key, array $indexes): string
    {
        $idx = $indexes[$key] ?? null;
        
        if ($idx === null) {
            return '';
        }
        
        if (!isset($row[$idx])) {
            return '';
        }
        
        $value = $row[$idx];
        
        // Handle array values (shouldn't happen, but just in case)
        if (is_array($value)) {
            return implode(', ', $value);
        }
        
        return trim((string)$value);
    }

    /**
     * Build location statistics with unique MAC tracking per SN
     */
    private function buildLocationStats(array $connections, array $ontMap): array
    {
        $stats = [];
        $skipped = 0;
        
        foreach ($connections as $c) {
            try {
                if (!is_array($c)) {
                    $this->warn('⚠️  Connection is not an array: ' . gettype($c));
                    $skipped++;
                    continue;
                }

                $sn = isset($c['sn']) ? strtoupper(trim($c['sn'])) : '';
                
                if (empty($sn)) {
                    $skipped++;
                    continue;
                }
                
                if (!isset($ontMap[$sn])) {
                    $skipped++;
                    continue;
                }
                
                $info = $ontMap[$sn];
                
                if (!is_array($info)) {
                    $this->warn('⚠️  ONT info for SN ' . $sn . ' is not an array: ' . gettype($info));
                    $skipped++;
                    continue;
                }
                
                if (empty($info['location'])) {
                    $skipped++;
                    continue;
                }
                
                // Safely get WiFi clients
                $wifiClients = isset($c['wifiClients']) && is_array($c['wifiClients']) 
                    ? $c['wifiClients'] 
                    : [];
                
                $allClients = array_merge(
                    isset($wifiClients['5G']) && is_array($wifiClients['5G']) ? $wifiClients['5G'] : [],
                    isset($wifiClients['2_4G']) && is_array($wifiClients['2_4G']) ? $wifiClients['2_4G'] : [],
                    isset($wifiClients['unknown']) && is_array($wifiClients['unknown']) ? $wifiClients['unknown'] : []
                );
                
                // Track unique MAC addresses
                $uniqueMacs = [];
                
                foreach ($allClients as $client) {
                    if (!is_array($client)) {
                        continue;
                    }

                    $mac = isset($client['wifi_terminal_mac']) 
                        ? strtoupper(trim($client['wifi_terminal_mac'])) 
                        : '';
                    
                    $mac = preg_replace('/[^A-F0-9:]/', '', $mac);
                    
                    if (!empty($mac) && strlen($mac) >= 12) {
                        $uniqueMacs[$mac] = true;
                    }
                }
                
                $userCount = count($uniqueMacs);
                
                // Only add if there are users
                if ($userCount > 0) {
                    $stats[] = [
                        'location' => $info['location'],
                        'kemantren' => $info['kemantren'] ?? '-',
                        'sn' => $sn,
                        'user_count' => $userCount,
                    ];
                }
                
            } catch (\Throwable $e) {
                $this->warn('⚠️  Error processing connection: ' . $e->getMessage());
                $skipped++;
                continue;
            }
        }
        
        if ($skipped > 0) {
            $this->warn("⚠️  Skipped $skipped ONUs (no mapping, no location, or errors)");
        }
        
        return $stats;
    }
}