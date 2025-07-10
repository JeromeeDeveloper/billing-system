<?php

namespace App\Exports;

use App\Models\Member;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MembersNoBranchExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function collection()
    {
        return Member::whereNull('branch_id')
            ->orWhere('branch_id', '')
            ->orderBy('fname')
            ->orderBy('lname')
            ->get();
    }

    public function headings(): array
    {
        return [
            'CID',
            'Employee ID',
            'Full Name',
            'Address',
            'Birth Date',
            'Gender',
            'Date Registered',
            'Member Tagging',
        ];
    }

    public function map($member): array
    {
        return [
            $member->cid,
            $member->emp_id,
            $member->fname . ' ' . $member->lname,
            $member->address,
            $member->birth_date ? $member->birth_date->format('Y-m-d') : '',
            $member->gender,
            $member->date_registered ? $member->date_registered->format('Y-m-d') : '',
            $member->member_tagging,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header styling for A1:H1 only
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Auto-filter for the header row
        $sheet->setAutoFilter('A1:H1');

        // Column alignment
        $sheet->getStyle('A:H')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('A:H')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        // Date columns: E (Birth Date), G (Date Registered)
        $sheet->getStyle('E')->getNumberFormat()->setFormatCode('yyyy-mm-dd');
        $sheet->getStyle('G')->getNumberFormat()->setFormatCode('yyyy-mm-dd');

        // Wrap text
        $sheet->getStyle('A:H')->getAlignment()->setWrapText(true);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,  // CID
            'B' => 15,  // Employee ID
            'C' => 25,  // Full Name
            'D' => 30,  // Address
            'E' => 12,  // Birth Date
            'F' => 10,  // Gender
            'G' => 15,  // Date Registered
            'H' => 15,  // Member Tagging
        ];
    }
}
