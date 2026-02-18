<?php

namespace App\Exports\Sheets;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class WeeklyLocationSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected array $params;

    public function __construct(array $params) { $this->params = $params; }

    public function title(): string { return 'Rekap Lokasi Mingguan'; }

    public function headings(): array
    {
        return ['Lokasi', 'Kemantren', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu', 'Total'];
    }

    public function collection()
    {
        $today  = now();
        $monday = $today->copy()->startOfWeek();

        $weekDates = [];
        for ($i = 0; $i < 7; $i++) {
            $weekDates[] = $monday->copy()->addDays($i)->toDateString();
        }

        // Ambil semua data minggu ini
        $raw = DB::table('daily_location_stats')
            ->whereBetween('date', [$weekDates[0], $weekDates[6]])
            ->selectRaw('location, kemantren, date, SUM(user_count) as total')
            ->groupBy('location', 'kemantren', 'date')
            ->get();

        // Pivot: location|kemantren => [date => total]
        $pivot = [];
        foreach ($raw as $r) {
            $key = $r->location . '|||' . $r->kemantren;
            if (!isset($pivot[$key])) {
                $pivot[$key] = ['location' => $r->location, 'kemantren' => $r->kemantren];
                foreach ($weekDates as $d) $pivot[$key][$d] = 0;
            }
            $pivot[$key][$r->date] = (int)$r->total;
        }

        return collect(array_values($pivot))->map(function ($row) use ($weekDates) {
            $dailyValues = array_map(fn($d) => $row[$d] ?? 0, $weekDates);
            return array_merge(
                [$row['location'], $row['kemantren']],
                $dailyValues,
                [array_sum($dailyValues)]
            );
        })->sortByDesc(fn($r) => $r[count($r) - 1])->values();
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $sheet->getHighestRow();
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'name' => 'Arial', 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A5F']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            "J2:J{$lastRow}" => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 35, 'B' => 20, 'C' => 10, 'D' => 10, 'E' => 10, 'F' => 10, 'G' => 10, 'H' => 10, 'I' => 10, 'J' => 10];
    }
}