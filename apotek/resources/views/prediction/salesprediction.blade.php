@extends('layouts.main')
@section('content')

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Prediksi Penjualan Obat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <h1 class="text-center mb-4">
                    Dashboard Prediksi Penjualan Obat
                </h1>
                
                <!-- Alert Messages -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Fitur 1: Upload CSV dan Input Manual -->
                <div class="card mb-4">
                    <div class="card-body">
                        <!-- Upload CSV -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6>Import dari CSV:</h6>
                                <form action="{{ route('import') }}" method="POST" enctype="multipart/form-data" class="row g-3">
                                    @csrf
                                    <div class="col-md-6">
                                        <label for="file" class="form-label">Pilih File CSV</label>
                                        <input type="file" class="form-control" id="file" name="file" accept=".csv,.txt" required>
                                        <div class="form-text">
                                            Format file: CSV dengan header: tanggal,nama_obat,x1 (harga) ,x2 (curah hujan) ,y (penjualan)
                                        </div>
                                    </div>
                                    <div class="col-md-6 d-flex align-items-end">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-upload me-2"></i>Import CSV
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Input Manual -->
                        <div class="row">
                            <div class="col-12">
                                <h6>Input Manual:</h6>
                                <form action="{{ route('store.manual') }}" method="POST" class="row g-3">
                                    @csrf
                                    <div class="col-md-2">
                                        <label for="tanggal" class="form-label">Tanggal</label>
                                        <input type="date" class="form-control" id="tanggal" name="tanggal" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="nama_obat" class="form-label">Nama Obat</label>
                                        <input type="text" class="form-control" id="nama_obat" name="nama_obat" placeholder="Antasida" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="x1" class="form-label">X1 (Harga)</label>
                                        <input type="number" step="0.01" class="form-control" id="x1" name="x1" placeholder="100" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="x2" class="form-label">X2 (Curah Hujan)</label>
                                        <input type="number" step="0.01" class="form-control" id="x2" name="x2" placeholder="50" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="y" class="form-label">Y (Penjualan)</label>
                                        <input type="number" step="0.01" class="form-control" id="y" name="y" placeholder="200" required>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>Tambah Data
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Debug Info -->
                {{-- @if($salesData->count() > 0)
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Data ditemukan: {{ $salesData->count() }} records, {{ $obatList->count() }} jenis obat
                </div>
                @endif --}}
<div class="row d-flex justify-content-center">
    <div class="col-md-2 col-lg-2">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center p-5">
                <!-- Icon -->
                <div class="mb-4">
                    <i class="mdi mdi-chart-line mdi-48px text-success"></i>
                </div>
                
                <h4 class="card-title mb-3">Hasil Prediksi</h4>                
                <a href="{{ route('prediction.results') }}" class="btn btn-success btn-lg">
                    <i class="mdi mdi-chart-bar me-2"></i>Lihat Hasil
                </a>
            </div>
        </div>
    </div>
</div>
                <!-- Data Table -->
                @if($salesData->count() > 0)
                <div class="card mb-4">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-table me-2"></i>Data Penjualan ({{ $salesData->count() }} data)
                        </h5>
                        {{-- <span class="badge bg-light text-dark">
                            {{ $obatList->count() }} jenis obat
                        </span> --}}
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Tanggal</th>
                                        <th>Nama Obat</th>
                                        <th>X1 (Harga)</th>
                                        <th>X2 (Curah Hujan)</th>
                                        <th>Y (Penjualan)</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                {{-- GANTI BAGIAN INI SAJA --}}
<tbody>
    @foreach($salesData as $index => $data)
    <tr>
        <td>{{ $index + 1 }}</td>
<td>
    @if($data->tanggal)
        {{ \Carbon\Carbon::parse($data->tanggal)->format('d-m-Y') }}
    @else
        - 
    @endif
</td>
        <td>{{ $data->nama_obat }}</td>
        <td>{{ number_format($data->x1, 2) }}</td>
        <td>{{ number_format($data->x2, 1) }}</td>
        <td>{{ number_format($data->y, 0) }}</td>
        <td>
            <form action="{{ route('delete.data', $data->id) }}" method="POST" class="d-inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Hapus data ini?')">
                    <i class="fas fa-trash"></i>
                </button>
            </form>
        </td>
    </tr>
    @endforeach
</tbody>
{{-- END GANTI --}}
                            </table>
                        </div>
                        
                        <!-- Button Actions -->
                        <div class="text-center mt-3">
                            <a href="{{ route('prediction.results') }}" class="btn btn-success me-2">
                                <i class="fas fa-chart-bar me-2"></i>Lihat Hasil Prediksi
                            </a>
                            
                            @php
                                // Hitung obat yang punya cukup data (minimal 2 data)
                                $obatWithEnoughData = [];
                                foreach ($obatList as $obat) {
                                    $count = $salesData->where('nama_obat', $obat)->count();
                                    if ($count >= 2) {
                                        $obatWithEnoughData[$obat] = $count;
                                    }
                                }
                            @endphp
                            
                            @if(count($obatWithEnoughData) > 0)
                            <form action="{{ route('calculate.regression') }}" method="POST" class="d-inline me-2">
                                @csrf
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-calculator me-2"></i>Hitung Ulang Prediksi
                                </button>
                            </form>
                            @endif
                            
                            <form action="{{ route('clear.data') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Hapus semua data?')">
                                    <i class="fas fa-trash me-2"></i>Hapus Semua Data
                                </button>
                            </form>
                        </div>

                        <!-- Info Obat yang Cukup Data -->
                        @if(count($obatWithEnoughData) > 0)
                        <div class="mt-3">
                            <div class="alert alert-success">
                                <strong>Obat yang siap untuk prediksi:</strong><br>
                                @foreach($obatWithEnoughData as $obat => $count)
                                <span class="badge bg-primary me-2 mb-1">{{ $obat }} ({{ $count }} data)</span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @else
                <div class="alert alert-warning text-center">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Belum ada data. Silakan input data manual atau import CSV.
                </div>
                @endif
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
@endsection