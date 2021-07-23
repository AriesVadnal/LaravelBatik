<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Barang;
use App\Pesanan;
use App\PesananDetail;
use Auth;
use Carbon\Carbon;
use Alert;

class PesanController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index($id)
    {
        $barang = Barang::where('id', $id)->first();
        return view('pesan.index', compact('barang'));
    }

    public function pesan(Request $request, $id)
    {
        $barang = Barang::where('id', $id)->first();
        $tanggal = Carbon::now();

        // Validasi Persediaan stok
        if ( $request->jumlah_pesan > $barang->stok )
        {
            alert()->error('Error', 'Stok Tidak cukup');
            return redirect('pesan/'.$id);
        }

        // Insert To Database 
        $cek_pesanan = Pesanan::where('user_id', Auth::user()->id)->where('status', 0)->first();

        if(empty($cek_pesanan))
        {
            $pesanan = new Pesanan;
            $pesanan->user_id = Auth::user()->id;
            $pesanan->tanggal = $tanggal;
            $pesanan->jumlah_harga = 0;
            $pesanan->status = 0;
            $pesanan->kode = mt_rand(100,999);
            $pesanan->save();
        }

        // Insert To Table PesananDetail
        $pesanan_baru = Pesanan::where('user_id', Auth::user()->id)->where('status', 0)->first();

        $cek_pesanan_detail = PesananDetail::where('barang_id', $barang->id)->where('pesanan_id', $pesanan_baru->id)->first();
        if(empty($cek_pesanan_detail))
        {
            $pesanan_detail = new PesananDetail;
            $pesanan_detail->barang_id = $barang->id;
            $pesanan_detail->pesanan_id = $pesanan_baru->id;
            $pesanan_detail->jumlah = $request->jumlah_pesan;
            $pesanan_detail->jumlah_harga = $barang->harga * $request->jumlah_pesan;
            $pesanan_detail->save();
        } else 
        {
            $pesanan_detail = PesananDetail::where('barang_id', $barang->id)->where('pesanan_id', $pesanan_baru->id)->first();
            $pesanan_detail->jumlah += $request->jumlah_pesan;
            $pesanan_detail->jumlah_harga += $barang->harga * $request->jumlah_pesan;
            $pesanan_detail->update();
        }

        $harga_pesanan = Pesanan::where('user_id', Auth::user()->id)->where('status',0)->first();
        $harga_pesanan->jumlah_harga += $barang->harga * $request->jumlah_pesan;
        $harga_pesanan->update();

        alert()->success('Success', 'Barang berhasil masuk keranjang');
        return redirect('/home');
    }

    public function check_out()
    {
        $pesanan = Pesanan::where('user_id', Auth::user()->id)->where('status', 0)->first();
        $pesanan_details = [];
        if(!empty($pesanan))
        {
            $pesanan_details = PesananDetail::where('pesanan_id', $pesanan->id)->get();
        }
        return view('pesan.check_out', compact('pesanan', 'pesanan_details'));
    }

    public function delete($id)
    {
        $pesanan_detail = PesananDetail::where('id', $id)->first();

        $pesanan = Pesanan::where('id', $pesanan_detail->pesanan_id)->first();
        $pesanan->jumlah_harga -= $pesanan_detail->jumlah_harga;
        $pesanan->update();

        $pesanan_detail->delete();
        alert()->error('Success', 'Pesanan berhasil di delete');
        return redirect('/check-out');
    }

    public function konfirmasi()
    {
        $user = Auth::user();

        if(empty($user->email))
        {
            alert()->error('Error', 'Lengkapi Profile');
            return redirect('/profile');
        }

        if(empty($user->nohp))
        {
            alert()->error('Error', 'Lengkapi Profile');
            return redirect('/profile');
        }

        $pesanan = Pesanan::where('user_id', Auth::user()->id)->where('status', 0)->first();
        $pesanan->status = 1;
        $pesanan->update();

        $pesanan_details = PesananDetail::where('pesanan_id', $pesanan->id)->get();
        foreach ( $pesanan_details as $pesanan_detail )
        {
            $barang = Barang::where('id', $pesanan_detail->barang_id)->first();
            $barang->stok -= $pesanan_detail->jumlah;
            $barang->update();
        }

        alert()->success('Success', 'Barang berhasi di checkout');
        return redirect('/check-out');
    }
}
