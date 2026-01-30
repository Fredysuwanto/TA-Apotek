@extends('layouts.main')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">
                        <i class="mdi mdi-pills me-2"></i>Hasil Prediksi Penjualan Obat
                    </h4>

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif
<div class="alert alert-info">
    <strong>Keterangan:</strong>
    <ul class="mt-2 mb-0">
        <li>
            <strong>Persamaan Regresi</strong>  
            Y = penjualan, Nilai a = penjualan dasarnya tanpa mempertimbangkan harga dan curah hujan, nilai b1 = jika hasil positif artinya saat harga naik -> penjualan akan naik, jika hasil negatif artinya saat harga naik -> penjualan akan turun, b2 = jika curah hujan meningkat → penjualan ikut meningkat., jika curah hujan meningkat → penjualan menurun.
        </li>

        <li>
            <strong>MSE (Mean Squared Error)</strong>  
            Semakin kecil nilai MSE, semakin baik kualitas model.  
            MSE mengukur rata-rata kesalahan kuadrat antara nilai aktual dan nilai prediksi.
        </li>

        <li class="mt-2">
            <strong>RMSE (Root Mean Squared Error)</strong>  
            RMSE adalah akar dari MSE dan menunjukkan seberapa jauh prediksi menyimpang dari nilai aktual.  
            Semakin kecil RMSE, semakin akurat prediksi. Misal RMSE 10 dan hasil prediksi 100, maka rata-rata meleset  ±10  penjualan.
        </li>

        <li class="mt-2">
            <strong>MAPE (Mean Absolute Percentage Error)</strong>  
            MAPE mengukur rata-rata persentase error antara prediksi dan nilai aktual.  
            Semakin kecil MAPE semakin akurat model. (&lt;10% sangat baik, 10–20% baik, 20-50% layak, >50% buruk).
        </li>

        <li class="mt-2">
            <strong>R² (Koefisien Determinasi)</strong>  
            R² menunjukkan seberapa baik variabel independen (harga(b1) dan curah hujan(b2)) menjelaskan pengaruh terhadap variabel dependen (penjualan(y)).  
            Nilai mendekati 1 berarti model sangat baik dalam menjelaskan data.
        </li>
    </ul>
</div>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead class="thead-dark">
                                <tr>
                                    <th>No</th>
                                    <th>Nama Obat</th>
                                    <th>Persamaan Regresi</th>
                                    <th>Hasil Prediksi (Y)</th>
                                    <th>MSE</th>
                                    <th>RMSE</th>
                                    <th>MAPE</th>
                                    <th>R²</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($results as $index => $result)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td class="font-weight-bold">{{ $result['nama_obat'] }}</td>
                                    <td class="text-sm">
                                        <small>{{ $result['equation'] }}</small>
                                        <br>
                                        <small class="text-muted">
                                            a = {{ number_format($result['coefficients']['a'], 4) }}, 
                                            b1 = {{ number_format($result['coefficients']['b1'], 4) }}, 
                                            b2 = {{ number_format($result['coefficients']['b2'], 4) }}
                                        </small>
                                    </td>
                                    <td class="font-weight-bold text-primary">
                                        {{ number_format($result['prediksi'], 2) }}
                                            @if(!empty($result['satuan']))
                                                {{ ' ' . $result['satuan'] }}
                                            @endif
                                    </td>
                                    <td>{{ number_format($result['metrics']['mse'] ?? 0, 4) }}</td>
                                    <td>{{ number_format($result['metrics']['rmse'] ?? 0, 4) }}</td>
                                    <td>{{ number_format($result['metrics']['mape'] ?? 0, 2) }}%</td>
                                    <td>{{ number_format($result['metrics']['r2'] ?? 0, 4) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        <i class="mdi mdi-information-outline me-2"></i>
                                        Belum ada hasil prediksi. 
                                        @if(\App\Models\SalesData::count() > 0)
                                            <form action="{{ route('calculate.regression') }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-warning ml-2">
                                                    Hitung Prediksi
                                                </button>
                                            </form>
                                        @else
                                            Silakan input data terlebih dahulu.
                                        @endif
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 text-center">
                        <a href="{{ route('prediction.index') }}" class="btn btn-primary">
                            <i class="mdi mdi-arrow-left me-2"></i>Kembali ke Input Data
                        </a>
                        
                        @if(count($results) > 0)
                        <form action="{{ route('calculate.regression') }}" method="POST" class="d-inline ml-2">
                            @csrf
                            <button type="submit" class="btn btn-warning">
                                <i class="mdi mdi-calculator me-2"></i>Hitung Ulang Prediksi
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection