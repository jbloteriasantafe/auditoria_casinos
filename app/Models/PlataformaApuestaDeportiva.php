<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Plataforma de apuestas deportivas (Bplay AADD, CCOL AADD, Jugadon, etc.).
 *
 * Son opciones de casino/plataforma SOLO para el módulo de Notas Unificadas:
 * cargan notas pero NO asocian juegos ni MTM. Sus ids comparten la columna
 * `id_plataforma` de notas_ingreso/grupos_tramites con las plataformas online
 * de la API (ids 1, 2, ...); por eso esta tabla usa ids fijos >= 1001
 * (rangos disjuntos). El acceso se otorga por rol CARGA_NOTAS_<codigo>,
 * misma convención que las plataformas online.
 */
class PlataformaApuestaDeportiva extends Model
{
    protected $table = 'plataformas_apuestas_deportivas';

    protected $primaryKey = 'id_plataforma';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['id_plataforma', 'nombre', 'codigo'];
}
