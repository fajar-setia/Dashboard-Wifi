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

class MonthlyUserSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected array $params;

    public function __construct(array $params) { $this->params = $params; }

    public function title(): string { return 'Rekap User Bulanan'; }

    public function headings(): array
    {
        return ['Tanggal', 'Jumlah User'];
    }

    public function collection()
    {
        $month = (int)($this->params['month'] ?? now()->month);
        $year  = (int)($this->params['year']  ?? now()->year);

        $start = Carbon::createFromDate($year, $month, 1)->toDateString();
        $end   = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();
        if ($end > now()->toDateString()) $end = now()->toDateString();

        return DB::table('daily_user_stats')
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->get()
            ->map(fn($r) => [
                'tanggal' => $r->date,
                'count'   => (int)$r->user_count,
            ]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'name' => 'Arial', 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A5F']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function columnWidths(): array { return ['A' => 15, 'B' => 15]; }
}