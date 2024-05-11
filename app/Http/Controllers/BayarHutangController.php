<?php

namespace App\Http\Controllers;

use App\Models\BayarHutang;
use Illuminate\Http\Request;
use App\Models\Hutang;

class BayarHutangController extends Controller
{
    public function index()
    {
        $bayarhutang = BayarHutang::all();
        $hutangs = Hutang::where('id_usaha', auth()->user()->id_usaha)->get(); 
        return view('pembayaran.hutang', compact('bayarhutang','hutangs')); 
    }

    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'tanggal_pembayaran' => 'required|date',
            'nama' => 'required|string',
            'pembayaran' => 'required|string',
            'jumlah' => 'required|numeric|min:0',
        ]);

        $hutang = Hutang::where('nama', $request->nama)->first();

        if (!$hutang) {
            return redirect()->back()->with('error', 'Nama hutang tidak ditemukan');
        }

        if ($hutang->jumlah_cicilan <= 0) {
            return redirect()->back()->with('error', 'Jumlah cicilan sudah habis');
        }

        if ($request->jumlah > $hutang->sisa_hutang) {
            return redirect()->back()->with('error', 'Jumlah melebihi sisa hutang');
        }

        $sisa_hutang = $hutang->sisa_hutang - $request->jumlah;
        $hutang->sisa_hutang = $sisa_hutang;

        $hutang->jumlah_cicilan -= 1;
        if ($sisa_hutang <= 0) {
            $hutang->status = true; // Mengubah status menjadi true jika lunas
        }
        $hutang->save();

        // Simpan data pembayaran hutang
        BayarHutang::create([
            'id_hutang' => $hutang->id,
            'tanggal_pembayaran' => $request->tanggal_pembayaran,
            'nama' => $request->nama,
            'pembayaran' => $request->pembayaran,
            'jumlah' => $request->jumlah,
            'id_usaha' => $hutang->id_usaha,
        ]);

        return redirect()->route('hutang.index')->with('success', 'Pembayaran hutang berhasil disimpan.');
    }
}