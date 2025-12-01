<?php

namespace App\Http\Controllers;

use Maatwebsite\Excel\Facades\Excel;
use App\Imports\SalesDataImport;
use Illuminate\Http\Request;
use App\Models\SalesData;
use App\Models\RegressionResult;
use App\Services\RegressionService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SalesPredictionController extends Controller
{
    public function index()
    {
        $salesData = SalesData::orderBy('tanggal', 'desc')->get();
        $obatList = SalesData::distinct()->pluck('nama_obat');
        
        return view('prediction.salesprediction', compact('salesData', 'obatList'));
    }

public function import(Request $request)
{
    $request->validate([
        'file' => 'required|mimes:csv,txt'
    ]);

    ini_set('max_execution_time', 0); // biar gak timeout
    Excel::import(new SalesDataImport, $request->file('file'));

    return back()->with('success', 'Data berhasil diimport!');
}
    private function sortPredictions(&$normalResults, &$fallbackResults)
    {
        usort($normalResults, function($a, $b) {
            return $b['prediksi'] <=> $a['prediksi'];
        });
        usort($fallbackResults, function($a, $b) {
            return $b['prediksi'] <=> $a['prediksi'];
        });
    }
    public function storeManual(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'nama_obat' => 'required',
            'x1' => 'required|numeric',
            'x2' => 'required|numeric', 
            'y' => 'required|numeric'
        ]);

        SalesData::create([
            'tanggal' => $request->tanggal,
            'nama_obat' => $request->nama_obat,
            'x1' => $request->x1,
            'x2' => $request->x2,
            'y' => $request->y
        ]);
        
        // AUTO CALCULATE PREDICTIONS setelah tambah data
        $regressionService = new RegressionService();
        $regressionService->calculateAndSaveAllPredictions();
        
        return redirect()->back()->with('success', 'Data berhasil ditambahkan dan prediksi dihitung!');
    }

    public function predictionResults()
{
    try {
        Log::info('=== PREDICTION RESULTS START ===');
        
        $regressionService = new RegressionService();
        $regressionResults = RegressionResult::all();
        Log::info('Regression results count: ' . $regressionResults->count());
        
        $normalResults = [];
        $fallbackResults = [];

        foreach ($regressionResults as $result) {
            try {
                $predictedValue = $regressionService->getLatestPrediction($result->nama_obat);
                
                if ($predictedValue !== null) {
                    $resultData = [
                        'nama_obat' => $result->nama_obat,
                        'prediksi' => $predictedValue,
                        'equation' => $result->equation,
                        'coefficients' => $result->coefficients,
                        'metrics' => $result->metrics
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
        
        // ✅ FIX: Inisialisasi $results di sini
        $this->sortPredictions($normalResults, $fallbackResults);
        $results = array_merge($normalResults, $fallbackResults);

        // ✅ FIX: Cek jika $results kosong (bukan undefined)
        if (empty($results)) {
            Log::info('No predictions found, calculating...');
            $regressionService->calculateAndSaveAllPredictions();
            
            // Reset dan ambil ulang data
            $normalResults = [];
            $fallbackResults = [];
            $regressionResults = RegressionResult::all();
            
            foreach ($regressionResults as $result) {
                $predictedValue = $regressionService->getLatestPrediction($result->nama_obat);
                if ($predictedValue !== null) {
                    $resultData = [
                        'nama_obat' => $result->nama_obat,
                        'prediksi' => $predictedValue,
                        'equation' => $result->equation,
                        'coefficients' => $result->coefficients,
                        'metrics' => $result->metrics
                    ];
                    
                    if (strpos($result->equation, 'Fallback - Average') !== false) {
                        $fallbackResults[] = $resultData;
                    } else {
                        $normalResults[] = $resultData;
                    }
                }
            }
            
            // ✅ SORT dan GABUNGKAN setelah recalculate
            $this->sortPredictions($normalResults, $fallbackResults);
            $results = array_merge($normalResults, $fallbackResults);
        }
        
        Log::info('Final results count: ' . count($results));
        Log::info('Normal results: ' . count($normalResults));
        Log::info('Fallback results: ' . count($fallbackResults));
        Log::info('=== PREDICTION RESULTS END ===');
        
        return view('prediction.results', compact('results'));
        
    } catch (\Exception $e) {
        Log::error('Error in predictionResults: ' . $e->getMessage());
        return view('prediction.results', ['results' => []])
            ->with('error', 'Error: ' . $e->getMessage());
    }
}

    public function clearData()
    {
        SalesData::truncate();
        RegressionResult::truncate();
        return redirect()->back()->with('success', 'Data berhasil dihapus!');
    }

    public function deleteData($id)
    {
        $data = SalesData::find($id);
        $obat = $data->nama_obat;
        $data->delete();
        
        // Recalculate predictions after delete
        $regressionService = new RegressionService();
        $regressionService->calculateAndSaveAllPredictions();
        
        return redirect()->back()->with('success', 'Data berhasil dihapus!');
    }

    public function downloadTemplate()
    {
        $template = "tanggal,nama_obat,x1,x2,y\n";
        $template .= "01/01/2024,Antasida,100.5,25.5,20\n";
        $template .= "02/01/2024,Antasida,105.0,30.2,25\n";
        $template .= "03/01/2024,Amoxilin,85.0,22.1,15\n";
        $template .= "# FORMAT: DD/MM/YYYY atau DD-MM-YYYY\n";
        $template .= "# X1 (harga): 100.50 (2 decimal)\n";
        $template .= "# X2 (curah hujan): 25.5 (1 decimal)\n";
        $template .= "# Y (penjualan): 20 (tanpa decimal)\n";
        
        return response($template)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="template_obat.csv"');
    }

    public function calculateRegression()
    {
        try {
            $regressionService = new RegressionService();
            $results = $regressionService->calculateAndSaveAllPredictions();
            
            return redirect()->route('prediction.results')->with('success', 'Prediksi berhasil dihitung ulang!');
            
        } catch (\Exception $e) {
            return redirect()->route('prediction.index')->with('error', 'Error menghitung prediksi: ' . $e->getMessage());
        }
    }
    
}