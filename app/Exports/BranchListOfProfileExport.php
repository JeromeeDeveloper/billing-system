<?php

namespace App\Exports;

use App\Models\Member;
use App\Models\Branch;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BranchListOfProfileExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle
{
    protected $branchId;

    public function __construct($branchId = null)
    {
        $this->branchId = $branchId;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Member::with(['branch', 'savings', 'shares', 'loanForecasts'])
            ->where('branch_id', $this->branchId)
            ->get();
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Name',
            'Branch',
            'Loan Balance',
            'Savings Balance',
            'Share Balance',
            'Loan Accounts',
            'Savings Accounts',
            'Share Accounts',
        ];
    }

    public function map($member): array
    {
        return [
            $member->emp_id,
            $member->fname . ' ' . $member->lname,
            $member->branch ? $member->branch->name : '',
            $member->loan_balance,
            $member->savings_balance,
            $member->share_balance,
            $member->loanForecasts->pluck('loan_acct_no')->implode(', '),
            $member->savings->pluck('account_number')->implode(', '),
            $member->shares->pluck('account_number')->implode(', '),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center',
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ]);
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('F')->setAutoSize(true);
        $sheet->getColumnDimension('G')->setAutoSize(true);
        $sheet->getColumnDimension('H')->setAutoSize(true);
        $sheet->getColumnDimension('I')->setAutoSize(true);
        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 25,
            'C' => 20,
            'D' => 15,
            'E' => 15,
            'F' => 15,
            'G' => 25,
            'H' => 25,
            'I' => 25,
        ];
    }

    public function title(): string
    {
        return 'Branch List of Profile';
    }
}
