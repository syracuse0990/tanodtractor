<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\FromArray;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TractorUsageSummarySheet implements FromArray, WithTitle, WithStyles, WithColumnWidths, WithCustomStartCell
{
    protected array $tractors;
    protected array $summary;

    public function __construct(array $tractors, array $summary)
    {
        $this->tractors = $tractors;
        $this->summary = $summary;
    }

    public function startCell(): string
    {
        return 'A1';
    }

    public function array(): array
    {
        return [[]];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 3,
            'B' => 24,
            'C' => 20,
            'D' => 20,
            'E' => 20,
            'F' => 20,
            'G' => 20,
            'H' => 3,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $tractors = collect($this->tractors);
        $total = $tractors->count();
        $online = $tractors->where('status', 'online')->count();
        $offline = $tractors->where('status', 'offline')->count();
        $inactive = $tractors->where('status', 'inactive')->count();
        $withData = $tractors->filter(fn($t) => ($t['total_distance'] ?? 0) > 0 || ($t['running_hours'] ?? 0) > 0)->count();
        $totalDist = round($this->summary['total_distance'] ?? 0, 2);
        $totalHrs = round($this->summary['total_hours'] ?? 0, 2);
        $avgDist = $total > 0 ? round($totalDist / $total, 2) : 0;
        $avgHrs = $total > 0 ? round($totalHrs / $total, 2) : 0;
        $dataPct = $total > 0 ? round($withData / $total * 100, 1) : 0;
        $pmsDue = $this->summary['pms_due'] ?? 0;
        $totalMaintenances = $this->summary['total_maintenances'] ?? 0;

        // Group breakdown
        $groups = $tractors->groupBy(fn($t) => $t['group_name'] ?? 'Unassigned')
            ->map(fn($items) => [
                'count' => $items->count(),
                'distance' => round($items->sum('total_distance'), 2),
                'hours' => round($items->sum('running_hours'), 2),
                'pms_due' => $items->where('pms_status', 'Due')->count(),
            ])
            ->sortByDesc('distance');

        // Top 10 by distance
        $topByDistance = $tractors->sortByDesc('total_distance')->take(10)->values();

        // Top 10 by hours
        $topByHours = $tractors->sortByDesc('running_hours')->take(10)->values();

        // Whole sheet background
        $sheet->getStyle('A1:H80')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
        ]);

        // HEADER BANNER
        $sheet->mergeCells('B2:G2');
        $sheet->setCellValue('B2', 'TRACTOR USAGE REPORT');
        $sheet->getStyle('B2:G2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 20, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(50);

        $sheet->mergeCells('B3:G3');
        $sheet->setCellValue('B3', 'Fleet Performance Dashboard  •  Generated ' . now()->format('F j, Y'));
        $sheet->getStyle('B3:G3')->applyFromArray([
            'font' => ['size' => 10, 'color' => ['rgb' => 'C7D2FE']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4338CA']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(28);

        // KPI CARDS
        $sheet->getRowDimension(5)->setRowHeight(22);
        $sheet->mergeCells('B5:G5');
        $sheet->setCellValue('B5', 'KEY METRICS');
        $this->sectionHeader($sheet, 'B5:G5');

        $sheet->getRowDimension(6)->setRowHeight(18);
        $sheet->getRowDimension(7)->setRowHeight(32);
        $sheet->getRowDimension(8)->setRowHeight(18);

        $cards = [
            'B' => ['Total Tractors', number_format($total), "{$online} online  •  {$offline} offline  •  {$inactive} inactive", '3B82F6'],
            'C' => ['Total Distance', number_format($totalDist, 2) . ' km', "Avg {$avgDist} km / tractor", '10B981'],
            'D' => ['Running Hours', number_format($totalHrs, 2) . ' hrs', "Avg {$avgHrs} hrs / tractor", 'F59E0B'],
            'E' => ['With Usage Data', "{$withData} / {$total}", "{$dataPct}% of fleet reporting", '8B5CF6'],
            'F' => ['PMS Due', (string) $pmsDue, "{$totalMaintenances} total records", 'F97316'],
            'G' => ['Online Rate', ($total > 0 ? round($online / $total * 100, 1) : 0) . '%', "{$online} of {$total} tractors online", '06B6D4'],
        ];

        foreach ($cards as $col => $card) {
            $sheet->setCellValue("{$col}6", $card[0]);
            $sheet->setCellValue("{$col}7", $card[1]);
            $sheet->setCellValue("{$col}8", $card[2]);

            $sheet->getStyle("{$col}6")->applyFromArray([
                'font' => ['size' => 8, 'bold' => true, 'color' => ['rgb' => '6B7280']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $sheet->getStyle("{$col}7")->applyFromArray([
                'font' => ['size' => 16, 'bold' => true, 'color' => ['rgb' => $card[3]]],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle("{$col}8")->applyFromArray([
                'font' => ['size' => 7, 'italic' => true, 'color' => ['rgb' => '9CA3AF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            $sheet->getStyle("{$col}6:{$col}8")->applyFromArray([
                'borders' => [
                    'outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']],
                ],
            ]);
            $sheet->getStyle("{$col}6")->applyFromArray([
                'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => $card[3]]]],
            ]);
        }

        // GROUP BREAKDOWN
        $row = 10;
        $sheet->mergeCells("B{$row}:G{$row}");
        $sheet->setCellValue("B{$row}", 'BREAKDOWN BY GROUP');
        $this->sectionHeader($sheet, "B{$row}:G{$row}");
        $row++;

        $sheet->getRowDimension($row)->setRowHeight(22);
        $grpHeaders = ['B' => 'Group', 'C' => 'Tractors', 'D' => 'Distance (km)', 'E' => 'Hours', 'F' => 'PMS Due', 'G' => '% of Fleet'];
        foreach ($grpHeaders as $col => $h) {
            $sheet->setCellValue("{$col}{$row}", $h);
        }
        $sheet->getStyle("B{$row}:G{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6366F1']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '818CF8']]],
        ]);
        $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $row++;

        foreach ($groups as $name => $data) {
            $pct = $total > 0 ? round($data['count'] / $total * 100, 1) : 0;
            $sheet->setCellValue("B{$row}", $name);
            $sheet->setCellValue("C{$row}", $data['count']);
            $sheet->setCellValue("D{$row}", $data['distance']);
            $sheet->setCellValue("E{$row}", $data['hours']);
            $sheet->setCellValue("F{$row}", $data['pms_due']);
            $sheet->setCellValue("G{$row}", "{$pct}%");

            $bgColor = ($row % 2 === 0) ? 'FFFFFF' : 'F9FAFB';
            $sheet->getStyle("B{$row}:G{$row}")->applyFromArray([
                'font' => ['size' => 9],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
            ]);
            $sheet->getStyle("C{$row}:G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            if ($data['pms_due'] > 0) {
                $sheet->getStyle("F{$row}")->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('DC2626'));
            }
            $row++;
        }

        // TOP 10 BY DISTANCE & HOURS
        $row += 1;

        $sheet->mergeCells("B{$row}:C{$row}");
        $sheet->setCellValue("B{$row}", 'TOP 10 BY DISTANCE');
        $this->sectionHeader($sheet, "B{$row}:C{$row}");

        $sheet->mergeCells("E{$row}:G{$row}");
        $sheet->setCellValue("E{$row}", 'TOP 10 BY RUNNING HOURS');
        $this->sectionHeader($sheet, "E{$row}:G{$row}");
        $row++;

        $sheet->setCellValue("B{$row}", 'Tractor');
        $sheet->setCellValue("C{$row}", 'Distance (km)');
        $sheet->setCellValue("E{$row}", 'Tractor');
        $sheet->setCellValue("F{$row}", 'Hours');
        $sheet->setCellValue("G{$row}", 'PMS');
        $subHeaderStyle = [
            'font' => ['bold' => true, 'size' => 8, 'color' => ['rgb' => '4B5563']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEF2FF']],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'C7D2FE']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];
        $sheet->getStyle("B{$row}:C{$row}")->applyFromArray($subHeaderStyle);
        $sheet->getStyle("E{$row}:G{$row}")->applyFromArray($subHeaderStyle);
        $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;

        for ($i = 0; $i < 10; $i++) {
            $bgColor = ($i % 2 === 0) ? 'FFFFFF' : 'F9FAFB';
            $rowStyle = [
                'font' => ['size' => 9],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'F3F4F6']]],
            ];

            if (isset($topByDistance[$i])) {
                $t = $topByDistance[$i];
                $sheet->setCellValue("B{$row}", ($i + 1) . '. ' . ($t['no_plate'] ?? ''));
                $sheet->setCellValue("C{$row}", round($t['total_distance'] ?? 0, 2));
                $sheet->getStyle("B{$row}:C{$row}")->applyFromArray($rowStyle);
                $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
                if ($i < 3) {
                    $medal = ['F59E0B', 'A0AEC0', 'CD7F32'][$i];
                    $sheet->getStyle("B{$row}")->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color($medal));
                }
            }

            if (isset($topByHours[$i])) {
                $t = $topByHours[$i];
                $sheet->setCellValue("E{$row}", ($i + 1) . '. ' . ($t['no_plate'] ?? ''));
                $sheet->setCellValue("F{$row}", round($t['running_hours'] ?? 0, 2));
                $sheet->getStyle("E{$row}:G{$row}")->applyFromArray($rowStyle);
                $sheet->getStyle("F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->setCellValue("G{$row}", $t['pms_status'] ?? '');
                $sheet->getStyle("G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                if (($t['pms_status'] ?? '') === 'Due') {
                    $sheet->getStyle("G{$row}")->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('DC2626'));
                } else {
                    $sheet->getStyle("G{$row}")->applyFromArray(['font' => ['size' => 8, 'color' => ['rgb' => '16A34A']]]);
                }
                if ($i < 3) {
                    $medal = ['F59E0B', 'A0AEC0', 'CD7F32'][$i];
                    $sheet->getStyle("E{$row}")->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color($medal));
                }
            }

            $row++;
        }

        // STATUS DISTRIBUTION
        $row += 1;
        $sheet->mergeCells("B{$row}:G{$row}");
        $sheet->setCellValue("B{$row}", 'STATUS DISTRIBUTION');
        $this->sectionHeader($sheet, "B{$row}:G{$row}");
        $row++;

        $statuses = [
            ['Online', $online, '22C55E', 'DCFCE7'],
            ['Offline', $offline, 'EF4444', 'FEE2E2'],
            ['Inactive', $inactive, '6B7280', 'F3F4F6'],
        ];

        foreach ($statuses as $s) {
            $pct = $total > 0 ? round($s[1] / $total * 100, 1) : 0;
            $sheet->setCellValue("B{$row}", $s[0]);
            $sheet->setCellValue("C{$row}", $s[1]);
            $sheet->setCellValue("D{$row}", "{$pct}%");

            $sheet->getStyle("B{$row}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => $s[2]]],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $s[3]]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle("C{$row}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => $s[2]]],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $s[3]]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle("D{$row}")->applyFromArray([
                'font' => ['size' => 9, 'color' => ['rgb' => '6B7280']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $s[3]]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle("B{$row}:D{$row}")->applyFromArray([
                'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
            ]);
            $sheet->getRowDimension($row)->setRowHeight(26);
            $row++;
        }

        // FOOTER
        $row += 1;
        $sheet->mergeCells("B{$row}:G{$row}");
        $sheet->setCellValue("B{$row}", 'Report generated on ' . now()->format('F j, Y \a\t g:i A') . '  •  TANOD Tractor Fleet Management System');
        $sheet->getStyle("B{$row}")->applyFromArray([
            'font' => ['size' => 8, 'italic' => true, 'color' => ['rgb' => '9CA3AF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->setShowGridlines(false);

        return [];
    }

    private function sectionHeader(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '1E1B4B']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E7FF']],
            'borders' => [
                'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '6366F1']],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
    }

    public function title(): string
    {
        return 'Summary';
    }
}
