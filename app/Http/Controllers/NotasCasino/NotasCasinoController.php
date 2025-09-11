<?php

namespace App\Http\Controllers\NotasCasino;

use App\Http\Controllers\Controller;

class NotasCasinoController extends Controller
{
    //TODO: OBTENER DE LA BDD LAS CATEGORIAS, TIPO EVENTO
    public function index(){
        return view('NotasCasino.indexNotasCasino');
    }


}