<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TractorUsageExport implements WithMultipleSheets
{
    protected array $tractors;
    protected array $summary;

    public function __construct(array $tractors, array $summary)
    {
        $this->tractors = $tractors;
        $this->summary = $summary;
    }

    public function sheets(): array
    {
        return [
            new TractorUsageSummarySheet($this->tractors, $this->summary),
            new TractorUsageDataSheet($this->tractors, $this->summary),
        ];
    }
}
