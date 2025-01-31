<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cnpj extends Model
{
    use HasFactory;

    protected $fillable = ['cnpj', 'dados'];
}
