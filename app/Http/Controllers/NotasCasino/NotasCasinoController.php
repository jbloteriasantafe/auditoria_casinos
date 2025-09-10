<?php

namespace App\Http\Controllers\NotasCasino;

use App\Http\Controllers\Controller;

class NotasCasinoController extends Controller
{
    public function index(){
        return view('NotasCasino.indexNotasCasino');
    }


}