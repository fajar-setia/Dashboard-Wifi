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

class MonthlyLocationSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected array $params;

    public function __construct(array $params) { $this->params = $params; }

    public function title(): string { return 'Rekap Lokasi Bulanan'; }

    public function headings(): array
    {
        return ['Lokasi', 'Kemantren', 'Total User'];
    }

    public function collection()
    {
        $month = (int)($this->params['month'] ?? now()->month);
        $year  = (int)($this->params['year']  ?? now()->year);
        $kemantren = $this->params['kemantren'] ?? null;

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

        return $query->get()->map(fn($r) => [
            'location'  => $r->location,
            'kemantren' => $r->kemantren,
            'total'     => (int)$r->total,
        ]);
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
            "C2:C{$lastRow}" => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array { return ['A' => 35, 'B' => 20, 'C' => 15]; }
}