<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SharesImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {

    }
}
