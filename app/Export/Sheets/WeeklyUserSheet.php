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

class WeeklyUserSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    protected array $params;

    public function __construct(array $params) { $this->params = $params; }

    public function title(): string { return 'Rekap User Mingguan'; }

    public function headings(): array
    {
        return ['Hari', 'Tanggal', 'Jumlah User'];
    }

    public function collection()
    {
        $today  = now();
        $monday = $today->copy()->startOfWeek();
        $endOfWeek = $monday->copy()->endOfWeek();
        $dayNames = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];

        $stats = DB::table('daily_user_stats')
            ->whereBetween('date', [$monday->toDateString(), $endOfWeek->toDateString()])
            ->pluck('user_count', 'date');

        $rows = collect();
        for ($i = 0; $i < 7; $i++) {
            $date = $monday->copy()->addDays($i);
            $rows->push([
                'hari'    => $dayNames[$date->dayOfWeek],
                'tanggal' => $date->toDateString(),
                'count'   => (int)($stats[$date->toDateString()] ?? 0),
            ]);
        }

        return $rows;
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

    public function columnWidths(): array
    {
        return ['A' => 15, 'B' => 15, 'C' => 15];
    }
}