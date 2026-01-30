<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

use App\Imports\SalesDataImport;
use App\Imports\RainfallImport;

use App\Models\SalesData;
use App\Models\RainfallData;
use App\Models\RegressionResult;

use App\Services\RegressionService;
use Carbon\Carbon;

class SalesPredictionController extends Controller
{
    /**
     * Tampilkan dashboard utama (upload, input manual, tabel data).
     */
    public function index()
    {
        $salesData = SalesData::orderBy('tanggal', 'desc')->paginate(50);
        $obatList  = SalesData::distinct()->pluck('nama_obat');

        return view('prediction.salesprediction', compact('salesData', 'obatList'));
    }

    /**
     * Import data penjualan dari CSV.
     * Mekanisme ALL-OR-NOTHING:
     * - Jika ada SATU saja baris error -> TIDAK ADA data yang disimpan.
     * - Jika semua baris valid -> semua disimpan ke database.
     */
    public function importSales(Request $request)
    {
        $request->validate([
            'file_sales' => 'required|mimes:csv,txt',
        ]);

        ini_set('max_execution_time', 0);

        // Pakai instance supaya bisa ambil error & baris valid
        $import = new SalesDataImport();
        Excel::import($import, $request->file('file_sales'));

        $errors    = $import->getErrors();
        $validRows = $import->getValidRows();

        // ❌ Ada SATU saja baris error -> batal semua
        if (!empty($errors)) {
            return back()->with([
                'error'         => 'Import dibatalkan karena ada data yang tidak valid. Silakan perbaiki file CSV terlebih dahulu.',
                'import_errors' => $errors,
            ]);
        }

        // ✅ Tidak ada error -> simpan semua baris valid dalam satu transaksi
        if (!empty($validRows)) {
            DB::transaction(function () use ($validRows) {
                foreach ($validRows as $row) {
                    SalesData::create($row);
                }
            });

            // setelah import penjualan, merge dengan data curah hujan yang sudah ada
            $this->syncRainfallToSales();

            return back()->with([
                'success'       => 'Data penjualan berhasil diimport!',
                'import_errors' => [],
            ]);
        }

        // File hanya berisi header / komentar / tidak ada data valid
        return back()->with([
            'error' => 'Tidak ada data yang bisa diimport dari file CSV ini.',
        ]);
    }

    /**
     * Import data curah hujan dari CSV.
     */
    public function importRainfall(Request $request)
    {
        $request->validate([
            'file_rainfall' => 'required|mimes:csv,txt',
        ]);

        ini_set('max_execution_time', 0);

        Excel::import(new RainfallImport, $request->file('file_rainfall'));

        // setelah import curah hujan, merge ke tabel penjualan
        $this->syncRainfallToSales();

        return back()->with('success', 'Data curah hujan berhasil diimport!');
    }

    /**
     * Sinkronisasi curah hujan (RainfallData) ke field x2 di SalesData.
     */
    private function syncRainfallToSales()
    {
        $rainfalls = RainfallData::all();

        foreach ($rainfalls as $rf) {
            // update semua baris penjualan pada tanggal tersebut
            // hanya yang x2 masih null (biar tidak menimpa input manual)
            SalesData::whereDate('tanggal', $rf->tanggal)
                ->whereNull('x2')
                ->update([
                    'x2' => $rf->curahhujan,
                ]);
        }
    }

    /**
     * Helper: sort hasil prediksi dari yang terbesar.
     */
    private function sortPredictions(&$normalResults, &$fallbackResults)
    {
        usort($normalResults, function ($a, $b) {
            return $b['prediksi'] <=> $a['prediksi'];
        });
        usort($fallbackResults, function ($a, $b) {
            return $b['prediksi'] <=> $a['prediksi'];
        });
    }

    /**
     * Simpan input manual satu baris sales data.
     */
    public function storeManual(Request $request)
    {
        $request->validate([
            'tanggal'   => 'required|date',
            'nama_obat' => 'required',
            'satuan'    => 'nullable|string',
            'x1'        => 'required|numeric',
            'x2'        => 'required|numeric',
            'y'         => 'required|numeric',
        ]);

        SalesData::create([
            'tanggal'   => $request->tanggal,
            'nama_obat' => $request->nama_obat,
            'satuan'    => $request->satuan,
            'x1'        => $request->x1,
            'x2'        => $request->x2,
            'y'         => $request->y,
        ]);

        // AUTO CALCULATE PREDICTIONS setelah tambah data
        $regressionService = new RegressionService();
        $regressionService->calculateAndSaveAllPredictions();

        return redirect()->back()->with('success', 'Data berhasil ditambahkan dan prediksi dihitung!');
    }

