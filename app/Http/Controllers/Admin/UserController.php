<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Rules\MatchOldPassword;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\Facades\DataTables;

use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $query = User::latest()->get();

            return Datatables::of($query)
                ->addColumn('action', function ($item) {
                    $rolePrefix = [
                        'admin' => 'admin',
                        'guru' => 'guru',
                        'staff' => 'staff',
                        'kepala sekolah' => 'kepala-sekolah'
                    ];

                    $prefix = $rolePrefix[Session('user')['role']] ?? 'default'; // default jika role tidak dikenali
                    return '
                        <a class="btn btn-primary btn-xs" href="' .  url($prefix . '/user/' . $item->id . '/edit') .  '">
                            <i class="fas fa-edit"></i> &nbsp; Ubah
                        </a>
                        <form action="' . route('user.destroy', $item->id) . '" method="POST" onsubmit="return confirm(' . "'Anda akan menghapus item ini secara permanen dari situs anda?'" . ')">
                            ' . method_field('delete') . csrf_field() . '
                            <button class="btn btn-danger btn-xs">
                                <i class="far fa-trash-alt"></i> &nbsp; Hapus
                            </button>
                        </form>
                    ';
                })
                ->editColumn('name', function ($item) {
                    return $item->profile ?
                        '<div class="d-flex align-items-center">
                                    <div class="avatar me-2"><img class="avatar-img img-fluid" src="' . Storage::url($item->profile) . '" /></div>' .
                        $item->name . '
                                </div>'
                        :
                        '<div class="d-flex align-items-center">
                                    <div class="avatar me-2"><img class="avatar-img img-fluid" src="https://ui-avatars.com/api/?name=' . $item->name . '" /></div>' .
                        $item->name . '
                                </div>';
                })
                ->addIndexColumn()
                ->removeColumn('id')
                ->rawColumns(['action', 'name'])
                ->make();
        }
        return view('pages.admin.user.index');
    }

    public function create()
    {
        return view('pages.admin.user.create');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email:dns|unique:users',
            'password' => 'required|min:5|max:255',
            'role' => 'required|in:admin,staff administrasi,guru,kepala sekolah',
        ]);

        $validatedData['password'] = Hash::make($validatedData['password']);

        User::create($validatedData);

        return redirect()
            ->route('user.index')
            ->with('success', 'Sukses! Data Pengguna Berhasil Disimpan');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = Auth::user();

        return view('pages.admin.user.index', [
            'user' => $user
        ]);
    }

    public function edit($id)
    {
        $item = User::findOrFail($id);

        return view('pages.admin.user.edit', [
            'item' => $item
        ]);
    }

    public function update(Request $request, $id)
    {
        /** ----------------------------------------------------------------
         * 1. VALIDASI
         * ----------------------------------------------------------------*/
        $validatedData = $request->validate([
            'name'      => 'required|max:255',
            'email'     => 'required|email:dns|unique:users,email,' . $id,
            'password'  => 'nullable|min:6',
            'signature' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        /** ----------------------------------------------------------------
         * 2. TEMUKAN USER
         * ----------------------------------------------------------------*/
        $user = User::findOrFail($id);

        /** ----------------------------------------------------------------
         * 3. HANDLE PASSWORD (jika diisi)
         * ----------------------------------------------------------------*/
        if ($request->filled('password')) {
            $validatedData['password'] = Hash::make($request->password);
        } else {
            unset($validatedData['password']); // Jangan sentuh kolom password
        }

        /** ----------------------------------------------------------------
         * 4. HANDLE FILE SIGNATURE (jika ada)
         * ----------------------------------------------------------------*/
        if ($request->hasFile('signature')) {
            if ($user->signature && file_exists(public_path($user->signature))) {
                @unlink(public_path($user->signature));
            }

            $file           = $request->file('signature');
            $filename       = time() . '_' . $file->getClientOriginalName();
            $destinationDir = public_path('assets/signatures');
            if (!is_dir($destinationDir)) {
                mkdir($destinationDir, 0755, true);
            }
            $file->move($destinationDir, $filename);
            $validatedData['signature'] = 'assets/signatures/' . $filename;
        }

        /** ----------------------------------------------------------------
         * 5. UPDATE DATA
         * ----------------------------------------------------------------*/
        $user->update($validatedData);

        return redirect()
            ->route('user.index')
            ->with('success', 'Sukses! Data pengguna telah diperbarui.');
    }

    public function destroy($id)
    {
        $item = User::findorFail($id);

        Storage::delete($item->profile);

        $item->delete();

        return redirect()
            ->route('user.index')
            ->with('success', 'Sukses! Data Pengguna telah dihapus');
    }

    public function upload_profile(Request $request)
    {
        $validatedData = $request->validate([
            'profile' => 'required|image|file|max:1024',
        ]);

        $id = $request->id;
        $item = User::findOrFail($id);

        //dd($item);

        if ($request->file('profile')) {
            Storage::delete($item->profile);
            $item->profile = $request->file('profile')->store('assets/profile-images');
        }

        $item->save();

        return redirect()
            ->route('user.index')
            ->with('success', 'Sukses! Photo Pengguna telah diperbarui');
    }

    public function change_password()
    {
        return view('pages.admin.user.change-password');
    }
}
