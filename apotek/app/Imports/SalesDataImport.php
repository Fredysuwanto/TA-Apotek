<?php

namespace App\Imports;
use Carbon\Carbon;
use App\Models\SalesData;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class SalesDataImport implements ToModel, WithHeadingRow, WithChunkReading
{
    public function model(array $row)
    {
        // ambil & bersihkan raw tanggal
        $rawTanggal = trim($row['vc_tgl_nota']);

        // coba parse, hasilnya menjadi string 'Y-m-d' atau datetime sesuai kebutuhan
        try {
            $tanggalCarbon = Carbon::createFromFormat('d/m/Y', $rawTanggal);
            // jika DB kolom bertipe DATETIME gunakan ->format('Y-m-d H:i:s')
            $tanggal = $tanggalCarbon->format('Y-m-d'); // atau 'Y-m-d H:i:s'
        } catch (\Exception $e) {
            $tanggal = null; // atau handle/log sesuai kebijakan
        }

        return new SalesData([
            'tanggal'   => $tanggal,                // <-- pastikan ini variabel / string
            'nama_obat' => trim($row['vc_n_obat']),
            'x1'        => (float) preg_replace('/[^0-9.]/', '', $row['dc_rupiah']),
            'x2'        => (float) preg_replace('/[^0-9.]/', '', $row['curahhujan']),
            'y'         => (float) preg_replace('/[^0-9.]/', '', $row['dc_qty']),
        ]);
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
