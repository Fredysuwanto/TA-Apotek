<?php

namespace App\Imports;

use Carbon\Carbon;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class SalesDataImport implements OnEachRow, WithHeadingRow, WithChunkReading
{
    // penampung error & baris valid
    protected array $errors = [];
    protected array $validRows = [];

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getValidRows(): array
    {
        return $this->validRows;
    }

    public function onRow(Row $row)
    {
        $rowIndex = $row->getIndex();      // nomor baris di file (2 = baris data pertama)
        $data     = $row->toArray();

        $tglRaw    = trim($data['vc_tgl_nota']   ?? '');
        $obatRaw   = trim($data['vc_n_obat']     ?? '');
        $satuanRaw = trim($data['vc_n_satuan']   ?? '');
        $hargaRaw  = trim($data['dc_rupiah']     ?? '');
        $qtyRaw    = trim($data['dc_qty']        ?? '');

        $messages = [];

        /* =========================
         * 1. CEK KOSONG
         * ========================= */
        $emptyCols = [];
        if ($tglRaw     === '') $emptyCols[] = 'vc_tgl_nota';
        if ($obatRaw    === '') $emptyCols[] = 'vc_n_obat';
        if ($satuanRaw  === '') $emptyCols[] = 'vc_n_satuan';
        if ($hargaRaw   === '') $emptyCols[] = 'dc_rupiah';
        if ($qtyRaw     === '') $emptyCols[] = 'dc_qty';

        if (!empty($emptyCols)) {
            $messages[] = 'Kolom ' . implode(', ', $emptyCols) . ' kosong';
        }

        // baris komentar diawali '#'
        if (substr($tglRaw, 0, 1) === '#') {
            $messages[] = 'Baris komentar (diawali "#")';
        }

        /* =========================
         * 2. CEK NILAI '-'
         * ========================= */
        $dashCols = [];
        if ($tglRaw    === '-') $dashCols[] = 'vc_tgl_nota';
        if ($obatRaw   === '-') $dashCols[] = 'vc_n_obat';
        if ($satuanRaw === '-') $dashCols[] = 'vc_n_satuan';
        if ($hargaRaw  === '-') $dashCols[] = 'dc_rupiah';
        if ($qtyRaw    === '-') $dashCols[] = 'dc_qty';

        if (!empty($dashCols)) {
            $messages[] = 'Kolom ' . implode(', ', $dashCols) . " berisi '-' (missing data)";
        }

        /* =========================
         * 3. VALIDASI ANGKA
         *    dc_rupiah & dc_qty
         *    + konversi Rp -> decimal
         * ========================= */

        // --- rupiah ---
        $hargaClean = null;
        if ($hargaRaw !== '' && $hargaRaw !== '-') {
            $hargaClean = strtolower($hargaRaw);
            $hargaClean = str_replace(['rp', ' '], '', $hargaClean);

            // buang karakter selain digit, koma, titik
            $hargaClean = preg_replace('/[^0-9\.,]/', '', $hargaClean);

            if (strpos($hargaClean, ',') !== false && strpos($hargaClean, '.') !== false) {
                // contoh: 10.000,50 -> 10000.50
                $hargaClean = str_replace('.', '', $hargaClean);
                $hargaClean = str_replace(',', '.', $hargaClean);
            } elseif (strpos($hargaClean, ',') !== false && strpos($hargaClean, '.') === false) {
                // cuma ada koma -> anggap pemisah ribuan, 21,312 -> 21312
                $hargaClean = str_replace(',', '', $hargaClean);
            } else {
                // cuma titik atau angka -> biarkan
            }

            if (!is_numeric($hargaClean)) {
                $messages[] = "dc_rupiah harus berupa angka, ditemukan '{$hargaRaw}'";
            }
        }

        // --- qty ---
        $qtyClean = null;
        if ($qtyRaw !== '' && $qtyRaw !== '-') {
            $qtyClean = str_replace(',', '.', $qtyRaw);

            if (!is_numeric($qtyClean)) {
                $messages[] = "dc_qty harus berupa angka, ditemukan '{$qtyRaw}'";
            }
        }

        /* =========================
         * 4. PARSE TANGGAL
         * ========================= */
        $tanggal = null;
        if ($tglRaw !== '' && $tglRaw !== '-') {
            try {
                // d/m/Y
                $tanggal = Carbon::createFromFormat('d/m/Y', $tglRaw)->format('Y-m-d');
            } catch (\Exception $e) {
                try {
                    // d-m-Y
                    $tanggal = Carbon::createFromFormat('d-m-Y', $tglRaw)->format('Y-m-d');
                } catch (\Exception $e2) {
                    $messages[] = "Format tanggal tidak valid: '{$tglRaw}' (gunakan dd/mm/yyyy atau dd-mm-yyyy)";
                }
            }
        }

        /* =========================
         * 5. JIKA ADA ERROR:
         *    -> CATAT & JANGAN MASUK validRows
         * ========================= */
        if (!empty($messages)) {
            $this->errors[] = [
                'row'     => $rowIndex,
                'message' => implode(' | ', $messages),
            ];
            return;
        }

        /* =========================
         * 6. JIKA LOLOS VALIDASI:
         *    -> SIMPAN KE ARRAY validRows (BELUM KE DB)
         * ========================= */
        $x1 = round((float)$hargaClean, 2);  // decimal(10,2)
        $y  = round((float)$qtyClean,   0);  // penjualan (tanpa decimal)

        $this->validRows[] = [
            'tanggal'   => $tanggal,
            'nama_obat' => $obatRaw,
            'satuan'    => $satuanRaw,
            'x1'        => $x1,
            'x2'        => null,   // curah hujan diisi dari import lain
            'y'         => $y,
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
