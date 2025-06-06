@extends('layouts.admin')

@section('title')
    Detail Surat Keluar
@endsection

@section('container')
    @php
        \Carbon\Carbon::setLocale('id');
    @endphp
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
                                Detail Surat Arsip
                            </h1>
                            <div class="page-header-subtitle">Informasi lengkap surat keluar</div>
                        </div>
                        <div class="col-12 col-xl-auto mt-4">
                            <a class="btn btn-sm btn-light text-primary"
                                href="{{ (Session('user')['role'] == 'admin' ? url('/admin/arsip') : Session('user')['role'] == 'kepala desa') ? url('/kepala-desa/arsip') : url('/staff/arsip') }}">
                                <i class="me-1" data-feather="arrow-left"></i>
                                Kembali Ke Semua Surat Arsip
                            </a>
                        </div>
                    </div>

                    <nav class="mt-4 rounded" aria-label="breadcrumb">
                        <ol class="breadcrumb px-3 py-2 rounded mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin-dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('arsip.index') }}">Surat Keluar</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Detail</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </header>


        <!-- Main page content -->
        <div class="container-xl px-4 mt-n10">
            <div class="row gx-4">
                <div class="col-lg-7">
                    <div class="card mb-4">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center justify-content-between w-100">
                                <div>Detail Surat</div>
                                <div>

                                    @php
                                        $role = Session('user')['role'];
                                        $url = match ($role) {
                                            'admin' => url('/admin/arsip/' . $surat->id . '/download'),
                                            'kepala desa' => url('/kepala-desa/arsip/' . $surat->id . '/download'),
                                            default => url('/staff/arsip/' . $surat->id . '/download'),
                                        };
                                    @endphp
                                    <!-- Tombol Show QR -->
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal"
                                        data-bs-target="#qrModal">
                                        <i class="fa fa-qrcode"></i>&nbsp; Show QR
                                    </button>
                                    <a href="{{ $url }}" class="btn btn-sm btn-outline-primary ms-3"
                                        target="_blank">
                                        <i class="fa fa-download mr-3"></i>&nbsp; Download
                                    </a>
                                    @if (Session('user')['role'] == 'kepala desa')
                                        @if ($surat->status == 'Pending')
                                            <a href="{{ route('arsip.approve', $surat->id) }}"
                                                class="btn btn-sm btn-success">
                                                <i class="fa fa-check" aria-hidden="true"></i> &nbsp; Setujui
                                            </a>
                                            <a href="{{ route('arsip.reject', $surat->id) }}" class="btn btn-sm btn-danger">
                                                <i class="fa fa-times" aria-hidden="true"></i> &nbsp; Tolak
                                            </a>
                                        @elseif($surat->status == 'Diterima')
                                            <span class=" btn-sm btn-success text-capitalize">
                                                Surat Telah {{ $surat->status }}
                                            </span>
                                        @else
                                            <span class=" btn-sm btn-danger text-capitalize">
                                                Surat Telah {{ $surat->status }}
                                            </span>
                                        @endif
                                    @endif

                                </div>
                            </div>

                        </div>

                        <div class="card-body">
                            <table class="table">
                                <tbody>
                                    <tr>
                                        <th>Jenis Surat</th>
                                        <td class="text-capitalize">
                                            {{ $surat->jenisSurat->nama ? $surat->jenisSurat->nama : '-' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Nomor Surat</th>
                                        <td>{{ $surat->nomor_surat }}</td>
                                    </tr>
                                    <tr>
                                        <th>Tanggal Surat</th>
                                        <td>{{ \Carbon\Carbon::parse($surat->tanggal_surat)->translatedFormat('d F Y') }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Nama Surat</th>
                                        <td>{{ $surat->nama_surat }}</td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        @if ($surat->status == 'Pending')
                                            <td class="">
                                                <span class=" btn-sm btn-warning text-capitalize">

                                                    {{ $surat->status }}
                                                    <span class=" btn-sm btn-danger text-capitalize">
                                            </td>
                                        @elseif ($surat->status == 'Diterima')
                                            <td>
                                                <span class=" btn-sm btn-success text-capitalize">
                                                    {{ $surat->status }}
                                                </span>
                                            </td>
                                        @else
                                            <td>
                                                <span class=" btn-sm btn-danger text-capitalize">
                                                    {{ $surat->status }}
                                                </span>
                                            </td>
                                        @endif
                                        {{-- <td>{{ $surat->status }}</td> --}}
                                    </tr>

                                    <!-- Field Dinamis -->
                                    @foreach ($surat->fieldValues as $value)
                                        <tr>
                                            <th>{{ $value->fieldDefinition->label }}</th>
                                            <td>
                                                @php
                                                    try {
                                                        $parsedDate = \Carbon\Carbon::createFromFormat(
                                                            'Y-m-d',
                                                            $value->value,
                                                        );
                                                        $isDate =
                                                            $parsedDate &&
                                                            $parsedDate->format('Y-m-d') === $value->value;
                                                    } catch (\Exception $e) {
                                                        $isDate = false;
                                                    }
                                                @endphp

                                                @if ($isDate)
                                                    {{ $parsedDate->translatedFormat('d F Y') }}
                                                @else
                                                    {{ $value->value }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card mb-4">
                        <div class="card-header">
                            Lampiran Surat
                        </div>
                        <div class="card-body">
                            @if ($surat->file_lampiran)
                                @php
                                    $ext = strtolower(pathinfo($surat->file_lampiran, PATHINFO_EXTENSION));
                                    // $fileUrl = url('public/storage/' . $surat->file_lampiran);
                                    $fileUrl = asset('/public/' . $surat->file_lampiran);

                                @endphp

                                @if (in_array($ext, ['pdf']))
                                    <embed src="{{ $fileUrl }}" width="100%" height="375" type="application/pdf">
                                @elseif (in_array($ext, ['jpg', 'jpeg', 'png']))
                                    <img src="{{ $fileUrl }}" class="img-fluid rounded" alt="Lampiran Surat">
                                @else
                                    <p>File tidak dapat ditampilkan. <a href="{{ $fileUrl }}" target="_blank"
                                            class="btn btn-sm btn-primary">Download</a></p>
                                @endif
                            @else
                                <p>Tidak ada file lampiran.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal QR Code -->
        <div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content text-center p-4">
                    <div class="modal-header border-0">
                        <h5 class="modal-title" id="qrModalLabel">QR Code untuk Unduh Surat</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        {!! $qrCode !!}
                        <p class="mt-3"><strong>{{ $surat->nomor_surat }}</strong></p>
                    </div>
                </div>
            </div>
        </div>

    </main>
@endsection
