<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        return view('profile.index', compact('user'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'password' => 'confirmed'
        ]);

        $user = Auth::user();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->alamat = $request->alamat;
        $user->nohp = $request->nohp;
        if ($request->password)
        {
            $user->password = bcrypt($request->password);
        }
        $user->update();

        alert()->success('Success', 'User barhasil di update');
        return redirect('/profile');
    }
}
