<?php
use App\Http\Controllers\SalesPredictionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SalesPredictionController::class, 'index'])->name('home');
Route::get('/prediction', [SalesPredictionController::class, 'index'])->name('prediction.index');
Route::post('/import', [SalesPredictionController::class, 'import'])->name('import');
Route::post('/manual-input', [SalesPredictionController::class, 'storeManual'])->name('store.manual');
Route::get('/prediction-results', [SalesPredictionController::class, 'predictionResults'])->name('prediction.results');
Route::post('/calculate-regression', [SalesPredictionController::class, 'calculateRegression'])->name('calculate.regression'); // INI YANG DITAMBAH
Route::post('/clear-data', [SalesPredictionController::class, 'clearData'])->name('clear.data');
Route::delete('/delete-data/{id}', [SalesPredictionController::class, 'deleteData'])->name('delete.data');
Route::get('/download-template', [SalesPredictionController::class, 'downloadTemplate'])->name('download.template');
Route::post('/import-sales', [SalesPredictionController::class, 'importSales'])->name('import.sales');
Route::post('/import-rainfall', [SalesPredictionController::class, 'importRainfall'])->name('import.rainfall');
Route::get('/', function () {
    return view('layouts.main');
});

// DEBUG ROUTES
Route::get('/debug-system', function() {
    echo "<h1>Debug System</h1>";
    
    // 1. Cek sales data
    $salesData = \App\Models\SalesData::all();
    echo "<h3>Sales Data Count: " . $salesData->count() . "</h3>";
    
    if ($salesData->count() > 0) {
        foreach ($salesData as $data) {
            echo "ID: {$data->id} | Obat: {$data->nama_obat} | X1: {$data->x1} | X2: {$data->x2} | Y: {$data->y}<br>";
        }
        
        // 2. Cek per obat
        $obatList = \App\Models\SalesData::distinct()->pluck('nama_obat');
        echo "<h3>Obat List:</h3>";
        foreach ($obatList as $obat) {
            $count = \App\Models\SalesData::where('nama_obat', $obat)->count();
            echo "{$obat}: {$count} data<br>";
        }
    }
    
    // 3. Cek regression results
    $regressionResults = \App\Models\RegressionResult::all();
    echo "<h3>Regression Results Count: " . $regressionResults->count() . "</h3>";
});

Route::get('/calculate-now', function() {
    try {
        $service = new \App\Services\RegressionService();
        $results = $service->calculateAndSaveAllPredictions();
        
        echo "<h1>Calculation Results:</h1>";
        echo "<pre>";
        print_r($results);
        echo "</pre>";
        
        // Cek stored results
        $stored = \App\Models\RegressionResult::all();
        echo "<h3>Stored in Database:</h3>";
        foreach ($stored as $item) {
            echo "Obat: {$item->nama_obat} | Equation: {$item->equation}<br>";
        }
        
    } catch (\Exception $e) {
        echo "<h3>Error:</h3>";
        echo $e->getMessage();
        echo "<h3>Trace:</h3>";
        echo $e->getTraceAsString();
    }
});

Route::get('/create-sample-and-calculate', function() {
    // Hapus data lama
    \App\Models\SalesData::truncate();
    \App\Models\RegressionResult::truncate();
    
    // Buat sample data
    $sampleData = [
        ['tanggal' => '2024-01-01', 'nama_obat' => 'Antasida', 'x1' => 100, 'x2' => 50, 'y' => 200],
        ['tanggal' => '2024-01-02', 'nama_obat' => 'Antasida', 'x1' => 120, 'x2' => 60, 'y' => 250],
        ['tanggal' => '2024-01-03', 'nama_obat' => 'Antasida', 'x1' => 90, 'x2' => 45, 'y' => 180],
        ['tanggal' => '2024-01-01', 'nama_obat' => 'Amoxilin', 'x1' => 80, 'x2' => 40, 'y' => 150],
        ['tanggal' => '2024-01-02', 'nama_obat' => 'Amoxilin', 'x1' => 95, 'x2' => 48, 'y' => 170],
    ];
    
    foreach ($sampleData as $data) {
        \App\Models\SalesData::create($data);
    }
    
    // Hitung regresi
    $service = new \App\Services\RegressionService();
    $results = $service->calculateAndSaveAllPredictions();
    
    return redirect('/prediction-results')->with('success', 'Sample data created and calculated!');
});
