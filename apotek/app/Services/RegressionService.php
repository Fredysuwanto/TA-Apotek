<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\SalesData;
use App\Models\RegressionResult;
use Illuminate\Support\Facades\Log;

class RegressionService
{
    /**
     * Hitung dan simpan semua prediksi dengan split 80/20
     */
    public function calculateAndSaveAllPredictions()
    {
        try {
            set_time_limit(300);
            Log::info('=== START CALCULATE ALL PREDICTIONS ===');
            
            RegressionResult::truncate();
            $obatList = SalesData::distinct()->pluck('nama_obat');
            Log::info('Obat list found: ' . $obatList->count());
            
            $results = [];
            
            foreach ($obatList as $obat) {
                try {
                    Log::info("Processing obat: {$obat}");
                    
                    $data = SalesData::where('nama_obat', $obat)->get();
                    $dataCount = $data->count();
                    Log::info("Data count for {$obat}: {$dataCount}");
                    
                    // ✅ MINIMAL 5 DATA UNTUK SPLIT 80/20 YANG MEANINGFUL
                    if ($dataCount < 11) {
                        Log::warning("Not enough data for {$obat} ({$dataCount}), need at least 5. Skipping...");
                        continue;
                    }
                    
                    $regression = $this->calculateMultipleRegression($data, $obat);
                    
                    if (!$this->isValidRegressionResult($regression)) {
                        Log::warning("Regression result invalid for {$obat}, skipping save.");
                        continue;
                    }
                    
                    // Simpan ke database
                    RegressionResult::create([
                        'nama_obat' => $obat,
                        'equation' => $regression['equation'],
                        'coefficients' => $regression['coefficients'],
                        'calculations' => $regression['calculations'],
                        'predictions' => $regression['predictions'],
                        'metrics' => $regression['metrics']
                    ]);
                    
                    $results[$obat] = $regression['latest_prediction'];
                    Log::info("✅ Successfully calculated regression for {$obat}: " . $regression['latest_prediction']);
                    
                } catch (\Exception $e) {
                    Log::error("Error processing {$obat}: " . $e->getMessage());
                    continue;
                }
            }
            
            Log::info('=== END CALCULATE ALL PREDICTIONS ===');
            return $results;
            
        } catch (\Exception $e) {
            Log::error('Error in calculateAndSaveAllPredictions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * REGRESI LINEAR BERGANDA DENGAN SPLIT 80/20 KONSISTEN
     */
    private function calculateMultipleRegression($data, $obatName)
    {
        try {
            $n = $data->count();
            Log::info("Calculating regression for {$obatName} with {$n} data points");
            
            // ✅ SORT BY DATE UNTUK TIME-SERIES CONSISTENCY
            $dataSorted = $data->sortBy(function($item) {
                try {
                    return Carbon::createFromFormat('d/m/Y', $item->tanggal);
                } catch (\Exception $e) {
                    return Carbon::parse($item->tanggal);
                }
            })->values();

            // ✅ SPLIT 80/20 - PASTIKAN MINIMAL 1 DATA TESTING
            $splitIndex = max(1, floor($n * 0.8)); // Minimal 1 data testing
            $trainingData = $dataSorted->slice(0, $splitIndex);
            $testingData = $dataSorted->slice($splitIndex);

            // ✅ LOG SPLIT INFORMATION
            Log::info("=== DATA SPLIT FOR {$obatName} ===");
            Log::info("Total data: {$n}");
            Log::info("Training data: {$trainingData->count()} records (" . round(($trainingData->count()/$n)*100, 1) . "%)");
            Log::info("Testing data: {$testingData->count()} records (" . round(($testingData->count()/$n)*100, 1) . "%)");

            // ✅ LOG TRAINING DATA
            Log::info("=== TRAINING DATA ===");
            foreach ($trainingData as $i => $item) {
                Log::info("Training {$i}: {$item->tanggal}, X1={$item->x1}, X2={$item->x2}, Y={$item->y}");
            }

            // ✅ HITUNG COEFFICIENTS DARI TRAINING DATA
            $coefficients = $this->calculateCoefficientsWithCramer($trainingData, $obatName);
            
            if (!$coefficients) {
                Log::warning("Cannot calculate coefficients for {$obatName}, using fallback");
                return $this->calculateFallbackPrediction($data, $obatName);
            }

            list($a, $b1, $b2) = $coefficients;
            
            Log::info("✅ Coefficients from {$trainingData->count()} training data: a={$a}, b1={$b1}, b2={$b2}");

            // ✅ HITUNG METRIK DENGAN TESTING DATA
            $metrics = $this->calculateAllMetrics($testingData, $a, $b1, $b2);

            // ✅ PREDIKSI UNTUK DATA TERBARU (dari seluruh dataset)
            $latestData = $dataSorted->last();
            $latestPrediction = $a + ($b1 * floatval($latestData->x1)) + ($b2 * floatval($latestData->x2));
Log::info("=== LATEST PREDICTION INPUT FOR {$obatName} ===");
Log::info("X1 (Harga): {$latestData->x1}, X2 (CurahHujan): {$latestData->x2}");
Log::info("Using coefficients: a={$a}, b1={$b1}, b2={$b2}");
Log::info("Result Y: {$latestPrediction}");

            // Handle negative prediction
            if ($latestPrediction < 0) {
                Log::warning("Negative prediction for {$obatName}: {$latestPrediction}, setting to 0");
                $latestPrediction = 0;
            }

            // Hitung prediksi untuk training data (untuk analysis)
            $predictions = [];
            foreach ($trainingData as $item) {
                $predictedY = $a + ($b1 * floatval($item->x1)) + ($b2 * floatval($item->x2));
                $predictions[] = [
                    'actual' => floatval($item->y),
                    'predicted' => $predictedY,
                    'residual' => floatval($item->y) - $predictedY,
                    'data_type' => 'training'
                ];
            }
            
            // Tambahkan testing data predictions
            foreach ($testingData as $item) {
                $predictedY = $a + ($b1 * floatval($item->x1)) + ($b2 * floatval($item->x2));
                    Log::info("TESTING DATA -> Y_actual={$item->y}, Y_predicted={$predictedY}, X1={$item->x1}, X2={$item->x2}");
                $predictions[] = [
                    'actual' => floatval($item->y),
                    'predicted' => $predictedY,
                    'residual' => floatval($item->y) - $predictedY,
                    'data_type' => 'testing'
                ];
            }
            Log::info("TEST PREDICTION -> {$obatName}: X1={$item->x1}, X2={$item->x2}, Actual={$item->y}, Predicted={$predictedY}");

            return [
                'latest_prediction' => $latestPrediction,
                'equation' => sprintf("Y = %.4f + %.4fX1 + %.4fX2", $a, $b1, $b2),
                'coefficients' => [
                    'a' => $a,
                    'b1' => $b1,
                    'b2' => $b2
                ],
                'calculations' => [
                    'training_count' => $trainingData->count(),
                    'testing_count' => $testingData->count(),
                    'total_count' => $n,
                    'split_ratio' => round(($trainingData->count()/$n)*100, 1) . '/' . round(($testingData->count()/$n)*100, 1),
                    'method' => 'Cramer'
                ],
                'predictions' => $predictions,
                'metrics' => $metrics
            ];
            
        } catch (\Exception $e) {
            Log::error("Error in calculateMultipleRegression for {$obatName}: " . $e->getMessage());
            return $this->calculateFallbackPrediction($data, $obatName);
        }
    }

    /**
     * METODE CRAMER - TETAP SAMA
     */
    private function calculateCoefficientsWithCramer($trainingData, $obatName)
    {
        try {
            $sumX1 = $sumX2 = $sumY = 0;
            $sumX1X1 = $sumX2X2 = $sumX1X2 = 0;
            $sumX1Y = $sumX2Y = 0;
            
            $n = $trainingData->count();

            Log::info("=== CALCULATING COEFFICIENTS WITH CRAMER ===");
            Log::info("Training data count: {$n}");

            foreach ($trainingData as $item) {
                $x1 = floatval($item->x1);
                $x2 = floatval($item->x2);
                $y = floatval($item->y);
                
                $sumX1 += $x1;
                $sumX2 += $x2;
                $sumY += $y;
                $sumX1X1 += $x1 * $x1;
                $sumX2X2 += $x2 * $x2;
                $sumX1X2 += $x1 * $x2;
                $sumX1Y += $x1 * $y;
                $sumX2Y += $x2 * $y;
            }

            // Matriks untuk persamaan normal
            $matrix = [
                [$n,      $sumX1,    $sumX2],
                [$sumX1,  $sumX1X1,  $sumX1X2],
                [$sumX2,  $sumX1X2,  $sumX2X2]
            ];
            
            $constants = [$sumY, $sumX1Y, $sumX2Y];

            // Metode Cramer
            $detA = $this->calculateDeterminant($matrix);
            
            if (abs($detA) < 1e-10) {
                Log::warning("Matrix is singular, determinant: " . $detA);
                return null;
            }

            // Hitung b0, b1, b2 dengan Cramer's Rule
            $matrixA0 = [
                [$constants[0], $matrix[0][1], $matrix[0][2]],
                [$constants[1], $matrix[1][1], $matrix[1][2]],
                [$constants[2], $matrix[2][1], $matrix[2][2]]
            ];
            $detA0 = $this->calculateDeterminant($matrixA0);
            $b0 = $detA0 / $detA;

            $matrixA1 = [
                [$matrix[0][0], $constants[0], $matrix[0][2]],
                [$matrix[1][0], $constants[1], $matrix[1][2]],
                [$matrix[2][0], $constants[2], $matrix[2][2]]
            ];
            $detA1 = $this->calculateDeterminant($matrixA1);
            $b1 = $detA1 / $detA;

            $matrixA2 = [
                [$matrix[0][0], $matrix[0][1], $constants[0]],
                [$matrix[1][0], $matrix[1][1], $constants[1]],
                [$matrix[2][0], $matrix[2][1], $constants[2]]
            ];
            $detA2 = $this->calculateDeterminant($matrixA2);
            $b2 = $detA2 / $detA;

            $solution = [$b0, $b1, $b2];
            
            Log::info("✅ Cramer Solution: a={$b0}, b1={$b1}, b2={$b2}");
            
            return $solution;
            
        } catch (\Exception $e) {
            Log::error("Error in Cramer method for {$obatName}: " . $e->getMessage());
            return null;
        }
    }
    /**
     * HITUNG DETERMINAN MATRIX 3x3
     */
    private function calculateDeterminant($matrix)
    {
        // Rumus: a(ei − fh) − b(di − fg) + c(dh − eg)
        $a = $matrix[0][0]; $b = $matrix[0][1]; $c = $matrix[0][2];
        $d = $matrix[1][0]; $e = $matrix[1][1]; $f = $matrix[1][2];
        $g = $matrix[2][0]; $h = $matrix[2][1]; $i = $matrix[2][2];
        
        return $a * ($e * $i - $f * $h) 
             - $b * ($d * $i - $f * $g) 
             + $c * ($d * $h - $e * $g);
    }

    /**
     * HITUNG SEMUA METRIK EVALUASI
     */
    private function calculateAllMetrics($testingData, $a, $b1, $b2)
    {
        $n = count($testingData);
        
        if ($n < 2) {
            Log::warning("No testing data available for metrics calculation");
            return [
                'mse' => null, 'rmse' => null, 'mape' => null, 'r2' => null
            ];
        }

        $errors = [];
        $actualList = [];
        $predList = [];

        // Hitung prediksi untuk testing data
        foreach ($testingData as $item) {
            $actual = floatval($item->y);
            $pred = $a + ($b1 * floatval($item->x1)) + ($b2 * floatval($item->x2));
            
            $errors[] = $actual - $pred;
            $actualList[] = $actual;
            $predList[] = $pred;
        }

        // ✅ MSE (Mean Squared Error)
        $mse = $this->calculateMSE($errors, $n);
        
        // ✅ RMSE (Root Mean Squared Error)
        $rmse = $this->calculateRMSE($mse);
        
        // ✅ MAPE (Mean Absolute Percentage Error)
        $mape = $this->calculateMAPE($actualList, $predList, $n);
        
        // ✅ R² (R-squared)
        $r2 = $this->calculateR2($actualList, $predList, $n);

        Log::info("METRICS - MSE: {$mse}, RMSE: {$rmse}, MAPE: {$mape}%, R²: {$r2}");

        return [
            'mse' => $mse,
            'rmse' => $rmse,
            'mape' => $mape,
            'r2' => $r2
        ];
    }

    /**
     * MSE = 1/n Σ(y_i - ŷ_i)²
     */
    private function calculateMSE($errors, $n)
    {
        $sumSquaredErrors = 0;
        foreach ($errors as $error) {
            $sumSquaredErrors += $error * $error;
        }
        return $sumSquaredErrors / $n;
    }

    /**
     * RMSE = √MSE
     */
    private function calculateRMSE($mse)
    {
        return sqrt($mse);
    }

    /**
     * MAPE = (1/n) Σ |(y_i - ŷ_i) / y_i| * 100%
     */
    private function calculateMAPE($actualList, $predList, $n)
    {
        $mapeSum = 0;
        $validCount = 0;
        
        for ($i = 0; $i < $n; $i++) {
            if ($actualList[$i] != 0) {
                $mapeSum += abs(($actualList[$i] - $predList[$i]) / $actualList[$i]);
                $validCount++;
            }
        }
        
        return $validCount > 0 ? ($mapeSum / $validCount) * 100 : 0;
    }

    /**
     * R² = 1 - (SS_res / SS_tot)
     */
    private function calculateR2($actualList, $predList, $n)
{
    if ($n < 2) {
        Log::warning("R² calculation: Not enough data points ({$n}), need at least 2");
        return null;
    }

    $meanActual = array_sum($actualList) / $n;
    $ssRes = 0;
    $ssTot = 0;

    for ($i = 0; $i < $n; $i++) {
        $ssRes += ($actualList[$i] - $predList[$i]) ** 2;
        $ssTot += ($actualList[$i] - $meanActual) ** 2;
    }

    // Debug detailed information
    Log::info("R² DEBUG - Actual: " . json_encode($actualList));
    Log::info("R² DEBUG - Predicted: " . json_encode($predList));
    Log::info("R² DEBUG - Mean: {$meanActual}, SS_Res: {$ssRes}, SS_Tot: {$ssTot}");

    if ($ssTot == 0) {
        if ($ssRes == 0) {
            Log::info("R² calculation: Perfect fit - all predictions match actual values");
            return 1; // Perfect fit
        } else {
            Log::warning("R² calculation: No variance in actual values (SS_Tot=0) but predictions vary");
            return null; // Cannot calculate R²
        }
    }

    $r2 = 1 - ($ssRes / $ssTot);
    
    Log::info("R² Raw: {$r2}");

    return $r2;
}

    /**
     * FALLBACK PREDICTION (jika regresi gagal)
     */
    private function calculateFallbackPrediction($data, $obatName)
    {
        Log::info("Using fallback prediction for {$obatName}");
        
        $totalY = 0;
        foreach ($data as $item) {
            $totalY += $item->y;
        }
        $averageY = $totalY / $data->count();
        
        return [
            'latest_prediction' => $averageY,
            'equation' => "Y = {$averageY} (Fallback - Average)",
            'coefficients' => ['a' => $averageY, 'b1' => 0, 'b2' => 0],
            'calculations' => ['fallback' => true, 'average' => $averageY],
            'predictions' => [],
            'metrics' => ['mse' => null, 'rmse' => null, 'mape' => null, 'r2' => null]
        ];
    }

    /**
     * VALIDASI HASIL REGRESI
     */
    private function isValidRegressionResult($regression)
    {
        if (!$regression || !is_array($regression)) {
            return false;
        }
        
        $requiredKeys = ['latest_prediction', 'coefficients', 'equation'];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $regression)) {
                Log::warning("Missing required key: {$key}");
                return false;
            }
        }
        
        // Validasi prediksi numeric
        if (!is_numeric($regression['latest_prediction']) || !is_finite($regression['latest_prediction'])) {
            Log::warning("Invalid prediction value: " . $regression['latest_prediction']);
            return false;
        }
        
        // Validasi coefficients
        $coeff = $regression['coefficients'];
        if (!is_array($coeff) || 
            !isset($coeff['a']) || !isset($coeff['b1']) || !isset($coeff['b2'])) {
            Log::warning("Invalid coefficients structure");
            return false;
        }
        
        return true;
    }

    /**
     * GET LATEST PREDICTION UNTUK CONTROLLER
     */
    public function getLatestPrediction($namaObat)
    {
        try {
            $regressionResult = RegressionResult::where('nama_obat', $namaObat)->first();
            if (!$regressionResult) {
                return null;
            }
            
            $latestData = SalesData::where('nama_obat', $namaObat)
                                 ->orderBy('tanggal', 'desc')
                                 ->first();
            
            if (!$latestData) {
                return null;
            }
            
            $coefficients = $regressionResult->coefficients;
            $prediction = $coefficients['a'] + 
                         ($coefficients['b1'] * $latestData->x1) + 
                         ($coefficients['b2'] * $latestData->x2);
                         
            if ($prediction < 0) {
                Log::warning("Negative prediction for {$namaObat}: {$prediction}, setting to 0");
                $prediction = 0;
            }
            
            return $prediction;
            
        } catch (\Exception $e) {
            Log::error("Error getting latest prediction for {$namaObat}: " . $e->getMessage());
            return null;
        }
    }
}