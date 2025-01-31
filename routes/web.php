<?php

use App\Http\Controllers\CnpjController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes VAGNER GIRALDINO 23/01/2025
| Aqui temos todas as nossas rotas web;
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

// Consulta de um único CNPJ
Route::get('/consulta-cnpj/{cnpj}', [CnpjController::class, 'consultar']);

// Upload de arquivo de CNPJs
Route::post('/upload', [CnpjController::class, 'upload'])->name('upload');

// Processamento em lote de CNPJs pendentes
Route::get('/processar', [CnpjController::class, 'processarCnpjs'])->name('processar');

// Listar CNPJs pendentes (página completa)
Route::get('/cnpjs-pendentes-count', [CnpjController::class, 'getPendingCnpjsCount'])->name('getPendingCnpjsCount');

// Validação de CNPJs selecionados pelo usuário
Route::post('/validateCnpjs', [CnpjController::class, 'validateCnpjs'])->name('validateCnpjs');

// Listar CNPJs pendentes com paginação
Route::get('/getPaginatedCnpjs', [CnpjController::class, 'getPaginatedCnpjs'])->name('getPaginatedCnpjs');

Route::get('/list-files', [CnpjController::class, 'listUploadedFiles'])->name('listUploadedFiles');

Route::post('/delete-file', [CnpjController::class, 'deleteFile'])->name('deleteFile');

Route::post('/process-file', [CnpjController::class, 'processFile'])->name('processFile');

Route::post('/validate-cnpjs-by-date', [CnpjController::class, 'validateCnpjsByDate'])->name('validateCnpjsByDate');

Route::delete('/acnpjs/clear-pending', [CnpjController::class, 'clearPendingCnpjs'])->name('clearPendingCnpjs');
