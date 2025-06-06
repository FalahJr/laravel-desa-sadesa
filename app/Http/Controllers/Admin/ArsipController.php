<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Surat;
use App\Models\FieldDefinition;
use App\Models\FieldValue;
use App\Models\JenisSurat;
use App\Models\Notifikasi;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Yajra\DataTables\Facades\DataTables;


class ArsipController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Surat::with('jenisSurat')
                ->where('status', '!=', 'Pending');

            if ($request->tipe_surat) {
                $query->where('tipe_surat', $request->tipe_surat);
            }

            if ($request->jenis_surat_id) {
                $query->where('jenis_surat_id', $request->jenis_surat_id);
            }

            $data = $query->latest()->get();

            return datatables()->of($data)
                ->addColumn('jenis_surat', function ($item) {
                    return $item->jenisSurat ? $item->jenisSurat->nama : '-';
                })
                ->addColumn('action', function ($item) {
                    $prefix = Session('user')['role'] === 'admin' ? 'admin' : (Session('user')['role'] === 'kepala desa' ? 'kepala-desa' : 'staff');

                    $view = '<a class="btn btn-info btn-xs" href="' . url("$prefix/arsip/{$item->id}") . '">
                            <i class="fas fa-eye"></i> &nbsp; Lihat
                         </a>';

                    $delete = '';
                    if (Session('user')['role'] === 'admin') {
                        $delete = '
                        <form action="' . route('arsip.destroy', $item->id) . '" method="POST" style="display:inline;" onsubmit="return confirm(\'Yakin ingin menghapus surat ini?\')">
                            ' . method_field('delete') . csrf_field() . '
                            <button class="btn btn-danger btn-xs" type="submit">
                                <i class="far fa-trash-alt"></i> &nbsp; Hapus
                            </button>
                        </form>';
                    }

                    return $view . $delete;
                })
                ->addIndexColumn()
                ->removeColumn('id')
                ->make();
        }

        $jenisSurat = JenisSurat::all();
        return view('pages.admin.arsip.index', compact('jenisSurat'));
    }

    public function create(Request $request)
    {
        // Ambil semua jenis surat dengan tipe 'masuk' untuk dropdown
        $jenisSuratList = JenisSurat::where('tipe', 'keluar')->get();

        // Ambil jenis_surat_id dari query string jika sudah dipilih
        $selectedJenisSuratId = $request->query('jenis_surat_id');

        // Default: tidak ada field dinamis
        $fieldDefinitions = collect();

        // Kalau user sudah pilih jenis surat, ambil field-field dinamis
        if ($selectedJenisSuratId) {
            $selectedJenisSurat = JenisSurat::findOrFail($selectedJenisSuratId);
            $fieldDefinitions = $selectedJenisSurat->field_definitions()->where('is_active', 'Y')->get();
        } else {
            $selectedJenisSurat = null;
        }

        // dd($fieldDefinitions);

        return view('pages.admin.arsip.create', [
            'jenisSuratList' => $jenisSuratList,
            'selectedJenisSuratId' => $selectedJenisSuratId,
            'selectedJenisSurat' => $selectedJenisSurat,
            'fieldDefinitions' => $fieldDefinitions
        ]);
    }


    public function store(Request $request)
    {
        // Validasi statis dulu
        $request->validate([
            'tgl_surat'      => 'required|date',
            'nama_surat'     => 'required|string|max:255',
            'file_lampiran'  => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:2048',
            'jenis_surat_id' => 'required|exists:jenis_surat,id',
        ]);

        // Ambil field definitions sesuai jenis surat yang dipilih
        $fieldDefinitions = FieldDefinition::where('jenis_surat_id', $request->jenis_surat_id)
            ->where('is_active', 'Y')
            ->get();

        // Siapkan aturan validasi dinamis
        $dynamicRules = [];
        foreach ($fieldDefinitions as $field) {
            $rule = [];
            if ($field->is_required === 'Y') {
                $rule[] = 'required';
            } else {
                $rule[] = 'nullable';
            }

            // Validasi tipe data sesuai tipe_input
            switch ($field->tipe_input) {
                case 'number':
                    $rule[] = 'numeric';
                    break;
                case 'email':
                    $rule[] = 'email';
                    break;
                case 'date':
                    $rule[] = 'date';
                    break;
                    // text & textarea tidak perlu aturan khusus
            }

            $dynamicRules['field_values.' . $field->id] = implode('|', $rule);
        }

        // Validasi dinamis untuk field_values
        $request->validate($dynamicRules);

        DB::beginTransaction();

        try {
            $filePath = null;

            // Generate nomor_surat otomatis dengan 2 digit untuk nomor urut
            $lastSurat = Surat::orderBy('id', 'desc')->first();
            $nextId = $lastSurat ? $lastSurat->id + 1 : 1;
            $nextIdFormatted = str_pad($nextId, 2, '0', STR_PAD_LEFT);

            $tanggalSuratFormatted = \Carbon\Carbon::parse($request->tgl_surat)->format('dmY');

            $nomorSurat = $request->jenis_surat_id . '/' . $nextIdFormatted . '/' . $tanggalSuratFormatted;

            // Cek apakah ada file lampiran baru yang diupload
            if ($request->hasFile('file_lampiran')) {
                $filePath = $request->file('file_lampiran')->store('assets/lampiran', 'public');
            }

            // Simpan surat masuk
            $surat = Surat::create([
                'nomor_surat'       => $nomorSurat,
                'tanggal_surat'     => $request->tgl_surat,
                'nama_surat'        => $request->nama_surat,
                'file_lampiran'     => $filePath, // bisa null jika tidak ada upload
                'tipe_surat'        => 'keluar',
                'jenis_surat_id'    => $request->jenis_surat_id,
                'created_by'        => 'admin', // sesuaikan dengan auth user
                'status'            => 'Pending',
            ]);

            // Simpan field dinamis jika ada input
            if ($request->filled('field_values')) {
                foreach ($request->input('field_values') as $fieldId => $value) {
                    FieldValue::create([
                        'surat_id'             => $surat->id,
                        'field_definition_id'  => $fieldId,
                        'value'                => $value,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('arsip.index')->with('success', 'Surat keluar berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Gagal menyimpan data: ' . $e->getMessage())->withInput();
        }
    }




    public function edit($id)
    {
        $surat = Surat::with('fieldValues')->findOrFail($id);

        $jenisSuratList = JenisSurat::where('tipe', 'keluar')->get();


        // Ambil field definition berdasarkan jenis_surat_id dari surat
        $fieldDefinitions = FieldDefinition::where('jenis_surat_id', $surat->jenis_surat_id)
            ->where('is_active', 'Y')
            ->get();

        // Kelompokkan nilai field berdasarkan id field definition
        $fieldValues = $surat->fieldValues->keyBy('field_definition_id');

        return view('pages.admin.arsip.edit', compact('surat', 'fieldDefinitions', 'fieldValues', 'jenisSuratList'));
    }


    public function update(Request $request, $id)
    {
        $surat = Surat::findOrFail($id);

        // Validasi field statis sesuai form input
        $request->validate([
            'nomor_surat'       => 'required|string|max:100',
            'tanggal_surat'      => 'required|date',
            'nama_surat'     => 'required|string|max:255',
            'jenis_surat_id' => 'required|exists:jenis_surat,id',
            'file_lampiran'  => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:2048',
        ]);

        // Ambil field definitions berdasar jenis_surat_id baru
        $fieldDefinitions = FieldDefinition::where('jenis_surat_id', $request->jenis_surat_id)->get();

        // Validasi dinamis
        $rules = [];
        foreach ($fieldDefinitions as $field) {
            $rule = $field->is_required === 'Y' ? 'required' : 'nullable';

            switch ($field->tipe_input) {
                case 'number':
                    $rule .= '|numeric';
                    break;
                case 'email':
                    $rule .= '|email';
                    break;
                case 'date':
                    $rule .= '|date';
                    break;
            }

            $rules['field_values.' . $field->id] = $rule;
        }

        $request->validate($rules);

        DB::beginTransaction();

        try {
            // Update surat statis
            $surat->nomor_surat = $request->nomor_surat;
            $surat->tanggal_surat = $request->tanggal_surat;
            $surat->nama_surat = $request->nama_surat;
            $surat->jenis_surat_id = $request->jenis_surat_id;

            // Update file lampiran jika ada file baru
            if ($request->hasFile('file_lampiran')) {
                if ($surat->file_lampiran && \Storage::disk('public')->exists($surat->file_lampiran)) {
                    \Storage::disk('public')->delete($surat->file_lampiran);
                }
                $filePath = $request->file('file_lampiran')->store('assets/lampiran', 'public');
                $surat->file_lampiran = $filePath;
            }

            $surat->save();

            $inputFieldValues = $request->input('field_values', []);

            foreach ($fieldDefinitions as $field) {
                // Cegah value null, ganti dengan string kosong
                $value = $inputFieldValues[$field->id] ?? '';
                if ($value === null) {
                    $value = '';
                }

                FieldValue::updateOrCreate(
                    ['surat_id' => $surat->id, 'field_definition_id' => $field->id],
                    ['value' => $value]
                );
            }

            DB::commit();

            return redirect()->route('arsip.index')->with('success', 'Surat keluar berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Gagal memperbarui data: ' . $e->getMessage())->withInput();
        }
    }


    public function destroy($id)
    {
        $surat = Surat::findOrFail($id);

        DB::beginTransaction();

        try {
            // Hapus semua notifikasi terkait surat ini
            Notifikasi::where('surat_id', $surat->id)->delete();
            FieldValue::where('surat_id', $surat->id)->delete();
            $surat->delete();

            DB::commit();

            // return redirect()->route('arsip.index')->with('success', 'Surat Arsip berhasil dihapus.');
            // return redirect()->route('surat-keluar.index')->with('success', 'Surat keluar berhasil dihapus.');
            if (Session('user')['role'] == 'admin') {
                return redirect('/admin/arsip')->with('success', 'Arsip berhasil dihapus.');
            } elseif (Session('user')['role'] == 'kepala desa') {
                return redirect('/kepala-desa/arsip')->with('success', 'Arsip berhasil dihapus.');
            } else {
                return redirect('/staff/arsip')->with('success', 'Arsip berhasil dihapus.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Gagal menghapus data: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $surat = Surat::with(['jenisSurat', 'fieldValues'])->findOrFail($id);

        // Ambil field definitions dari jenis surat ini (aktif saja)
        $fieldDefinitions = FieldDefinition::where('jenis_surat_id', $surat->jenis_surat_id)
            ->where('is_active', 'Y')
            ->get();

        // Mapping value berdasarkan field_definition_id
        $fieldValues = $surat->fieldValues->keyBy('field_definition_id');

        $downloadUrl = url("/arsip/{$surat->id}/download"); // atau route() jika pakai route name
        $qrCode = QrCode::size(200)->generate($downloadUrl);

        return view('pages.admin.arsip.show', compact('surat', 'fieldDefinitions', 'fieldValues', 'qrCode'));
    }

    public function download($id)
    {
        $surat = Surat::with(['jenisSurat', 'fieldValues.fieldDefinition'])->findOrFail($id);
        $user = User::where('id', $surat->user_id)->first();
        // Mapping field dinamis dengan label-nya
        $fields = $surat->fieldValues->map(function ($fv) {
            return [
                'label' => $fv->fieldDefinition->label,
                'value' => $fv->value,
            ];
        });

        // URL file lampiran jika ada
        $lampiranUrl = $surat->file_lampiran
            ? url('public/storage/' . $surat->file_lampiran)
            : null;

        $downloadUrl = url("/arsip/{$surat->id}/download"); // atau route() jika pakai route name
        // Generate QR code sebagai PNG base64
        // Hasilkan QR Code dalam format PNG dan encode ke base64
        $qrCodeImage = QrCode::format('svg')
            ->size(150)
            ->generate($downloadUrl);

        // Konversi hasilnya jadi base64 agar bisa dipakai sebagai <img src="...">
        $qrCode = 'data:image/png;base64,' . base64_encode($qrCodeImage);


        // dd($fields);
        // Kirim data ke view untuk PDF
        $pdf = Pdf::loadView('pages.admin.arsip.download', [
            'surat' => $surat,
            'fields' => $fields,
            'lampiranUrl' => $lampiranUrl,
            'user' => $user,
            'qrCode' => $qrCode,
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('surat-keterangan-kematian.pdf');
    }

    public function approve(Request $request, $id)
    {
        // dd('Function approve terpanggil');

        $item = Surat::findOrFail($id);

        if ($item->status === 'Diterima') {
            return redirect()->back()->with('info', 'Surat ini sudah disetujui sebelumnya.');
        }

        $item->update(['status' => 'Diterima']);

        return redirect()->back()
            ->with('success', 'Surat berhasil disetujui.');
    }

    public function reject(Request $request, $id)
    {
        $item = Surat::findOrFail($id);

        if ($item->status === 'Ditolak') {
            return redirect()->back()->with('info', 'Surat ini sudah ditolak sebelumnya.');
        }

        $item->update(['status' => 'Ditolak']);

        return redirect()->back()
            ->with('success', 'Surat berhasil ditolak.');
    }
}

// SuratKeluarController identik, hanya ganti semua 'masuk' menjadi 'keluar' dan view-nya ke 'surat-keluar'
