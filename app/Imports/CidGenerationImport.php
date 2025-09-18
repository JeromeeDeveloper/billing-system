<?php

namespace App\Imports;

use App\Models\Member;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CidGenerationImport implements ToCollection, WithHeadingRow
{
    public $results = [];
    public $originalData = [];
    public $stats = [
        'total_processed' => 0,
        'matched' => 0,
        'no_match' => 0
    ];

    public function collection(Collection $rows)
    {
        Log::info('CID Generation Import - Processing ' . $rows->count() . ' rows');

        foreach ($rows as $row) {
            $this->stats['total_processed']++;

            // Store original row data
            $this->originalData[] = $row->toArray();

            // Get the name from the Excel file
            $excelName = $this->getExcelName($row);

            if (empty($excelName)) {
                Log::warning('CID Generation - Empty name found in row: ' . json_encode($row->toArray()));
                $this->results[] = [
                    'excel_name' => '',
                    'member_name' => null,
                    'cid' => null,
                    'status' => 'no_match'
                ];
                continue;
            }

            // Try to match with existing members
            $match = $this->findMemberMatch($excelName);

            $this->results[] = [
                'excel_name' => $excelName,
                'member_name' => $match ? $match['member_name'] : null,
                'cid' => $match ? $match['cid'] : null,
                'status' => $match ? 'matched' : 'no_match'
            ];

            if ($match) {
                $this->stats['matched']++;
                Log::info("CID Generation - Matched: {$excelName} -> {$match['member_name']} (CID: {$match['cid']})");
            } else {
                $this->stats['no_match']++;
                Log::info("CID Generation - No match found for: {$excelName}");
            }
        }

        Log::info('CID Generation Import completed. Stats: ' . json_encode($this->stats));
    }

    private function getExcelName($row)
    {
        // Look specifically for 'name' column (Column A with header "NAME")
        if (isset($row['name']) && !empty(trim($row['name']))) {
            return trim($row['name']);
        }

        // Fallback: try other possible column names
        $possibleColumns = ['member_name', 'full_name', 'fullname', 'member', 'client_name'];

        foreach ($possibleColumns as $column) {
            if (isset($row[$column]) && !empty(trim($row[$column]))) {
                return trim($row[$column]);
            }
        }

        // If no standard column found, try the first non-empty column
        foreach ($row as $key => $value) {
            if (!empty(trim($value))) {
                return trim($value);
            }
        }

        return null;
    }

    private function findMemberMatch($excelName)
    {
        // Normalize the Excel name for matching
        $normalizedExcelName = $this->normalizeName($excelName);

        // Get all members and try to match
        $members = Member::all();

        foreach ($members as $member) {
            $memberFullName = trim($member->fname . ' ' . $member->lname);
            $normalizedMemberName = $this->normalizeName($memberFullName);

            // Try different matching strategies
            if ($this->isNameMatch($normalizedExcelName, $normalizedMemberName)) {
                return [
                    'member_name' => $memberFullName,
                    'cid' => $member->cid
                ];
            }
        }

        return null;
    }

    private function normalizeName($name)
    {
        // Convert to uppercase and remove extra spaces
        $name = strtoupper(trim($name));

        // Remove common prefixes/suffixes
        $name = preg_replace('/\b(MR|MRS|MS|DR|PROF)\b\.?\s*/i', '', $name);

        // Remove extra spaces and normalize
        $name = preg_replace('/\s+/', ' ', $name);

        return trim($name);
    }

    private function isNameMatch($excelName, $memberName)
    {
        // Direct match
        if ($excelName === $memberName) {
            return true;
        }

        // Handle "LASTNAME, FIRSTNAME" format from Excel
        if (strpos($excelName, ',') !== false) {
            $parts = explode(',', $excelName, 2);
            $lastName = trim($parts[0]);
            $firstName = trim($parts[1]);

            // Try "FIRSTNAME LASTNAME" format
            $reversedName = $firstName . ' ' . $lastName;
            if ($this->normalizeName($reversedName) === $memberName) {
                return true;
            }
        }

        // Try partial matching for cases where middle names might be missing
        $excelParts = explode(' ', $excelName);
        $memberParts = explode(' ', $memberName);

        if (count($excelParts) >= 2 && count($memberParts) >= 2) {
            // Check if first and last names match
            if ($excelParts[0] === $memberParts[0] &&
                end($excelParts) === end($memberParts)) {
                return true;
            }
        }

        // Try fuzzy matching for similar names
        $similarity = 0;
        similar_text($excelName, $memberName, $similarity);

        if ($similarity > 85) { // 85% similarity threshold
            return true;
        }

        return false;
    }
}
