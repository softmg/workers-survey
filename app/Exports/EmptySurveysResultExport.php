<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;

class EmptySurveysResultExport implements FromCollection
{
    use Exportable;

    public function collection(): Collection
    {
        return collect([]);
    }
}
