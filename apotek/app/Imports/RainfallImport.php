<?php

namespace App\Imports;

use Carbon\Carbon;
use App\Models\RainfallData;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class RainfallImport implements ToModel, WithHeadingRow, WithChunkReading
{
    public function model(array $row)
    {
        $tglRaw   = trim($row['tanggal'] ?? '');
        $chRaw    = trim($row['curahhujan'] ?? '');

        // skip baris yang tanggalnya kosong
        if ($tglRaw === '' || substr($tglRaw, 0, 1) === '#') {
            return null;
        }

        // parse tanggal
        $tanggal = null;
        try {
            $tanggal = Carbon::createFromFormat('d/m/Y', $tglRaw)->format('Y-m-d');
        } catch (\Exception $e) {
            try {
                $tanggal = Carbon::createFromFormat('d-m-Y', $tglRaw)->format('Y-m-d');
            } catch (\Exception $e2) {
                return null; // gagal parse → skip
            }
        }

        // mapping nilai khusus 8888, 9999, '-' → 0
        $special = ['8888', '9999', '-'];

        if ($chRaw === '' || in_array($chRaw, $special, true)) {
            $curah = 0.0;
        } else {
            $curah = (float) preg_replace('/[^0-9.]/', '', $chRaw);
        }

        return new RainfallData([
            'tanggal'    => $tanggal,
            'curahhujan' => $curah,
        ]);
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
