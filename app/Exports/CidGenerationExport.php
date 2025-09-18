<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class CidGenerationExport implements FromCollection, WithHeadings, WithTitle
{
    protected $originalData;
    protected $results;

    public function __construct($originalData, $results)
    {
        $this->originalData = $originalData;
        $this->results = $results;
    }

    public function collection()
    {
        $exportData = collect();

        foreach ($this->originalData as $index => $row) {
            $exportRow = [];

            // Add all original columns
            foreach ($row as $key => $value) {
                $exportRow[$key] = $value;
            }

            // Add the matched CID
            $matchedCid = '';
            if (isset($this->results[$index]) && $this->results[$index]['status'] === 'matched') {
                $matchedCid = $this->results[$index]['cid'];
            }

            $exportRow['matched_cid'] = $matchedCid;
            $exportData->push($exportRow);
        }

        return $exportData;
    }

    public function headings(): array
    {
        $headings = [];

        // Add original column headings
        if (!empty($this->originalData)) {
            $firstRow = $this->originalData[0];
            foreach ($firstRow as $key => $value) {
                $headings[] = ucfirst(str_replace('_', ' ', $key));
            }
        }

        // Add the new CID column
        $headings[] = 'Matched CID';

        return $headings;
    }

    public function title(): string
    {
        return 'CID Generation Results';
    }
}
