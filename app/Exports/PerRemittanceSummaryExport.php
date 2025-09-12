<?php

namespace App\Exports;

use App\Models\RemittanceReport;
use App\Models\RemittanceBatch;
use App\Models\LoanForecast;
use App\Models\Member;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Illuminate\Support\Collection;

class PerRemittanceSummaryExport implements FromArray, WithHeadings, WithTitle, WithStyles
{
	protected $billingPeriod;
	protected $isBranch;
	protected $branchId;
	protected $billingType;

	public function __construct($billingPeriod, $isBranch = false, $branchId = null, $billingType = null)
	{
		$this->billingPeriod = $billingPeriod;
		$this->isBranch = $isBranch;
		$this->branchId = $branchId;
		$this->billingType = $billingType;
	}

	public function title(): string
	{
		$type = $this->billingType ? ucfirst($this->billingType) . ' ' : '';
		return $this->isBranch ? "Branch Per-Remittance Summary ({$type})" : "Admin Per-Remittance Summary ({$type})";
	}

	public function headings(): array
	{
		return ['CID', 'Member Name', 'Type', 'Remitted Loans', 'Remitted Savings', 'Remitted Shares', 'Total Remitted'];
	}

	public function array(): array
	{
		// Get billing type for this billing period (Regular/Special)
		$billingType = $this->billingType ?: RemittanceBatch::where('billing_period', $this->billingPeriod)
			->value('billing_type') ?? 'Regular';

		// Get remittance tags for the specified billing type
		$remittanceTags = [];
		if ($this->billingType) {
			$remittanceTags = RemittanceBatch::where('billing_period', $this->billingPeriod)
				->where('billing_type', $this->billingType)
				->pluck('remittance_tag')
				->toArray();
		}

		// Get all members with remittance data
		$query = RemittanceReport::where('period', $this->billingPeriod);

		if ($this->isBranch && $this->branchId) {
			$query->whereHas('member', function($q) {
				$q->where('branch_id', $this->branchId);
			});
		}

		// Filter by remittance tags if billing type is specified
		if ($this->billingType) {
			if (!empty($remittanceTags)) {
				$query->whereIn('remittance_tag', $remittanceTags);
			} else {
				// If billing type is specified but no remittance tags exist for that type,
				// return empty results (no data for this billing type)
				$query->where('remittance_tag', -1); // This will return no results
			}
		}

		$reports = $query->get();

		// Group by CID
		$groupedData = $reports->groupBy('cid');

		$rows = [];

		// Add summary data for all members (only if they have non-zero values)
		foreach ($groupedData as $cid => $memberReports) {
			$memberName = $memberReports->first()->member_name ?? '';

			$totalLoans = $memberReports->where('remittance_type', 'loans_savings')->sum('remitted_loans');
			$totalSavings = $memberReports->where('remittance_type', 'loans_savings')->sum('remitted_savings');
			$totalShares = $memberReports->where('remittance_type', 'shares')->sum('remitted_shares');
			$totalRemitted = $totalLoans + $totalSavings + $totalShares;

			// Only include member if they have non-zero values
			if ($totalRemitted > 0) {
				$summaryRow = [
					$cid,
					$memberName,
					$billingType,
					$totalLoans,
					$totalSavings,
					$totalShares,
					$totalRemitted
				];
				$rows[] = $summaryRow;
			}
		}

		return $rows;
	}

	public function styles(Worksheet $sheet)
	{
		$sheet->getStyle('A1:G1')->applyFromArray([
			'font' => ['bold' => true],
			'fill' => [
				'fillType' => Fill::FILL_SOLID,
				'startColor' => ['rgb' => 'E6E6FA']
			],
			'borders' => [
				'allBorders' => [
					'borderStyle' => Border::BORDER_THIN,
					'color' => ['rgb' => '000000']
				]
			]
		]);

		// Auto-size columns
		foreach (range('A', 'G') as $column) {
			$sheet->getColumnDimension($column)->setAutoSize(true);
		}

		return [];
	}
}
