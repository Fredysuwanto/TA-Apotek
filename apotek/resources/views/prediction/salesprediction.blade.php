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
                
                @if(session('import_errors') && count(session('import_errors')) > 0)
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <strong>Beberapa baris tidak dapat diimport:</strong>
                    <ul class="mb-0">
                        @foreach(session('import_errors') as $err)
                            <li>Baris {{ $err['row'] }}: {{ $err['message'] }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

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
                                <h6 class="mb-3">Import dari CSV:</h6>
                                
                                <!-- Form Import Penjualan -->
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h6 class="card-subtitle mb-3 text-muted">Import Data Penjualan</h6>
                                        <form action="{{ route('import.sales') }}" method="POST" enctype="multipart/form-data" class="row g-3 align-items-center">
                                            @csrf
                                            <div class="col-md-6">
                                                <label for="file_sales" class="form-label">File Penjualan (CSV)</label>
                                                <input type="file" class="form-control" id="file_sales" name="file_sales" accept=".csv,.txt" required>
                                                <div class="form-text">
                                                    Format header: <code>vc_tgl_nota, vc_n_obat, vc_n_satuan, dc_rupiah, dc_qty</code>
                                                </div>
                                            </div>
                                            <div class="col-md-6 d-flex align-items-end">
                                                <button type="submit" class="btn btn-success w-100">
                                                    <i class="fas fa-upload me-2"></i>Import Penjualan
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Form Import Curah Hujan -->
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-subtitle mb-3 text-muted">Import Data Curah Hujan</h6>
                                        <form action="{{ route('import.rainfall') }}" method="POST" enctype="multipart/form-data" class="row g-3 align-items-center">
                                            @csrf
                                            <div class="col-md-6">
                                                <label for="file_rainfall" class="form-label">File Curah Hujan (CSV)</label>
                                                <input type="file" class="form-control" id="file_rainfall" name="file_rainfall" accept=".csv,.txt" required>
                                                <div class="form-text">
                                                    Format header: <code>tanggal, curahhujan</code>
                                                </div>
                                            </div>
                                            <div class="col-md-6 d-flex align-items-end">
                                                <button type="submit" class="btn btn-primary w-100">
                                                    <i class="fas fa-cloud-rain me-2"></i>Import Curah Hujan
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Input Manual -->
                        <div class="row">
                            <div class="col-12">
                                <h6 class="mb-3">Input Manual:</h6>
                                <div class="card">
                                    <div class="card-body">
                                        <form action="{{ route('store.manual') }}" method="POST" class="row g-3">
                                            @csrf
                                            <div class="col-md-2">
                                                <label for="tanggal" class="form-label">Tanggal</label>
                                                <input type="date" class="form-control" id="tanggal" name="tanggal" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label for="nama_obat" class="form-label">Nama Obat</label>
                                                <input type="text" class="form-control" id="nama_obat" name="nama_obat" placeholder="Masukkan Nama Obat" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label for="satuan" class="form-label">Satuan</label>
                                                <input type="text" class="form-control" id="satuan" name="satuan" placeholder="Masukkan Satuan Obat">
                                            </div>
                                            <div class="col-md-2">
                                                <label for="x1" class="form-label">X1 (Harga)</label>
                                                <input type="number" step="0.01" class="form-control" id="x1" name="x1" placeholder="Masukkan Harga Obat" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label for="x2" class="form-label">X2 (Curah Hujan)</label>
                                                <input type="number" step="0.01" class="form-control" id="x2" name="x2" placeholder="Masukkan Curah Hujan" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label for="y" class="form-label">Y (Penjualan)</label>
                                                <input type="number" step="0.01" class="form-control" id="y" name="y" placeholder="Masukkan Jumlah Penjualan" required>
                                            </div>
                                            <div class="col-md-12 mt-2">
                                                <button type="submit" class="btn btn-primary w-100">
                                                    <i class="fas fa-plus me-2"></i>Tambah Data
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Table -->
                @if($salesData->count() > 0)
                <div class="card mb-4">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-table me-2"></i>Data Penjualan dan Curah Hujan
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Tanggal</th>
                                        <th>Nama Obat</th>
                                        <th>Satuan</th>
                                        <th>X1 (Harga)</th>
                                        <th>X2 (Curah Hujan)</th>
                                        <th>Y (Penjualan)</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
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
                                        <td>{{ $data->satuan ?? '-' }}</td>
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
                            </table>
                        </div>
                        
                        <!-- Button Actions -->
                        <div class="text-center mt-3">
                            <a href="{{ route('prediction.results') }}" class="btn btn-success me-2">
                                <i class="fas fa-chart-bar me-2"></i>Lihat Hasil Prediksi
                            </a>
                            
                            @php
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