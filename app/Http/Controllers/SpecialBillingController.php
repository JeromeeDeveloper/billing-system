<?php

namespace App\Http\Controllers;

use App\Models\SpecialBilling;
use Illuminate\Http\Request;
use App\Imports\SpecialBillingImport;
use App\Exports\SpecialBillingExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SpecialBillingController extends Controller
{
    public function index()
    {
        $specialBillings = SpecialBilling::all();
        return view('components.admin.special_billing', compact('specialBillings'));
    }

    public function import(Request $request)
    {
        ini_set('max_execution_time', 600);
        $request->validate([
            'forecast_file' => 'required|mimes:xlsx,xls,csv|max:10240',
            'detail_file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        // 1. Process forecast file (CSV: skip first 4 rows, row 5 is header, row 6+ is data)
        $forecastRowsRaw = Excel::toCollection(null, $request->file('forecast_file'))[0];
        $forecastRows = collect();
        $headerRow = [];
        foreach ($forecastRowsRaw as $i => $row) {
            if ($i == 4) {
                $headerRow = $row->toArray();
            } elseif ($i > 4) {
                $assoc = [];
                foreach ($headerRow as $k => $header) {
                    $assoc[strtolower(trim($header))] = $row[$k] ?? null;
                }
                $forecastRows->push($assoc);
            }
        }
        $forecastGrouped = $forecastRows->groupBy(function ($row) {
            return strval(trim($row['cid'] ?? ''));
        });
        $forecastData = [];
        foreach ($forecastGrouped as $cid => $rows) {
            if (empty($cid)) continue;
            $totalDue = $rows->sum(function ($row) {
                $value = $row['total due'] ?? 0;
                $value = preg_replace('/[^0-9.]/', '', str_replace(',', '', $value));
                return floatval($value);
            });
            $name = $rows->first()['name'] ?? null;
            $forecastData[$cid] = [
                'cid' => $cid,
                'name' => $name,
                'amortization' => $totalDue,
                'total_due' => $totalDue,
            ];
        }

        // 2. Process detail file (CSV: skip first 5 rows, row 6 is header, row 7+ is data)
        $detailRowsRaw = Excel::toCollection(null, $request->file('detail_file'))[0];
        $detailRows = collect();
        $headerRow = [];
        foreach ($detailRowsRaw as $i => $row) {
            if ($i == 5) {
                $headerRow = $row->toArray();
            } elseif ($i > 5) {
                $assoc = [];
                foreach ($headerRow as $k => $header) {
                    $assoc[strtolower(trim($header))] = $row[$k] ?? null;
                }
                $detailRows->push($assoc);
            }
        }
        $detailByCid = [];
        foreach ($detailRows as $row) {
            $cid = strval(trim($row['cid'] ?? ''));
            $gross = floatval($row['gross'] ?? 0);
            $startDateRaw = $row['start_date'] ?? null;
            $endDateRaw = $row['end_date'] ?? null;
            $startDate = null;
            $endDate = null;
            try {
                if ($startDateRaw) $startDate = Carbon::createFromFormat('m/d/Y', $startDateRaw)->format('Y-m-d');
            } catch (\Exception $e) {}
            try {
                if ($endDateRaw) $endDate = Carbon::createFromFormat('m/d/Y', $endDateRaw)->format('Y-m-d');
            } catch (\Exception $e) {}
            if (empty($cid)) continue;
            if (!isset($detailByCid[$cid]) || $gross > $detailByCid[$cid]['gross']) {
                $detailByCid[$cid] = [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'gross' => $gross,
                ];
            }
        }

        // 3. Merge and store in special_billings
        foreach ($forecastData as $cid => $data) {
            $detail = $detailByCid[$cid] ?? null;
            \App\Models\SpecialBilling::updateOrCreate(
                ['cid' => $cid],
                [
                    'employee_id' => 'N/A',
                    'name' => $data['name'],
                    'amortization' => $data['amortization'],
                    'start_date' => $detail['start_date'] ?? null,
                    'end_date' => $detail['end_date'] ?? null,
                    'gross' => $detail['gross'] ?? 0,
                    'total_due' => $data['total_due'],
                ]
            );
        }

        return redirect()->route('special-billing.index')->with('success', 'Special billing files imported and merged successfully.');
    }

    public function export()
    {
        return Excel::download(new SpecialBillingExport, 'special_billing_export_' . now()->format('Y-m-d') . '.xlsx');
    }
}
