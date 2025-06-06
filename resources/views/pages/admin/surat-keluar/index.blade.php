@extends('layouts.admin')

@section('title')
    Surat
@endsection

@section('container')
    <main>
        <header class="page-header page-header-dark bg-gradient-primary-to-secondary pb-10">
            <div class="container-xl px-4">
                <div class="page-header-content pt-4">
                    <div class="row align-items-center justify-content-between">
                        <div class="col-auto mt-4">
                            <h1 class="page-header-title">
                                <div class="page-header-icon">
                                    <i data-feather="file-text"></i>
                                </div>
                                Data Surat
                            </h1>
                            <div class="page-header-subtitle">List Data Surat</div>
                        </div>
                    </div>
                    <nav class="mt-4 rounded" aria-label="breadcrumb">
                        <ol class="breadcrumb px-3 py-2 rounded mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin-dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Surat</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </header>

        <!-- Main page content-->
        <div class="container-xl px-4 mt-n10">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card card-header-actions mb-4">
                        <div class="card-header">
                            List Surat
                            @if (Session('user')['role'] == 'admin')
                                <a class="btn btn-sm btn-primary" href="{{ url('admin/surat-keluar/create') }}">
                                    Tambah Surat
                                </a>
                            @elseif (Session('user')['role'] == 'staff administrasi')
                                <a class="btn btn-sm btn-primary" href="{{ url('staff/surat-keluar/create') }}">
                                    Tambah Surat
                                </a>
                            @else
                            @endif
                        </div>
                        <div class="card-body">
                            {{-- Alert --}}
                            @if (session()->has('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    {{ session('success') }}
                                    <button class="btn-close" type="button" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                </div>
                            @endif
                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                    <button class="btn-close" type="button" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                </div>
                            @endif
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="filter_jenis_surat" class="form-label">Filter Jenis Surat</label>
                                    <select id="filter_jenis_surat" class="form-select">
                                        <option value="">-- Semua Jenis Surat --</option>
                                        @foreach ($jenisSurat as $jenis)
                                            <option value="{{ $jenis->id }}">{{ $jenis->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            {{-- Tabel Surat --}}
                            <table class="table table-striped table-hover table-sm" id="crudTable">
                                <thead>
                                    <tr>
                                        <th width="10">No.</th>
                                        <th>No Surat</th>
                                        <th>Nama Surat</th>
                                        <th>Tanggal Surat</th>
                                        <th>Jenis Surat</th>

                                        <th width="15%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('addon-script')
    <script>
        var datatable = $('#crudTable').DataTable({
            processing: true,
            serverSide: true,
            ordering: true,
            ajax: {
                url: '{!! url()->current() !!}',
                data: function(d) {
                    d.jenis_surat_id = $('#filter_jenis_surat').val();
                }
            },
            columns: [{
                    data: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'nomor_surat',
                    name: 'nomor_surat'
                },
                {
                    data: 'nama_surat',
                    name: 'nama_surat'
                },
                {
                    data: 'tanggal_surat',
                    name: 'tanggal_surat',
                    render: function(data, type, row) {
                        if (!data) return '';
                        var date = new Date(data);
                        var day = date.getDate();
                        var months = [
                            "Januari", "Februari", "Maret", "April", "Mei", "Juni",
                            "Juli", "Agustus", "September", "Oktober", "November", "Desember"
                        ];
                        var monthName = months[date.getMonth()];
                        var year = date.getFullYear();
                        return day + ' ' + monthName + ' ' + year;
                    }
                },
                {
                    data: 'jenis_surat',
                    name: 'jenis_surat'
                },

                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    width: '15%'
                },
            ]
        });
        // Trigger filter
        $('#filter_jenis_surat').change(function() {
            datatable.ajax.reload();
        });
    </script>
@endpush
