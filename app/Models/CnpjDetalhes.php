<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CnpjDetalhes extends Model
{
    use HasFactory;

    protected $fillable = [
        'cnpj',
        'tipo',
        'porte',
        'nome',
        'fantasia',
        'abertura',
        'atividade_principal',
        'atividades_secundarias',
        'natureza_juridica',
        'logradouro',
        'numero',
        'complemento',
        'cep',
        'bairro',
        'municipio',
        'uf',
        'email',
        'telefone',
        'situacao',
        'data_situacao',
        'capital_social',
        'qsa',
        'simples',
        'simei',
        'ultima_atualizacao',
    ];
}
