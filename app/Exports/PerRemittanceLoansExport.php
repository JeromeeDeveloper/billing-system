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

class PerRemittanceLoansExport implements FromArray, WithHeadings, WithTitle, WithStyles
{
	protected $billingPeriod;
	protected $isBranch;
	protected $branchId;

	public function __construct($billingPeriod, $isBranch = false, $branchId = null)
	{
		$this->billingPeriod = $billingPeriod;
		$this->isBranch = $isBranch;
		$this->branchId = $branchId;
	}

	public function title(): string
	{
		return $this->isBranch ? 'Branch Per-Remittance Loans' : 'Admin Per-Remittance Loans';
	}

	public function headings(): array
	{
		// Get all remittance tags for this billing period
		$remittanceTags = RemittanceBatch::where('billing_period', $this->billingPeriod)
			->orderBy('remittance_tag')
			->pluck('remittance_tag')
			->toArray();

		$maxTags = count($remittanceTags);

		$loansHeaders = ['CID', 'Member Name', 'Type', 'Billed Amount'];
		for ($i = 1; $i <= $maxTags; $i++) {
			$loansHeaders[] = "Remittance Loans {$i}";
		}
		$loansHeaders[] = 'Running Balance';

		return $loansHeaders;
	}

	public function array(): array
	{
		// Get billing type for this billing period (Regular/Special)
		$billingType = RemittanceBatch::where('billing_period', $this->billingPeriod)
			->value('billing_type') ?? 'Regular';

		// Get all remittance tags for this billing period
		$remittanceTags = RemittanceBatch::where('billing_period', $this->billingPeriod)
			->orderBy('remittance_tag')
			->pluck('remittance_tag')
			->toArray();

		$maxTags = count($remittanceTags);

		// Get all members with remittance data
		$query = RemittanceReport::where('period', $this->billingPeriod);

		if ($this->isBranch && $this->branchId) {
			$query->whereHas('member', function($q) {
				$q->where('branch_id', $this->branchId);
			});
		}

		$reports = $query->get();

		// Group by CID
		$groupedData = $reports->groupBy('cid');

		$rows = [];

		// Add loans data for all members (only if they have non-zero loan values)
		foreach ($groupedData as $cid => $memberReports) {
			$memberName = $memberReports->first()->member_name ?? '';

			$totalLoans = $memberReports->where('remittance_type', 'loans_savings')->sum('remitted_loans');

			// Only include member if they have non-zero loan values
			if ($totalLoans > 0) {
				// billed amount = original_total_due for this period via member relationship
				$billedAmount = 0;
				$member = Member::where('cid', $cid)->first();
				if ($member) {
					$origPrincipal = (float) $member->loanForecasts()
						->where('billing_period', $this->billingPeriod)
						->sum('original_principal_due');
					$origInterest = (float) $member->loanForecasts()
						->where('billing_period', $this->billingPeriod)
						->sum('original_interest_due');
					$billedAmount = $origPrincipal + $origInterest;
				}

				$loansRow = [
					$cid,
					$memberName,
					$billingType,
					$billedAmount
				];

				$totalLoansPaid = 0;
				foreach ($remittanceTags as $tag) {
					$loanAmount = $memberReports->where('remittance_tag', $tag)->where('remittance_type', 'loans_savings')->first()->remitted_loans ?? 0;
					$loansRow[] = $loanAmount;
					$totalLoansPaid += $loanAmount;
				}

				$runningBalance = $billedAmount - $totalLoansPaid;
				$loansRow[] = $runningBalance;
				$rows[] = $loansRow;
			}
		}

		return $rows;
	}

	public function styles(Worksheet $sheet)
	{
		$lastColumn = $sheet->getHighestColumn();
		$sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
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
		foreach (range('A', $lastColumn) as $column) {
			$sheet->getColumnDimension($column)->setAutoSize(true);
		}

		return [];
	}
}
