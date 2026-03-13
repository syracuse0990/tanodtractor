<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TractorUsageDataSheet implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected array $tractors;
    protected array $summary;

    public function __construct(array $tractors, array $summary)
    {
        $this->tractors = $tractors;
        $this->summary = $summary;
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->tractors as $i => $t) {
            $rows[] = [
                $i + 1,
                $t['no_plate'] ?? '',
                trim(($t['brand'] ?? '') . ' ' . ($t['model'] ?? '')),
                $t['group_name'] ?? '—',
                $t['imei'] ?? '',
                round($t['total_distance'] ?? 0, 2),
                round($t['running_hours'] ?? 0, 2),
                $t['last_pms_date'] ?? 'Never',
                $t['pms_status'] ?? 'No Data',
                ucfirst($t['status'] ?? 'inactive'),
            ];
        }

        // Blank separator
        $rows[] = array_fill(0, 10, '');

        // Totals row
        $rows[] = [
            '',
            'TOTALS',
            '',
            $this->summary['total_tractors'] . ' tractors',
            '',
            round($this->summary['total_distance'] ?? 0, 2),
            round($this->summary['total_hours'] ?? 0, 2),
            '',
            ($this->summary['pms_due'] ?? 0) . ' due',
            '',
        ];

        return $rows;
    }

    public function headings(): array
    {
        return [
            '#',
            'Plate / Name',
            'Brand & Model',
            'Group',
            'IMEI',
            'Total Distance (km)',
            'Running Hours',
            'Last PMS',
            'PMS Status',
            'Status',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 28,
            'C' => 22,
            'D' => 22,
            'E' => 20,
            'F' => 20,
            'G' => 16,
            'H' => 14,
            'I' => 14,
            'J' => 12,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $dataRows = count($this->tractors);
        $lastDataRow = $dataRows + 1;
        $summaryRow = $lastDataRow + 2;

        // Header row
        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // All data cells borders
        $sheet->getStyle("A1:J{$summaryRow}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Row # center
        $sheet->getStyle("A2:A{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Numeric columns right-align + format
        $sheet->getStyle("F2:F{$summaryRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("G2:G{$summaryRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("F2:F{$summaryRow}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("G2:G{$summaryRow}")->getNumberFormat()->setFormatCode('#,##0.00');

        // Last PMS + PMS Status center
        $sheet->getStyle("H2:H{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("I2:I{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Status column center
        $sheet->getStyle("J2:J{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Alternating row colors
        for ($row = 2; $row <= $lastDataRow; $row++) {
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:J{$row}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9FAFB']],
                ]);
            }
        }

        // Summary row
        $sheet->getStyle("A{$summaryRow}:J{$summaryRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEF2FF']],
            'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '6366F1']]],
        ]);

        // Color code status and PMS cells
        for ($row = 2; $row <= $lastDataRow; $row++) {
            $status = strtolower((string) $sheet->getCell("J{$row}")->getValue());
            $color = match ($status) {
                'online' => '16A34A',
                'offline' => 'DC2626',
                default => '6B7280',
            };
            $sheet->getStyle("J{$row}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color($color));

            $pms = (string) $sheet->getCell("I{$row}")->getValue();
            $pmsColor = match (true) {
                $pms === 'Due' => 'DC2626',
                str_contains($pms, 'left') => '16A34A',
                default => '6B7280',
            };
            $sheet->getStyle("I{$row}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color($pmsColor));
            if ($pms === 'Due') {
                $sheet->getStyle("I{$row}")->getFont()->setBold(true);
            }
        }

        // Auto-filter on header
        $sheet->setAutoFilter("A1:J{$lastDataRow}");

        // Freeze top row
        $sheet->freezePane('A2');

        // Print settings
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setFitToWidth(1);

        return [];
    }

    public function title(): string
    {
        return 'Tractor Usage Data';
    }
}
