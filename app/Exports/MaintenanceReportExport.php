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

class MaintenanceReportExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->data as $i => $device) {
            $totalHours = (float)($device['total_hours'] ?? 0);
            $hoursLeft = max(0, 100 - $totalHours);
            $needsPms = !empty($device['needs_pms']);
            $pmsStatus = $needsPms ? 'Due Now' : ceil($hoursLeft) . ' hrs left';

            $status = match ($device['status'] ?? '') {
                '1' => 'Online',
                default => 'Offline',
            };

            $rows[] = [
                $i + 1,
                $device['device_name'] ?? '',
                $device['imei'] ?? '',
                $totalHours,
                $device['total_distance'] ?? 0,
                $pmsStatus,
                $status,
                $device['last_active'] ?? 'N/A',
            ];
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            '#',
            'Device Name',
            'IMEI',
            'Total Hours',
            'Total Distance (km)',
            'PMS Due',
            'Status',
            'Last Active',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 25,
            'C' => 20,
            'D' => 14,
            'E' => 22,
            'F' => 16,
            'G' => 12,
            'H' => 22,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = count($this->data) + 1;

        // Header row styling
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '198754'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // All cells border
        $sheet->getStyle("A1:H{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D0D0D0'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Center-align numeric columns
        $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D2:D{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("E2:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("G2:G{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Highlight "Due Now" rows
        for ($row = 2; $row <= $lastRow; $row++) {
            $pmsValue = $sheet->getCell("F{$row}")->getValue();
            if ($pmsValue === 'Due Now') {
                $sheet->getStyle("F{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'DC3545']],
                ]);
            }
        }

        return [];
    }

    public function title(): string
    {
        return 'Maintenance Report';
    }
}
