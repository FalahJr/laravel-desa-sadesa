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
                                Data Surat Arsip
                            </h1>
                            <div class="page-header-subtitle">List Data Surat Arsip</div>
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
                        {{-- <div class="card-header">
                            List Surat
                            <a class="btn btn-sm btn-primary" href="{{ route('surat-keluar.create') }}">
                                Tambah Surat
                            </a>
                        </div> --}}
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
                            <!-- Filter -->
                            <div class="mb-3 row">
                                <div class="col-md-3">
                                    <label for="filter_tipe" class="form-label">Tipe Surat</label>
                                    <select class="form-select" id="filter_tipe">
                                        <option value="">Semua</option>
                                        <option value="masuk">Masuk</option>
                                        <option value="keluar">Keluar</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="filter_jenis" class="form-label">Jenis Surat</label>
                                    <select class="form-select" id="filter_jenis">
                                        <option value="">Semua</option>
                                        @foreach ($jenisSurat as $item)
                                            <option value="{{ $item->id }}" data-tipe="{{ $item->tipe }}">
                                                {{ $item->nama }}</option>
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
                                        <th>Tipe Surat</th>
                                        <th>Jenis Surat</th>

                                        <th>Status</th>
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
        function reloadDatatable() {
            $('#crudTable').DataTable().ajax.reload();
        }

        const datatable = $('#crudTable').DataTable({
            processing: true,
            serverSide: true,
            ordering: true,
            ajax: {
                url: '{!! url()->current() !!}',
                data: function(d) {
                    d.tipe_surat = $('#filter_tipe').val();
                    d.jenis_surat_id = $('#filter_jenis').val();
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
                    render: function(data) {
                        if (!data) return '';
                        const date = new Date(data);
                        const months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli",
                            "Agustus", "September", "Oktober", "November", "Desember"
                        ];
                        return date.getDate() + ' ' + months[date.getMonth()] + ' ' + date.getFullYear();
                    }
                },
                {
                    data: 'tipe_surat',
                    name: 'tipe_surat'
                },
                {
                    data: 'jenis_surat',
                    name: 'jenis_surat'
                },
                {
                    data: 'status',
                    name: 'status',
                    render: function(data) {
                        return data === 'Diterima' ?
                            '<span class="badge bg-success w-75">Diterima</span>' :
                            '<span class="badge bg-danger w-75">Ditolak</span>';
                    }
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                }
            ]
        });

        // Filter chaining
        $('#filter_tipe').on('change', function() {
            const selectedTipe = $(this).val();
            $('#filter_jenis option').each(function() {
                const optionTipe = $(this).data('tipe');
                if (!selectedTipe || $(this).val() === '' || optionTipe === selectedTipe) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });

            $('#filter_jenis').val('');
            reloadDatatable();
        });

        $('#filter_jenis').on('change', reloadDatatable);
    </script>
@endpush