    /**
     * Tampilkan hasil prediksi (regressionResults).
     */
    public function predictionResults()
    {
        try {
            Log::info('=== PREDICTION RESULTS START ===');

            $regressionService = new RegressionService();
            $regressionResults = RegressionResult::all();
            Log::info('Regression results count: ' . $regressionResults->count());

            $normalResults   = [];
            $fallbackResults = [];

            foreach ($regressionResults as $result) {
                try {
                    $predictedValue = $regressionService->getLatestPrediction($result->nama_obat);
                    $satuan         = SalesData::where('nama_obat', $result->nama_obat)->value('satuan');

                    if ($predictedValue !== null) {
                        $resultData = [
                            'nama_obat'    => $result->nama_obat,
                            'satuan'       => $satuan,
                            'prediksi'     => $predictedValue,
                            'equation'     => $result->equation,
                            'coefficients' => $result->coefficients,
                            'metrics'      => $result->metrics,
                        ];

                        if (strpos($result->equation, 'Fallback - Average') !== false) {
                            $fallbackResults[] = $resultData;
                        } else {
                            $normalResults[] = $resultData;
                        }

                        Log::info("Prediction for {$result->nama_obat}: {$predictedValue}");
                    }
                } catch (\Exception $e) {
                    Log::error("Error processing {$result->nama_obat}: " . $e->getMessage());
                    continue;
                }
            }

            $this->sortPredictions($normalResults, $fallbackResults);
            $results = array_merge($normalResults, $fallbackResults);

            if (empty($results)) {
                Log::info('No predictions found, calculating...');
                $regressionService->calculateAndSaveAllPredictions();

                // Reset dan ambil ulang data
                $normalResults   = [];
                $fallbackResults = [];
                $regressionResults = RegressionResult::all();

                foreach ($regressionResults as $result) {
                    $predictedValue = $regressionService->getLatestPrediction($result->nama_obat);
                    $satuan         = SalesData::where('nama_obat', $result->nama_obat)->value('satuan');

                    if ($predictedValue !== null) {
                        $resultData = [
                            'nama_obat'    => $result->nama_obat,
                            'satuan'       => $satuan,
                            'prediksi'     => $predictedValue,
                            'equation'     => $result->equation,
                            'coefficients' => $result->coefficients,
                            'metrics'      => $result->metrics,
                        ];

                        if (strpos($result->equation, 'Fallback - Average') !== false) {
                            $fallbackResults[] = $resultData;
                        } else {
                            $normalResults[] = $resultData;
                        }
                    }
                }

                $this->sortPredictions($normalResults, $fallbackResults);
                $results = array_merge($normalResults, $fallbackResults);
            }

            Log::info('Final results count: ' . count($results));
            return view('prediction.results', compact('results'));
        } catch (\Exception $e) {
            Log::error('Error in predictionResults: ' . $e->getMessage());

            return view('prediction.results', ['results' => []])
                ->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Hapus semua data sales & hasil regresi.
     */
    public function clearData()
    {
        SalesData::truncate();
        RegressionResult::truncate();

        return redirect()->back()->with('success', 'Data berhasil dihapus!');
    }

    /**
     * Hapus satu baris data sales.
     */
    public function deleteData($id)
    {
        $data = SalesData::find($id);

        if ($data) {
            $data->delete();

            // Recalculate predictions after delete
            $regressionService = new RegressionService();
            $regressionService->calculateAndSaveAllPredictions();
        }

        return redirect()->back()->with('success', 'Data berhasil dihapus!');
    }

    /**
     * Download template CSV untuk penjualan obat.
     */
    public function downloadTemplate()
    {
        $template  = "tanggal,nama_obat,x1,x2,y\n";
        $template .= "01/01/2024,Antasida,100.5,25.5,20\n";
        $template .= "02/01/2024,Antasida,105.0,30.2,25\n";
        $template .= "03/01/2024,Amoxilin,85.0,22.1,15\n";
        $template .= "# FORMAT: DD/MM/YYYY atau DD-MM-YYYY\n";
        $template .= "# X1 (harga): 100.50 (2 decimal)\n";
        $template .= "# X2 (curah hujan): 25.5 (1 decimal)\n";
        $template .= "# Y (penjualan): 20 (tanpa decimal)\n";

        return response($template)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename=\"template_obat.csv\"');
    }

    /**
     * Hitung ulang semua regresi & prediksi.
     */
    public function calculateRegression()
    {
        try {
            $regressionService = new RegressionService();
            $regressionService->calculateAndSaveAllPredictions();

            return redirect()
                ->route('prediction.results')
                ->with('success', 'Prediksi berhasil dihitung ulang!');
        } catch (\Exception $e) {
            return redirect()
                ->route('prediction.index')
                ->with('error', 'Error menghitung prediksi: ' . $e->getMessage());
        }
    }
}
