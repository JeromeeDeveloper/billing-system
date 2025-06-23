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

        // 1. Process forecast file (group by cid, sum total_due)
        $forecastRows = Excel::toCollection(null, $request->file('forecast_file'))[0];
        $forecastGrouped = $forecastRows->groupBy(function ($row) {
            return strval(trim($row['cid'] ?? ''));
        });
        $forecastData = [];
        foreach ($forecastGrouped as $cid => $rows) {
            if (empty($cid)) continue;
            $totalDue = $rows->sum(function ($row) {
                return floatval($row['total_due'] ?? 0);
            });
            $name = $rows->first()['name'] ?? null;
            $office = $rows->first()['office'] ?? null;
            $forecastData[$cid] = [
                'cid' => $cid,
                'name' => $name,
                'amortization' => $totalDue,
                'office' => $office,
                'total_due' => $totalDue,
            ];
        }

        Log::info('Forecast rows count: ' . $forecastRows->count());
        Log::info('Forecast rows sample:', $forecastRows->take(3)->toArray());

        // 2. Process detail file (find highest principal per cid)
        $detailRows = Excel::toCollection(null, $request->file('detail_file'))[0];
        $detailByCid = [];
        foreach ($detailRows as $row) {
            $cid = strval(trim($row[0] ?? ''));
            $principal = floatval($row[11] ?? 0); // Column L (index 11) is principal
            $openDateRaw = $row[6] ?? null; // Column 7 (index 6)
            $endDateRaw = $row[7] ?? null; // Column 8 (index 7)
            $gross = $row[10] ?? null; // Column 11 (index 10)
            $openDate = null;
            $endDate = null;
            try {
                if ($openDateRaw) $openDate = Carbon::createFromFormat('m/d/Y', $openDateRaw)->format('Y-m-d');
            } catch (\Exception $e) {}
            try {
                if ($endDateRaw) $endDate = Carbon::createFromFormat('m/d/Y', $endDateRaw)->format('Y-m-d');
            } catch (\Exception $e) {}
            if (empty($cid)) continue;
            if (!isset($detailByCid[$cid]) || $principal > $detailByCid[$cid]['principal']) {
                $detailByCid[$cid] = [
                    'open_date' => $openDate,
                    'end_date' => $endDate,
                    'gross' => $gross,
                    'principal' => $principal,
                ];
            }
        }

        Log::info('Detail rows count: ' . $detailRows->count());
        Log::info('Detail rows sample:', $detailRows->take(3)->toArray());
        Log::info('ForecastData keys:', array_keys($forecastData));
        Log::info('DetailByCid keys:', array_keys($detailByCid));

        // 3. Merge and store in special_billings
        foreach ($forecastData as $cid => $data) {
            $detail = $detailByCid[$cid] ?? null;
            Log::info('Attempting to store:', ['cid' => $cid, 'data' => $data, 'detail' => $detail]);
            \App\Models\SpecialBilling::updateOrCreate(
                ['cid' => $cid],
                [
                    'name' => $data['name'],
                    'amortization' => $data['amortization'],
                    'start_date' => $detail['open_date'] ?? null,
                    'end_date' => $detail['end_date'] ?? null,
                    'gross' => $detail['gross'] ?? 0,
                    'office' => $data['office'] ?? null,
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
