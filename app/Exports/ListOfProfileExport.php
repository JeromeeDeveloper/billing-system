<?php

namespace App\Exports;

use App\Models\Branch;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ListOfProfileExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle
{
    public function collection()
    {
        return Branch::with(['members' => function($query) {
            $query->orderBy('lname')->orderBy('fname');
        }])->get();
    }

    public function headings(): array
    {
        return [
            ['List of Profile'],
            ['Payroll Month', now()->format('F Y')],
            [''],
        ];
    }

    public function map($branch): array
    {
        $rows = [];

        // Add branch header and name
        $rows[] = [
            'Branch Name',
            $branch->name
        ];

        // Add CID and Name headers
        $rows[] = [
            'CID',
            'Name'
        ];

        // Add members
        foreach ($branch->members as $member) {
            $rows[] = [
                $member->cid,
                $member->lname . ', ' . $member->fname
            ];
        }

        // Add count
        $rows[] = [
            'Count',
            $branch->members->count()
        ];

        // Add empty row for spacing
        $rows[] = ['', ''];

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => 'center']],
            2 => ['font' => ['bold' => true]],
            4 => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 40,
        ];
    }

    public function title(): string
    {
        return 'List of Profile';
    }
}
