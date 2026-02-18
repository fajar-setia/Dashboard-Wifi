<?php

namespace App\Http\Controllers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class DashboardController extends Controller
{
    public function index()
    {
        /* ---------- default ---------- */
        $onus = [];
        $connections = [];
        $totalAp = 0;
        $userOnline = 0;
        $totalUser = 0;
        $logActivity = collect();

        /* ---------- Data dari OnuApiService (native PHP, no middleware!) ---------- */
        try {
            $onuService = app(\App\Services\OnuApiService::class);

            // Get simple ONU list (cached 60s)
            $onus = $onuService->getAllOnu();
            $totalAp = count($onus);

            // Get complete data with WiFi clients (cached 5 min)
            $connections = $onuService->getAllOnuWithClients();
            $ontMap = cache()->remember(
            'ont_map_paket_all',
            86400,
            fn() => $this->readOntMap()
        );
            $realtimeLocationStats =
            $this->buildRealtimeStats($connections, $ontMap);


            $totalAp = is_array($onus) ? count($onus) : 0;

            $weeklyLocationByDay = [];
            $today = now();
            $monday = $today->copy()->startOfWeek();

            for ($i = 0; $i < 7; $i++) {
                $date = $monday->copy()->addDays($i)->toDateString();
                $weeklyLocationByDay[$date] = [];
            }

            $locationBuckets = [];

            foreach ($connections as $c) {
                $sn = strtoupper(trim($c['sn'] ?? ''));
                if ($sn === '') continue;

                $info = $ontMap[$sn] ?? null;
                if (!$info || empty($info['location'])) continue;

                $clients = array_merge(
                    $c['wifiClients']['5G'] ?? [],
                    $c['wifiClients']['2_4G'] ?? [],
                    $c['wifiClients']['unknown'] ?? []
                );

                $macs = [];
                foreach ($clients as $cl) {
                    $mac = strtoupper(trim($cl['wifi_terminal_mac'] ?? ''));
                    $mac = preg_replace('/[^A-F0-9:]/i', '', $mac);
                    if ($mac !== '') {
                        $macs[$mac] = true;
                    }
                }

                $key = $info['location'].'|'.$info['kemantren'];

                if (!isset($locationBuckets[$key])) {
                    $locationBuckets[$key] = [
                        'location' => $info['location'],
                        'kemantren' => $info['kemantren'],
                        'total' => 0,
                    ];
                }

                $locationBuckets[$key]['total'] += count($macs);
            }

            $todayKey = now()->toDateString();
            $weeklyLocationByDay[$todayKey] = array_values($locationBuckets);

            // Hitung unique users berdasarkan MAC address untuk menghindari duplikasi
            $uniqueMacSet = [];
            if (is_array($connections)) {
                foreach ($connections as $c) {
                    if (!is_array($c) || !isset($c['wifiClients'])) {
                        continue;
                    }
                    $wifi = $c['wifiClients'];
                    $allClients = array_merge(
                        $wifi['5G'] ?? [],
                        $wifi['2_4G'] ?? [],
                        $wifi['unknown'] ?? []
                    );

                    foreach ($allClients as $client) {
                        if (!isset($client['wifi_terminal_mac']))
                            continue;
                        $mac = strtoupper(trim($client['wifi_terminal_mac']));
                        $mac = preg_replace('/[^A-F0-9:]/i', '', $mac);
                        if ($mac === '')
                            continue;
                        $uniqueMacSet[$mac] = true;
                    }
                }
            }
            $userOnline = count($uniqueMacSet);
        } catch (\Throwable $e) {
            Log::error('DashboardController@index : ' . $e->getMessage());
        }
        $totalUser = $userOnline;

        $capacityPerOnt = 2;
        $userCapacity = ($totalAp ?? 0) * $capacityPerOnt;

        /* ---------- log activity (dummy) ---------- */
        if (is_array($connections)) {
            foreach ($connections as $c) {
                foreach ($c['wifiClients']['unknown'] ?? [] as $cl) {
                    $logActivity->push((object) [
                        'time' => now()->subMinutes(rand(1, 30)),
                        'user' => $cl['wifi_terminal_name'] ?? 'Unknown',
                        'ap' => $c['sn'] ?? null,
                        'action' => 'connected',
                    ]);
                }
            }
        }

        /* ---------- Google-Sheet sebagai sumber utama chart ---------- */
        $today = now();
        $monday = $today->copy()->startOfWeek();
        $weekDates = [];
        for ($i = 0; $i < 7; $i++) {
            $weekDates[] = $monday->copy()->addDays($i)->toDateString();
        }

        $endOfWeek = $monday->copy()->endOfWeek();

        $stats = DB::table('daily_user_stats')
            ->whereBetween('date', [
                $monday->toDateString(),
                $endOfWeek->toDateString()
            ])
            ->pluck('user_count', 'date');

        $weeklyData = array_fill(0, 7, 0);
        foreach ($weekDates as $i => $dateStr) {
            $weeklyData[$i] = (int) ($stats[$dateStr] ?? 0);
        }

        $dayNames = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
        $dailyLabels = [];
        foreach ($weekDates as $d) {
            try {
                $dt = Carbon::parse($d);
                $dailyLabels[] = $dayNames[$dt->dayOfWeek] ?? $d;
            } catch (\Throwable $e) {
                $dailyLabels[] = $d;
            }
        }

        $dailyUsers = [
            'labels' => $dailyLabels,
            'raw_labels' => $weekDates,
            'data' => $weeklyData,
        ];

        /* ---------- baca Sheet paket 110 & 200 untuk mapping SN ---------- */
        $ontMap = cache()->remember(
            'ont_map_paket_all',
            86400,
            fn() => $this->readOntMap()
        );

        /* ---------- susun summary lokasi + preview clients (optimize memory) ---------- */
        $locationsMap = [];
        $globalMacSet = [];

        if (is_array($connections)) {
            foreach ($connections as $c) {
                $sn = strtoupper(trim($c['sn'] ?? ''));
                $info = $ontMap[$sn] ?? null;

                $clients = array_merge(
                    $c['wifiClients']['5G'] ?? [],
                    $c['wifiClients']['2_4G'] ?? [],
                    $c['wifiClients']['unknown'] ?? []
                );

                if (!isset($locationsMap[$sn])) {
                    $locationsMap[$sn] = [
                        'sn' => $sn,
                        'location' => $info['location'] ?? '-',
                        'kemantren' => $info['kemantren'] ?? '-',
                        'kelurahan' => $info['kelurahan'] ?? '-',
                        'rt' => $info['rt'] ?? '-',
                        'rw' => $info['rw'] ?? '-',
                        'clients_preview' => [],
                        'count' => 0,
                        'unique_macs' => [],
                    ];
                }

                foreach ($clients as $cl) {
                    $mac = strtoupper(trim($cl['wifi_terminal_mac'] ?? ''));
                    $mac = preg_replace('/[^A-F0-9:]/i', '', $mac);

                    if ($mac === '' || isset($globalMacSet[$mac])) {
                        continue;
                    }

                    $globalMacSet[$mac] = true;
                    $locationsMap[$sn]['unique_macs'][$mac] = true;
                    $locationsMap[$sn]['count']++;

                    if (count($locationsMap[$sn]['clients_preview']) < 5) {
                        $locationsMap[$sn]['clients_preview'][] = [
                            'wifi_terminal_name' => $cl['wifi_terminal_name'] ?? 'Unknown',
                            'wifi_terminal_ip' => $cl['wifi_terminal_ip'] ?? '-',
                            'wifi_terminal_mac' => $cl['wifi_terminal_mac'] ?? '-',
                        ];
                    }
                }
            }
        }

        $locationsCollection = collect(array_values($locationsMap))->sortByDesc('count')->values();

        $locPerPage = request('locPerPage', 5);
        $locPage = request('locPage', 1);
        $paginated = $locationsCollection->forPage($locPage, $locPerPage)->values()->all();

        $locations = new LengthAwarePaginator(
            array_map(function ($row) {
                return [
                    'sn' => $row['sn'],
                    'location' => $row['location'],
                    'kemantren' => $row['kemantren'],
                    'kelurahan' => $row['kelurahan'],
                    'rt' => $row['rt'],
                    'rw' => $row['rw'],
                    'clients' => $row['clients_preview'] ?? [],
                    'count' => $row['count'] ?? 0,
                ];
            }, $paginated),
            $locationsCollection->count(),
            $locPerPage,
            $locPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        $kemantrenList = $locationsCollection->pluck('kemantren')->unique()->sort()->values();

        $today = now();
        $monday = $today->copy()->startOfWeek();
        $firstDayOfMonth = $today->copy()->startOfMonth();
        $currentMonth = $today->month;
        $currentYear = $today->year;

        $dayLabels = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

        $weeklyLocationByDay = cache()->remember(sprintf('weekly_location_by_day_%s', $monday->toDateString()), 600, function () use ($monday) {
            $result = [];
            for ($i = 0; $i < 7; $i++) {
                $date = $monday->copy()->addDays($i);
                $rows = DB::table('daily_location_stats')
                    ->where('date', $date->toDateString())
                    ->groupBy('location', 'kemantren')
                    ->selectRaw('location, kemantren, SUM(user_count) as total')
                    ->get()
                    ->map(function ($r) {
                        return [
                            'location' => $r->location,
                            'kemantren' => $r->kemantren,
                            'total' => (int) $r->total,
                        ];
                    })->toArray();

                $result[$date->toDateString()] = $rows;
            }

            $allEmpty = true;
            foreach ($result as $rows) {
                if (!empty($rows)) {
                    $allEmpty = false;
                    break;
                }
            }

            if ($allEmpty) {
                Log::info('weekly_location_by_day: current week empty, falling back to historical data');

                $all = DB::table('daily_location_stats')
                    ->selectRaw('date, location, kemantren, SUM(user_count) as total')
                    ->groupBy('date', 'location', 'kemantren')
                    ->orderBy('date')
                    ->get()
                    ->groupBy('date')
                    ->map(function ($group) {
                        return $group->map(function ($r) {
                            return [
                                'location' => $r->location,
                                'kemantren' => $r->kemantren,
                                'total' => (int) $r->total,
                            ];
                        })->toArray();
                    })->toArray();

                if (!empty($all)) {
                    return $all;
                }
            }

            return $result;
        });

        try {
            $expected = [];
            for ($i = 0; $i < 7; $i++) {
                $expected[] = $monday->copy()->addDays($i)->toDateString();
            }

            $ordered = [];
            foreach ($expected as $d) {
                $ordered[$d] = $weeklyLocationByDay[$d] ?? [];
            }

            $weeklyLocationByDay = $ordered;
        } catch (\Throwable $e) {
            Log::warning('failed to normalize weeklyLocationByDay keys: ' . $e->getMessage());
        }

        try {
            $nonEmptyDays = 0;
            $totalRows = 0;
            $sample = [];
            foreach ($weeklyLocationByDay as $date => $rows) {
                if (!empty($rows)) {
                    $nonEmptyDays++;
                    $totalRows += count($rows);
                    if (empty($sample)) {
                        $sample = array_slice($rows, 0, 3);
                    }
                }
            }

            Log::info('weekly_location_by_day.summary', [
                'monday' => $monday->toDateString(),
                'non_empty_days' => $nonEmptyDays,
                'total_rows' => $totalRows,
                'sample' => $sample,
            ]);
        } catch (\Throwable $e) {
            Log::warning('failed to log weekly_location_by_day summary: ' . $e->getMessage());
        }

        $monthlyLocationData = cache()->remember(sprintf('monthly_location_data_%d_%d', $currentYear, $currentMonth), 600, function () use ($firstDayOfMonth, $today, $currentYear, $currentMonth) {
            return DB::table('daily_location_stats')
                ->whereYear('date', $currentYear)
                ->whereMonth('date', $currentMonth)
                ->whereBetween('date', [$firstDayOfMonth, $today])
                ->groupBy('location', 'kemantren')
                ->selectRaw('location, kemantren, SUM(user_count) as total')
                ->get()
                ->map(fn($r) => [
                    'location' => $r->location,
                    'kemantren' => $r->kemantren,
                    'total' => (int) $r->total,
                ])->toArray();
        });

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return view('dashboard', compact(
            'totalUser', 'totalAp', 'userOnline', 'userCapacity',
            'logActivity', 'dailyUsers', 'locations', 'kemantrenList',
            'monthlyLocationData', 'dayLabels', 'weeklyLocationByDay',
            'months', 'currentMonth', 'currentYear'
        ));
    }

    /* =========================================================
     * EXPORT EXCEL
     * ========================================================= */

    /**
     * Export data ke Excel (.xlsx)
     * Query params:
     *   type     : weekly_user | monthly_user | weekly_location | monthly_location
     *   month    : int  (untuk bulanan, default bulan ini)
     *   year     : int  (untuk bulanan, default tahun ini)
     *   kemantren: string (filter lokasi, opsional)
     */
    public function exportExcel(Request $request)
    {
        $type      = $request->query('type', 'weekly_user');
        $month     = (int) $request->query('month', now()->month);
        $year      = (int) $request->query('year', now()->year);
        $kemantren = $request->query('kemantren', null);

        $allowed = ['weekly_user', 'monthly_user', 'weekly_location', 'monthly_location'];
        if (!in_array($type, $allowed)) {
            return response()->json(['error' => 'Invalid export type'], 422);
        }

        $monthNames = [
            1=>'Januari', 2=>'Februari', 3=>'Maret', 4=>'April',
            5=>'Mei', 6=>'Juni', 7=>'Juli', 8=>'Agustus',
            9=>'September', 10=>'Oktober', 11=>'November', 12=>'Desember'
        ];

        // ── Style definitions ────────────────────────────────────────────────
        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'name' => 'Arial', 'size' => 11],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF3B5998']]],
        ];
        $dataStyle = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFDDDDDD']]],
            'font'    => ['name' => 'Arial', 'size' => 10],
        ];
        $totalStyle = [
            'font' => ['bold' => true, 'name' => 'Arial', 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE8F0FE']],
        ];

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        switch ($type) {

            // ── WEEKLY USER ──────────────────────────────────────────────────
            case 'weekly_user':
                $sheet->setTitle('User Mingguan');
                $monday   = now()->copy()->startOfWeek();
                $dayNames = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];

                $stats = DB::table('daily_user_stats')
                    ->whereBetween('date', [$monday->toDateString(), $monday->copy()->endOfWeek()->toDateString()])
                    ->pluck('user_count', 'date');

                $sheet->fromArray(['Hari', 'Tanggal', 'Jumlah User'], null, 'A1');
                $sheet->getStyle('A1:C1')->applyFromArray($headerStyle);
                $sheet->getRowDimension(1)->setRowHeight(22);

                for ($i = 0; $i < 7; $i++) {
                    $date  = $monday->copy()->addDays($i);
                    $count = (int)($stats[$date->toDateString()] ?? 0);
                    $row   = $i + 2;
                    $sheet->fromArray([$dayNames[$date->dayOfWeek], $date->toDateString(), $count], null, "A{$row}");
                    $sheet->getStyle("A{$row}:C{$row}")->applyFromArray($dataStyle);
                    $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                $totalRow = 9;
                $sheet->setCellValue("A{$totalRow}", 'TOTAL');
                $sheet->setCellValue("B{$totalRow}", '');
                $sheet->setCellValue("C{$totalRow}", '=SUM(C2:C8)');
                $sheet->getStyle("A{$totalRow}:C{$totalRow}")->applyFromArray($totalStyle);
                $sheet->mergeCells("A{$totalRow}:B{$totalRow}");

                $sheet->getColumnDimension('A')->setWidth(12);
                $sheet->getColumnDimension('B')->setWidth(14);
                $sheet->getColumnDimension('C')->setWidth(14);

                $fileName = 'rekap-user-mingguan-' . $monday->toDateString() . '.xlsx';
                break;

            // ── MONTHLY USER ─────────────────────────────────────────────────
            case 'monthly_user':
                $sheet->setTitle('User Bulanan');
                $start = Carbon::createFromDate($year, $month, 1)->toDateString();
                $end   = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();
                if ($end > now()->toDateString()) $end = now()->toDateString();

                $stats = DB::table('daily_user_stats')
                    ->whereBetween('date', [$start, $end])
                    ->orderBy('date')
                    ->get();

                $sheet->fromArray(['Tanggal', 'Jumlah User'], null, 'A1');
                $sheet->getStyle('A1:B1')->applyFromArray($headerStyle);
                $sheet->getRowDimension(1)->setRowHeight(22);

                foreach ($stats as $i => $s) {
                    $row = $i + 2;
                    $sheet->fromArray([$s->date, (int)$s->user_count], null, "A{$row}");
                    $sheet->getStyle("A{$row}:B{$row}")->applyFromArray($dataStyle);
                    $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                $lastDataRow = count($stats) + 1;
                $totalRow    = $lastDataRow + 1;
                $sheet->setCellValue("A{$totalRow}", 'TOTAL');
                $sheet->setCellValue("B{$totalRow}", "=SUM(B2:B{$lastDataRow})");
                $sheet->getStyle("A{$totalRow}:B{$totalRow}")->applyFromArray($totalStyle);

                $sheet->getColumnDimension('A')->setWidth(14);
                $sheet->getColumnDimension('B')->setWidth(14);

                $fileName = 'rekap-user-' . ($monthNames[$month] ?? $month) . '-' . $year . '.xlsx';
                break;

            // ── WEEKLY LOCATION ──────────────────────────────────────────────
            case 'weekly_location':
                $sheet->setTitle('Lokasi Mingguan');
                $monday    = now()->copy()->startOfWeek();
                $weekDates = [];
                for ($i = 0; $i < 7; $i++) {
                    $weekDates[] = $monday->copy()->addDays($i)->toDateString();
                }
                $dayLabels = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];

                $headers = array_merge(['Lokasi', 'Kemantren'], $dayLabels, ['Total']);
                $sheet->fromArray($headers, null, 'A1');
                $lastCol = Coordinate::stringFromColumnIndex(count($headers));
                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray($headerStyle);
                $sheet->getRowDimension(1)->setRowHeight(22);

                $raw = DB::table('daily_location_stats')
                    ->whereBetween('date', [$weekDates[0], $weekDates[6]])
                    ->selectRaw('location, kemantren, date, SUM(user_count) as total')
                    ->groupBy('location', 'kemantren', 'date')
                    ->get();

                $pivot = [];
                foreach ($raw as $r) {
                    $key = $r->location . '|||' . $r->kemantren;
                    if (!isset($pivot[$key])) {
                        $pivot[$key] = ['location' => $r->location, 'kemantren' => $r->kemantren];
                        foreach ($weekDates as $d) $pivot[$key][$d] = 0;
                    }
                    $pivot[$key][$r->date] = (int)$r->total;
                }

                usort($pivot, function ($a, $b) use ($weekDates) {
                    $sumA = array_sum(array_map(fn($d) => $a[$d] ?? 0, $weekDates));
                    $sumB = array_sum(array_map(fn($d) => $b[$d] ?? 0, $weekDates));
                    return $sumB <=> $sumA;
                });

                foreach ($pivot as $i => $row) {
                    $r    = $i + 2;
                    $vals = [$row['location'], $row['kemantren']];
                    foreach ($weekDates as $d) $vals[] = $row[$d] ?? 0;
                    $vals[] = array_sum(array_slice($vals, 2));
                    $sheet->fromArray($vals, null, "A{$r}");
                    $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray($dataStyle);
                    $sheet->getStyle("{$lastCol}{$r}")->getFont()->setBold(true);
                }

                $sheet->getColumnDimension('A')->setWidth(35);
                $sheet->getColumnDimension('B')->setWidth(18);
                for ($c = 3; $c <= count($headers); $c++) {
                    $col = Coordinate::stringFromColumnIndex($c);
                    $sheet->getColumnDimension($col)->setWidth(10);
                }

                $fileName = 'rekap-lokasi-mingguan-' . now()->startOfWeek()->toDateString() . '.xlsx';
                break;

            // ── MONTHLY LOCATION ─────────────────────────────────────────────
            case 'monthly_location':
            default:
                $sheet->setTitle('Lokasi Bulanan');
                $start = Carbon::createFromDate($year, $month, 1)->toDateString();
                $end   = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();
                if ($end > now()->toDateString()) $end = now()->toDateString();

                $query = DB::table('daily_location_stats')
                    ->whereYear('date', $year)
                    ->whereMonth('date', $month)
                    ->whereBetween('date', [$start, $end])
                    ->groupBy('location', 'kemantren')
                    ->selectRaw('location, kemantren, SUM(user_count) as total')
                    ->orderByDesc('total');

                if ($kemantren && $kemantren !== 'all') {
                    $query->where('kemantren', $kemantren);
                }

                $data = $query->get();

                $sheet->fromArray(['No', 'Lokasi', 'Kemantren', 'Total User'], null, 'A1');
                $sheet->getStyle('A1:D1')->applyFromArray($headerStyle);
                $sheet->getRowDimension(1)->setRowHeight(22);

                foreach ($data as $i => $r) {
                    $row = $i + 2;
                    $sheet->fromArray([$i + 1, $r->location, $r->kemantren, (int)$r->total], null, "A{$row}");
                    $sheet->getStyle("A{$row}:D{$row}")->applyFromArray($dataStyle);
                    $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("D{$row}")->getFont()->setBold(true);
                }

                $lastDataRow = count($data) + 1;
                $totalRow    = $lastDataRow + 1;
                $sheet->setCellValue("A{$totalRow}", '');
                $sheet->setCellValue("B{$totalRow}", 'TOTAL');
                $sheet->setCellValue("C{$totalRow}", '');
                $sheet->setCellValue("D{$totalRow}", "=SUM(D2:D{$lastDataRow})");
                $sheet->getStyle("A{$totalRow}:D{$totalRow}")->applyFromArray($totalStyle);

                $sheet->getColumnDimension('A')->setWidth(6);
                $sheet->getColumnDimension('B')->setWidth(35);
                $sheet->getColumnDimension('C')->setWidth(18);
                $sheet->getColumnDimension('D')->setWidth(14);

                $fileName = 'rekap-lokasi-' . ($monthNames[$month] ?? $month) . '-' . $year . '.xlsx';
                break;
        }

        // ── Info sheet (metadata) ────────────────────────────────────────────
        $infoSheet = $spreadsheet->createSheet();
        $infoSheet->setTitle('Info');
        $infoSheet->setCellValue('A1', 'Diekspor pada');
        $infoSheet->setCellValue('B1', now('Asia/Jakarta')->format('d/m/Y H:i:s') . ' WIB');
        $infoSheet->setCellValue('A2', 'Tipe');
        $infoSheet->setCellValue('B2', $type);
        $infoSheet->setCellValue('A3', 'Periode');
        $infoSheet->setCellValue('B3', str_contains($type, 'monthly')
            ? ($monthNames[$month] ?? $month) . ' ' . $year
            : 'Minggu ' . now()->startOfWeek()->toDateString());
        $infoSheet->getColumnDimension('A')->setWidth(18);
        $infoSheet->getColumnDimension('B')->setWidth(30);

        $spreadsheet->setActiveSheetIndex(0);

        // ── Stream ke browser ────────────────────────────────────────────────
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    /* =========================================================
     * EXISTING METHODS (tidak ada perubahan)
     * ========================================================= */

    public function monthlyLocationData(Request $request)
    {
        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);
        $top = (int) $request->query('top', 10);
        $kemantren = $request->query('kemantren', null);
        $search = $request->query('search', null);

        $start = Carbon::createFromDate($year, $month, 1)->toDateString();
        $end = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();

        $today = now()->toDateString();
        if ($end > $today) {
            $end = $today;
        }

        $cacheKey = sprintf('monthly_location_data_%d_%d_top%d_k%s_s%s', $year, $month, $top, $kemantren ?? 'all', $search ?? 'all');

        $data = cache()->remember($cacheKey, 300, function () use ($year, $month, $start, $end, $top, $kemantren, $search) {
            $query = DB::table('daily_location_stats')
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->whereBetween('date', [$start, $end])
                ->groupBy('location', 'kemantren')
                ->selectRaw('location, kemantren, SUM(user_count) as total');

            if ($kemantren) {
                $query->where('kemantren', $kemantren);
            }

            if ($search) {
                $query->havingRaw("LOWER(location) LIKE ?", ['%' . strtolower($search) . '%']);
            }

            $rows = $query->orderByDesc('total')->limit(max(1, $top))->get()
                ->map(fn($r) => [
                    'location' => $r->location,
                    'kemantren' => $r->kemantren,
                    'total' => (int) $r->total,
                ])->toArray();

            return $rows;
        });

        return response()->json($data);
    }

    public function locationClients(Request $request)
    {
        $sn = strtoupper(trim($request->query('sn', '')));
        if ($sn === '') {
            return response()->json(['error' => 'sn required'], 400);
        }

        $onuService = app(\App\Services\OnuApiService::class);
        $connections = $onuService->getAllOnuWithClients();

        $ontMap = cache()->remember('ont_map_paket_all', 600, fn() => $this->readOntMap());

        $found = null;
        foreach ($connections as $c) {
            if (strtoupper(trim($c['sn'] ?? '')) === $sn) {
                $found = $c;
                break;
            }
        }

        if (!$found) {
            return response()->json([]);
        }

        $clients = array_merge(
            $found['wifiClients']['5G'] ?? [],
            $found['wifiClients']['2_4G'] ?? [],
            $found['wifiClients']['unknown'] ?? []
        );

        $info = $ontMap[$sn] ?? [];

        $result = array_map(function ($cl) use ($found, $info) {
            return [
                'wifi_terminal_name' => $cl['wifi_terminal_name'] ?? 'Unknown',
                'wifi_terminal_ip' => $cl['wifi_terminal_ip'] ?? '-',
                'wifi_terminal_mac' => $cl['wifi_terminal_mac'] ?? '-',
                'ap_sn' => $found['sn'] ?? null,
                'ap_name' => $info['location'] ?? '-',
                'ap_kemantren' => $info['kemantren'] ?? '-',
            ];
        }, $clients);

        return response()->json(array_values($result));
    }

    public function updateRealtimeLocationStats()
    {
        try {
            $onuService = app(\App\Services\OnuApiService::class);
            $connections = $onuService->getAllOnuWithClients();

            $ontMap = cache()->remember('ont_map_paket_all', 86400, fn() => $this->readOntMap());

            $stats = $this->buildRealtimeStats($connections, $ontMap);

            $today = now()->toDateString();

            DB::beginTransaction();
            try {
                foreach ($stats as $stat) {
                    DB::table('daily_location_stats')->updateOrInsert(
                        ['date' => $today, 'location' => $stat['location'], 'kemantren' => $stat['kemantren'], 'sn' => $stat['sn'] ?? null],
                        ['user_count' => $stat['user_count'], 'updated_at' => now()]
                    );
                }
                DB::commit();

                Log::info('Realtime location stats updated', [
                    'date' => $today,
                    'locations_count' => count($stats),
                    'total_users' => array_sum(array_column($stats, 'user_count'))
                ]);
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }

            return response()->json([
                'success' => true,
                'data' => $stats,
                'timestamp' => now()->toIso8601String(),
                'total_locations' => count($stats),
                'total_users' => array_sum(array_column($stats, 'user_count'))
            ]);
        } catch (\Throwable $e) {
            Log::error('updateRealtimeLocationStats error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getWeeklyLocationData(Request $request)
    {
        try {
            $today  = now();
            $monday = $today->copy()->startOfWeek();

            $kemantren = $request->query('kemantren', null);
            $search    = $request->query('search', null);
            $top       = (int) $request->query('top', 8);

            $weekDates = [];
            for ($i = 0; $i < 7; $i++) {
                $weekDates[] = $monday->copy()->addDays($i)->toDateString();
            }

            $query = DB::table('daily_location_stats')
                ->whereBetween('date', [$weekDates[0], $weekDates[6]])
                ->selectRaw('location, kemantren, SUM(user_count) as total_week')
                ->groupBy('location', 'kemantren');

            if ($kemantren && $kemantren !== 'all') {
                $query->where('kemantren', $kemantren);
            }

            if ($search) {
                $query->havingRaw("LOWER(location) LIKE ?", ['%' . strtolower($search) . '%']);
            }

            $topLocations = $query->orderByDesc('total_week')->limit($top)->get()->pluck('location')->toArray();

            if (empty($topLocations)) {
                return response()->json([
                    'labels' => ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'],
                    'dates' => $weekDates,
                    'data' => [],
                    'isEmpty' => true
                ]);
            }

            $result = [];
            foreach ($weekDates as $date) {
                $q = DB::table('daily_location_stats')
                    ->where('date', $date)
                    ->whereIn('location', $topLocations)
                    ->selectRaw('location, kemantren, SUM(user_count) as total')
                    ->groupBy('location', 'kemantren');

                if ($kemantren && $kemantren !== 'all') {
                    $q->where('kemantren', $kemantren);
                }

                $result[$date] = $q->get()->map(fn($r) => [
                    'location' => $r->location,
                    'kemantren' => $r->kemantren,
                    'total' => (int) $r->total,
                ])->toArray();
            }

            return response()->json([
                'labels' => ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'],
                'dates' => $weekDates,
                'data' => $result,
                'topLocations' => $topLocations,
                'isEmpty' => false
            ]);
        } catch (\Throwable $e) {
            Log::error('getWeeklyLocationData error: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to fetch data'], 500);
        }
    }

    private function buildRealtimeStats(array $connections, array $ontMap): array
    {
        $result = [];

        foreach ($connections as $c) {
            $sn = strtoupper(trim($c['sn'] ?? ''));
            if (!$sn || !isset($ontMap[$sn])) continue;

            $info = $ontMap[$sn];
            if (empty($info['location'])) continue;

            if (!isset($result[$sn])) {
                $result[$sn] = [
                    'sn' => $sn,
                    'location' => $info['location'],
                    'kemantren' => $info['kemantren'],
                    'user_count' => 0,
                    'macs' => []
                ];
            }

            $clients = array_merge(
                $c['wifiClients']['5G'] ?? [],
                $c['wifiClients']['2_4G'] ?? [],
                $c['wifiClients']['unknown'] ?? []
            );

            foreach ($clients as $cl) {
                $mac = strtoupper(trim($cl['wifi_terminal_mac'] ?? ''));
                $mac = preg_replace('/[^A-F0-9:]/', '', $mac);
                if (!$mac) continue;

                if (!isset($result[$sn]['macs'][$mac])) {
                    $result[$sn]['macs'][$mac] = true;
                    $result[$sn]['user_count']++;
                }
            }
        }

        return array_map(function ($item) {
            unset($item['macs']);
            return $item;
        }, array_values($result));
    }

    private function readOntMap(): array
    {
        $path = public_path('storage/ACSfiks.csv');
        if (!is_file($path)) {
            Log::error('readOntMap: ACSfiks.csv not found at ' . $path);
            return [];
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            Log::error('readOntMap: unable to open ' . $path);
            return [];
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            return [];
        }

        $indexes = [];
        foreach ($header as $idx => $name) {
            $key = strtolower(trim(preg_replace('/\s+/', '_', $name)));
            $indexes[$key] = $idx;
        }

        $map = [];
        $get = function (array $row, string $key) use ($indexes): string {
            $idx = $indexes[$key] ?? null;
            return $idx === null ? '' : trim($row[$idx] ?? '');
        };

        while (($row = fgetcsv($handle)) !== false) {
            $sn = strtoupper($get($row, 'sn'));
            if ($sn === '') continue;

            $map[$sn] = [
                'location'   => $get($row, 'nama_lokasi'),
                'kemantren'  => $get($row, 'kemantren'),
                'kelurahan'  => $get($row, 'kelurahan'),
                'rt'         => $get($row, 'rt'),
                'rw'         => $get($row, 'rw'),
                'ip'         => $get($row, 'ip'),
                'pic'        => $get($row, 'pic'),
                'coordinate' => $get($row, 'titik_koordinat'),
            ];
        }

        fclose($handle);
        return $map;
    }

    public function monthlyUserData(Request $request)
    {
        $month = (int) $request->query('month', now()->month);
        $year  = (int) $request->query('year', now()->year);

        $start = Carbon::createFromDate($year, $month, 1)->toDateString();
        $end   = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();

        $today = now()->toDateString();
        if ($end > $today) $end = $today;

        $data = cache()->remember(sprintf('monthly_user_data_%d_%d', $year, $month), 300, function () use ($start, $end) {
            $stats = DB::table('daily_user_stats')
                ->whereBetween('date', [$start, $end])
                ->orderBy('date')
                ->get();

            $labels = [];
            $values = [];
            foreach ($stats as $stat) {
                $labels[] = $stat->date;
                $values[] = (int) $stat->user_count;
            }

            return ['labels' => $labels, 'data' => $values];
        });

        return response()->json($data);
    }

    public function getUserOnline()
    {
        try {
            $onuService  = app(\App\Services\OnuApiService::class);
            $connections = $onuService->getAllOnuWithClients();

            $uniqueMacSet = [];
            if (is_array($connections)) {
                foreach ($connections as $c) {
                    if (!is_array($c) || !isset($c['wifiClients'])) continue;
                    $wifi       = $c['wifiClients'];
                    $allClients = array_merge($wifi['5G'] ?? [], $wifi['2_4G'] ?? [], $wifi['unknown'] ?? []);

                    foreach ($allClients as $client) {
                        if (!isset($client['wifi_terminal_mac'])) continue;
                        $mac = strtoupper(trim($client['wifi_terminal_mac']));
                        $mac = preg_replace('/[^A-F0-9:]/i', '', $mac);
                        if ($mac === '') continue;
                        $uniqueMacSet[$mac] = true;
                    }
                }
            }
            $userOnline = count($uniqueMacSet);

            $date = now()->toDateString();
            DB::table('daily_user_stats')->updateOrInsert(
                ['date' => $date],
                ['user_count' => $userOnline, 'updated_at' => now(), 'created_at' => now()]
            );

            return response()->json(['userOnline' => $userOnline]);
        } catch (\Throwable $e) {
            Log::error('DashboardController@getUserOnline : ' . $e->getMessage());

            try {
                $date      = now()->toDateString();
                $lastStats = DB::table('daily_user_stats')->where('date', $date)->first();
                $userOnline = $lastStats ? $lastStats->user_count : 0;

                return response()->json(['userOnline' => $userOnline, 'cached' => true, 'error' => 'Using cached data due to API timeout']);
            } catch (\Throwable $dbError) {
                Log::error('Database fallback error: ' . $dbError->getMessage());
                return response()->json(['error' => 'Unable to fetch user data'], 500);
            }
        }
    }

    public function getWeeklyUserData()
    {
        try {
            $today     = now();
            $monday    = $today->copy()->startOfWeek();
            $weekDates = [];
            for ($i = 0; $i < 7; $i++) {
                $weekDates[] = $monday->copy()->addDays($i)->toDateString();
            }

            $stats = DB::table('daily_user_stats')
                ->whereBetween('date', [$monday->toDateString(), $monday->copy()->endOfWeek()->toDateString()])
                ->pluck('user_count', 'date');

            $weeklyData = array_fill(0, 7, 0);
            foreach ($weekDates as $i => $dateStr) {
                $weeklyData[$i] = (int)($stats[$dateStr] ?? 0);
            }

            $dayNames    = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
            $dailyLabels = [];
            foreach ($weekDates as $d) {
                try {
                    $dailyLabels[] = $dayNames[Carbon::parse($d)->dayOfWeek] ?? $d;
                } catch (\Throwable $e) {
                    $dailyLabels[] = $d;
                }
            }

            return response()->json(['labels' => $dailyLabels, 'data' => $weeklyData]);
        } catch (\Throwable $e) {
            Log::error('DashboardController@getWeeklyUserData : ' . $e->getMessage());
            return response()->json(['error' => 'Unable to fetch weekly data'], 500);
        }
    }

    public function getDailyUserDataByHour(Request $request)
    {
        try {
            $dateParam  = $request->query('date');
            $nowJakarta = Carbon::now('Asia/Jakarta');

            if (!$dateParam) {
                $dateObj = $nowJakarta->copy()->startOfDay();
            } else {
                try {
                    $dateObj = Carbon::createFromFormat('Y-m-d', $dateParam, 'Asia/Jakarta')->startOfDay();
                } catch (\Throwable $e) {
                    try {
                        $dateObj = Carbon::parse($dateParam, 'Asia/Jakarta')->startOfDay();
                    } catch (\Throwable $ex) {
                        Log::warning('Invalid date format provided: ' . $dateParam);
                        $dateObj = $nowJakarta->copy()->startOfDay();
                    }
                }
            }

            if ($dateObj->isAfter($nowJakarta)) {
                $dateObj = $nowJakarta->copy()->startOfDay();
            }

            $date = $dateObj->toDateString();

            $hourLabels = [];
            for ($i = 0; $i < 24; $i++) {
                $hourLabels[] = str_pad($i, 2, '0', STR_PAD_LEFT) . ':00';
            }

            $hourlyData  = array_fill(0, 24, 0);
            $now         = Carbon::now('Asia/Jakarta');
            $isToday     = $date === $now->toDateString();
            $currentHour = $isToday ? (int)$now->format('H') : 23;

            try {
                $stats = DB::table('daily_user_stats_hourly')
                    ->where('date', $date)
                    ->orderBy('hour')
                    ->get();

                if ($stats->count() > 0) {
                    foreach ($stats as $stat) {
                        $hourIndex = (int)$stat->hour;
                        $hourlyData[$hourIndex] = ($isToday && $hourIndex > $currentHour) ? 0 : (int)$stat->user_count;
                    }

                    if ($isToday) {
                        for ($i = $currentHour + 1; $i < 24; $i++) {
                            $hourlyData[$i] = 0;
                        }
                    }
                } else {
                    Log::info('No hourly stats found for date: ' . $date);
                }
            } catch (\Throwable $e) {
                Log::warning('Error fetching daily_user_stats_hourly: ' . $e->getMessage());
            }

            return response()->json([
                'labels' => $hourLabels,
                'data' => $hourlyData,
                'date' => $date,
                'isToday' => $isToday,
                'currentHour' => $currentHour,
                'timezone' => 'Asia/Jakarta',
            ]);
        } catch (\Throwable $e) {
            Log::error('DashboardController@getDailyUserDataByHour : ' . $e->getMessage());
            return response()->json(['error' => 'Unable to fetch daily hour data'], 500);
        }
    }
}