<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DashboardExport implements WithMultipleSheets
{
    protected string $type;
    protected array  $params;

    public function __construct(string $type, array $params = [])
    {
        $this->type   = $type;
        $this->params = $params;
    }

    public function sheets(): array
    {
        return match ($this->type) {
            'weekly_user'    => [new Sheets\WeeklyUserSheet($this->params)],
            'monthly_user'   => [new Sheets\MonthlyUserSheet($this->params)],
            'weekly_location'=> [new Sheets\WeeklyLocationSheet($this->params)],
            'monthly_location'=> [new Sheets\MonthlyLocationSheet($this->params)],
            default          => [],
        };
    }
}