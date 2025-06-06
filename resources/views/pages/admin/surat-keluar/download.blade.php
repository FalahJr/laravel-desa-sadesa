<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>{{ $surat->jenisSurat->nama }}</title>
    <style>
        body {
            font-family: 'Times New Roman', serif;
            margin: 0;
            padding: 20px;
            font-size: 12pt;
            line-height: 1.5;
            /* background-color: red; */
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #000;
            padding-bottom: 15px;
        }

        .title {
            text-align: center;
            font-size: 15pt;
            font-weight: bold;
            text-decoration: underline;
            margin: 10px 0 10px 0;
            text-transform: uppercase;
        }

        .nomor {
            text-align: center;
            font-size: 12pt;
            margin-bottom: 15px;
        }

        .content {
            margin: 10px 0;
            text-align: justify;
        }

        .pembuka {
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            margin-top: 15px;
        }

        td {
            padding: 4px 2px;
        }

        .penutup {
            margin-top: 30px;
            margin-bottom: 40px;
            /* text-transform: capitalize; */
        }



        .signature-date {
            margin-bottom: 10px;
        }

        .signature-title {
            /* margin-bottom: 80px; */
        }

        .signature-name {
            text-decoration: underline;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <!-- Kop Surat -->
    <div class="header">
        <img src="{{ asset('public/assets/kop-surat-3.png') }}" style="max-width: 90%; height: auto;">
    </div>

    <!-- Judul -->
    <div class="title">{{ strtoupper($surat->jenisSurat->nama) }}</div>

    <!-- Nomor -->
    <div class="nomor">
        Nomor : {{ $surat->nomor_surat ?? '........./........./DS-WILAYUT/VIII/2023' }}
    </div>

    <!-- Isi -->
    <div class="content">
        @if ($surat->status == 'Diterima')
            <div class="pembuka">
                Yang bertanda tangan dibawah ini :

                <table>
                    <tr>
                        <td style="width: 30%;">Nama </td>
                        <td style="width: 2%;">:</td>
                        <td style="text-transform: uppercase;">
                            {{ $surat->user->name ?? '.....................................................' }} </td>

                    </tr>
                    <tr>
                        <td style="width: 30%;">Jabatan </td>
                        <td style="width: 2%;">:</td>
                        <td style="text-transform: uppercase;">
                            {{ $surat->user->role }} WILAYUT </td>

                    </tr>
                </table>
            </div>
        @endif

        <div class="pembuka">
            Kecamatan Sukodono Kabupaten Sidoarjo, dengan ini menerangkan bahwa :

        </div>

        <!-- Tabel Data -->
        @if ($fields && $fields->isNotEmpty())
            <table>
                @foreach ($fields as $field)
                    <tr>
                        <td style="width:
                        30%;">{{ $field['label'] }}</td>
                        <td style="width: 2%;">:</td>
                        <td style="">
                            @php
                                $value = $field['value'];
                                $formattedValue = $value;

                                try {
                                    // Cek apakah nilai bisa di-parse jadi tanggal dan valid
                                    // $parsedDate = \Carbon\Carbon::parse($value);

                                    // // Tampilkan hanya jika string aslinya memang tanggal, misal "2025-05-29" atau lainnya
                                    // if ($parsedDate && ($parsedDate->format('Y-m-d') === $value || strtotime($value))) {
                                    //     $formattedValue = $parsedDate->locale('id')->translatedFormat('d F Y');
                                    // }

                                    $parsedDate = \Carbon\Carbon::createFromFormat('Y-m-d', $formattedValue);
                                    $isDate = $parsedDate && $parsedDate->format('Y-m-d') === $formattedValue;
                                } catch (\Exception $e) {
                                    $isDate = false;

                                    // Abaikan jika bukan tanggal
                                }
                            @endphp
                            @if ($isDate)
                                {{ $parsedDate->translatedFormat('d F Y') }}
                            @else
                                {{ $formattedValue }}
                            @endif
                            {{-- {{ $formattedValue ?? '.....................................................' }} --}}
                        </td>

                    </tr>
                @endforeach
            </table>
        @endif

        <!-- Penutup -->
        <div class="penutup">
            @if ($surat->jenisSurat->footer)
                {!! $surat->jenisSurat->footer !!}
            @else
                Demikian {{ $surat->jenisSurat->nama }} ini kami buat dengan sebenarnya dan supaya dapat dipergunakan
                sebagaimana mestinya.
            @endif
        </div>
    </div>

    <!-- Tanda Tangan + QR Code -->
    <table style="width: 100%; margin-top: 50px;">
        <tr>
            <!-- QR Code kiri -->
            <td style="width: 50%; text-align: left;">
                @if ($surat->status === 'Diterima' && $user && $user->signature)
                    <img src="{{ $qrCode }}" alt="QR Code" style="height: 100px;">
                @endif
            </td>

            <!-- Tanda tangan kanan -->
            <td style="width: 50%; text-align: right;">
                <div>
                    Wilayut, {{ \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y') }}
                </div>
                @if ($surat->status == 'Diterima')
                    <div style="margin-top: 5px;">{{ $surat->user->name }},</div>
                @else
                    <div style="margin-top: 5px;">Kepala Desa,</div>
                @endif

                @if ($surat->status === 'Diterima' && $user && $user->signature)
                    <div style="margin-top: 10px;">
                        <img src="{{ public_path($user->signature) }}" alt="Tanda Tangan" style="height: 100px;">
                    </div>
                @else
                    <div style="height: 100px;"></div>
                @endif

                <div class="signature-name" style="margin-top: 5px; font-weight: bold; text-decoration: underline;">
                    {{ $user->name ?? '.......................................' }}
                </div>
            </td>
        </tr>
    </table>





    <!-- Lampiran File (opsional) -->
    {{-- @if ($lampiranUrl)
        <div style="margin-top: 40px;">
            <strong>Lampiran:</strong>
            <br>
            <a href="{{ $lampiranUrl }}">{{ $lampiranUrl }}</a>
        </div>
    @endif --}}

</body>

</html>
