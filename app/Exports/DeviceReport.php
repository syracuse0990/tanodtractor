<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class DeviceReport implements FromCollection, WithHeadings, WithStyles, WithEvents
{
    protected $data;
    protected $columns;
    /**
     * @return \Illuminate\Support\Collection
     */
    public function __construct($data, $columns)
    {
        $this->data = $data;
        $this->columns = $columns;
    }

    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        // First row with empty cells
        $headings = array_fill(0, $this->columns, '');

        // Second row with actual headings
        $headings[] = 'Heading 1';
        $headings[] = 'Heading 2';
        // Add other headings as needed...

        return [$headings, $headings];
    }

    public function styles($sheet)
    {
        $sheet->getStyle('A1')->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ]
        ]);
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Merge cells for the first row
                $event->sheet->mergeCells('A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($this->columns) . '1');
            },
        ];
    }
}
