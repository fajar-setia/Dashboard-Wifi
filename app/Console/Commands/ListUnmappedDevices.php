<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ListUnmappedDevices extends Command
{
    protected $signature = 'devices:list-unmapped 
                            {--export : Export to CSV}
                            {--csv-path= : Custom CSV path for location data}';

    protected $description = 'List devices yang belum ada mapping lokasi';

    private function normalizeSn(?string $sn): ?string
    {
        if (!$sn) return null;
        $sn = trim($sn);
        if (str_contains($sn, '-')) {
            $sn = trim(substr($sn, strrpos($sn, '-') + 1));
        }
        return strtoupper(preg_replace('/\s+/', '', $sn));
    }

    private function loadOntLocations(): array
    {
        // Cek multiple paths
        $possiblePaths = [
            $this->option('csv-path'),
            storage_path('app/public/ACSfiks.csv'),
            public_path('storage/ACSfiks.csv'),
            storage_path('app/ACSfiks.csv'),
            base_path('storage/ACSfiks.csv'),
        ];
        
        $csvPath = null;
        foreach ($possiblePaths as $path) {
            if ($path && file_exists($path)) {
                $csvPath = $path;
                break;
            }
        }
        
        if (!$csvPath) {
            return [];
        }

        $lines = array_filter(array_map('trim', file($csvPath)));
        if (count($lines) < 2) return [];

        $header = array_map(
            fn($h) => strtolower(str_replace(' ', '_', trim($h))),
            str_getcsv(array_shift($lines))
        );

        $locations = [];
        foreach ($lines as $line) {
            $row = str_getcsv($line);
            if (count($row) < count($header)) {
                $row = array_pad($row, count($header), null);
            }
            
            $data = array_combine($header, $row);
            if (!empty($data['sn'])) {
                $sn = $this->normalizeSn($data['sn']);
                $locations[$sn] = true;
            }
        }

        return $locations;
    }

    public function handle()
    {
        $this->info('🔍 Mencari device yang belum ada lokasi...');
        $this->newLine();

        try {
            // Get devices from API
            $onuService = app(\App\Services\OnuApiService::class);
            $onuData = $onuService->getAllOnu();

            // Load existing locations
            $existingLocations = $this->loadOntLocations();

            $this->info("📊 Total device dari API: " . count($onuData));
            $this->info("📊 Total lokasi di CSV: " . count($existingLocations));
            $this->newLine();

            // Find unmapped devices
            $unmapped = [];
            foreach ($onuData as $onu) {
                $sn = $this->normalizeSn($onu['sn'] ?? '');
                if ($sn && !isset($existingLocations[$sn])) {
                    $unmapped[] = [
                        'sn' => $sn,
                        'original_sn' => $onu['sn'] ?? '',
                        'model' => $onu['model'] ?? '-',
                        'ip' => $onu['ip'] ?? '-',
                        'state' => $onu['state'] ?? 'unknown',
                    ];
                }
            }

            if (empty($unmapped)) {
                $this->info('✅ Semua device sudah ada lokasi!');
                return 0;
            }

            $this->warn("⚠️  Ditemukan " . count($unmapped) . " device TANPA lokasi:");
            $this->newLine();

            // Display table
            $this->table(
                ['SN', 'Model', 'IP', 'Status'],
                array_map(fn($d) => [
                    $d['sn'],
                    $d['model'],
                    $d['ip'],
                    $d['state'],
                ], $unmapped)
            );

            // Export jika diminta
            if ($this->option('export')) {
                $this->newLine();
                $this->info('📝 Exporting to CSV...');
                
                $exportPath = storage_path('app/devices_unmapped.csv');
                $csv = fopen($exportPath, 'w');
                
                fputcsv($csv, ['No', 'Nama Lokasi', 'Kemantren', 'Kelurahan', 'RT', 'RW', 'ID Lifemedia', 'IP', 'SN']);
                
                foreach ($unmapped as $index => $device) {
                    fputcsv($csv, [
                        $index + 1,
                        '', // Nama Lokasi - ISI MANUAL
                        '', // Kemantren - ISI MANUAL
                        '', // Kelurahan - ISI MANUAL
                        '',
                        '',
                        '',
                        $device['ip'],
                        $device['sn'],
                    ]);
                }
                
                fclose($csv);
                
                $this->info("✅ Export selesai: {$exportPath}");
                $this->newLine();
                $this->warn('📝 NEXT STEPS:');
                $this->line('1. Buka file: ' . $exportPath);
                $this->line('2. Isi kolom Nama Lokasi, Kemantren, Kelurahan');
                $this->line('3. Append ke ACSfiks.csv (jangan replace!)');
                $this->line('4. Upload ke storage/app/public/ACSfiks.csv');
                $this->line('5. Run: php artisan cache:clear');
            } else {
                $this->newLine();
                $this->comment('💡 Gunakan --export untuk export ke CSV');
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }
}