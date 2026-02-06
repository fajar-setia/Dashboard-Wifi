<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ExportProductionSerialNumbers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:production-sn 
                            {--format=csv : Output format (csv or json)}
                            {--output= : Custom output path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export Serial Numbers dari production API untuk mapping lokasi';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Memulai export Serial Numbers dari production...');
        $this->newLine();

        try {
            // Get ONU service
            $onuService = app(\App\Services\OnuApiService::class);
            
            $this->info('📡 Mengambil data dari API...');
            $onuData = $onuService->getAllOnu();
            
            if (empty($onuData)) {
                $this->error('❌ Tidak ada data dari API!');
                return 1;
            }

            $this->info("✅ Berhasil mendapatkan " . count($onuData) . " device");
            $this->newLine();

            // Determine output path
            $format = $this->option('format');
            $outputPath = $this->option('output') ?: storage_path("app/production_sn_export.{$format}");

            // Export based on format
            if ($format === 'csv') {
                $this->exportToCsv($onuData, $outputPath);
            } else {
                $this->exportToJson($onuData, $outputPath);
            }

            $this->newLine();
            $this->info("✅ Export selesai!");
            $this->info("📁 File: {$outputPath}");
            $this->newLine();
            
            $this->warn('📝 NEXT STEPS:');
            $this->line('1. Buka file yang di-export');
            $this->line('2. Isi kolom Nama Lokasi, Kelurahan, dan Kemantren');
            $this->line('3. Save as ACSfiks.csv');
            $this->line('4. Upload ke storage/app/public/ACSfiks.csv');
            $this->line('5. Run: php artisan cache:clear');
            
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }

    private function exportToCsv(array $onuData, string $outputPath)
    {
        $this->info('📝 Membuat file CSV...');
        
        $csv = fopen($outputPath, 'w');
        
        // Header - sesuaikan dengan format CSV lama
        fputcsv($csv, [
            'No',
            'Nama Lokasi',
            'Kemantren',
            'Kelurahan',
            'RT',
            'RW',
            'ID Lifemedia',
            'IP',
            'SN',
            'Titik Koordinat',
            'PIC',
            'Kunjungan 2024',
            'Kunjungan 2025',
            'Total Kunjungan'
        ]);
        
        // Progress bar
        $bar = $this->output->createProgressBar(count($onuData));
        $bar->start();
        
        // Data
        foreach ($onuData as $index => $onu) {
            // Normalize SN (remove prefix model)
            $sn = $onu['sn'] ?? '';
            if (str_contains($sn, '-')) {
                $sn = trim(substr($sn, strrpos($sn, '-') + 1));
            }
            
            fputcsv($csv, [
                $index + 1,                 // No
                '',                         // Nama Lokasi - KOSONGKAN untuk diisi manual
                '',                         // Kemantren - KOSONGKAN
                '',                         // Kelurahan - KOSONGKAN
                '',                         // RT
                '',                         // RW
                '',                         // ID Lifemedia
                $onu['ip'] ?? '',          // IP
                $sn,                        // SN (sudah normalized)
                '',                         // Titik Koordinat
                '',                         // PIC
                '',                         // Kunjungan 2024
                '',                         // Kunjungan 2025
                '',                         // Total Kunjungan
            ]);
            
            $bar->advance();
        }
        
        $bar->finish();
        fclose($csv);
        $this->newLine();
    }

    private function exportToJson(array $onuData, string $outputPath)
    {
        $this->info('📝 Membuat file JSON...');
        
        $data = [];
        
        $bar = $this->output->createProgressBar(count($onuData));
        $bar->start();
        
        foreach ($onuData as $onu) {
            // Normalize SN
            $sn = $onu['sn'] ?? '';
            if (str_contains($sn, '-')) {
                $sn = trim(substr($sn, strrpos($sn, '-') + 1));
            }
            
            $data[] = [
                'sn' => $sn,
                'original_sn' => $onu['sn'] ?? '',
                'model' => $onu['model'] ?? '',
                'ip' => $onu['ip'] ?? '',
                'state' => $onu['state'] ?? '',
                'nama_lokasi' => '',        // Kosongkan untuk diisi manual
                'kelurahan' => '',
                'kemantren' => '',
            ];
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        
        file_put_contents(
            $outputPath,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
}