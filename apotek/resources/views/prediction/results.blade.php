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