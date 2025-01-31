<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Cnpj;
use App\Models\CnpjDetalhes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CnpjController extends Controller
{
    public function consultar($cnpj)
    {
        if (!$this->validarCnpj($cnpj)) {
            return response()->json(['error' => 'CNPJ inválido.'], 400);
        }

        $cnpjData = Cnpj::where('cnpj', $cnpj)->first();

        if ($cnpjData) {
            return view('cnpj', ['dados' => json_decode($cnpjData->dados, true)]);
        }

        $url = "https://receitaws.com.br/v1/cnpj/{$cnpj}";

        try {
            $response = Http::get($url);

            if ($response->status() == 429) {
                return response()->json(['error' => 'Limite de consultas excedido.'], 429);
            }

            if ($response->status() == 504) {
                return response()->json(['error' => 'Timeout na consulta.'], 504);
            }

            $dados = $response->json();

            Cnpj::create([
                'cnpj' => $cnpj,
                'dados' => json_encode($dados),
            ]);

            return view('cnpj', ['dados' => $dados]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao consultar a API.'], 500);
        }
    }

    private function validarCnpj($cnpj)
    {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        if (strlen($cnpj) != 14) {
            return false;
        }

        $soma1 = 0;
        $peso1 = 5;
        for ($i = 0; $i < 12; $i++) {
            $soma1 += $cnpj[$i] * $peso1;
            $peso1 = ($peso1 == 2) ? 9 : $peso1 - 1;
        }
        $resto1 = $soma1 % 11;
        $resto1 = ($resto1 < 2) ? 0 : 11 - $resto1;

        if ($resto1 != $cnpj[12]) {
            return false;
        }

        $soma2 = 0;
        $peso2 = 6;
        for ($i = 0; $i < 12; $i++) {
            $soma2 += $cnpj[$i] * $peso2;
            $peso2 = ($peso2 == 2) ? 9 : $peso2 - 1;
        }
        $soma2 += $resto1 * 2;
        $resto2 = $soma2 % 11;
        $resto2 = ($resto2 < 2) ? 0 : 11 - $resto2;

        return $resto2 == $cnpj[13];
    }

    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:csv,txt,xlsx',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Arquivo inválido.'], 400);
        }

        $path = $request->file('file')->store('uploads');
        Log::info("Caminho salvo: " . storage_path("app/{$path}"));

        $file = fopen(storage_path("app/{$path}"), 'r');

        $header = true;
        $count = 0;
        $invalid = 0;

        while (($data = fgetcsv($file, 1000, ',')) !== false) {
            if ($header) {
                $header = false;
                continue;
            }

            $cnpj = preg_replace('/[^0-9]/', '', $data[0]);

            if (strlen($cnpj) !== 14) {
                $invalid++;
                continue;
            }

            $created = Cnpj::firstOrCreate(['cnpj' => $cnpj]);
            if ($created->wasRecentlyCreated) {
                $count++;
            }
        }

        fclose($file);

        return response()->json([
            'success' => 'Planilha processada com sucesso!',
            'count' => $count,
            'invalid' => $invalid
        ], 200);
    }

    public function processarCnpjs()
    {
        Cnpj::whereNull('dados')->chunk(100, function ($cnpjs) {
            foreach ($cnpjs as $cnpj) {
                $url = "https://receitaws.com.br/v1/cnpj/{$cnpj->cnpj}";

                try {
                    $response = Http::get($url);

                    if ($response->ok()) {
                        $cnpj->dados = json_encode($response->json());
                        $cnpj->save();
                    }
                } catch (\Exception $e) {
                    Log::error("Erro ao processar CNPJ: {$cnpj->cnpj}", ['exception' => $e]);
                }
            }
        });

        return redirect('/')->with('success', 'CNPJs processados com sucesso!');
    }

    // Métodos adicionais para a API 
    public function getPendingCnpjsCount()
    {
        $count = Cnpj::whereNull('dados') // Para campos NULL
            ->orWhere('dados', '') // Para campos vazios
            ->count(); // Conta os pendentes

        return response()->json(['total' => $count]);
    }

    public function validateCnpjs(Request $request)
    {
        $totalCount = (int) $request->input('count', 3); // Número total de CNPJs a validar
        $createdAfter = $request->input('created_after', '2025-01-27 14:37:28'); // Data opcional do filtro

        if ($totalCount <= 0) {
            return response()->json(['error' => 'O número de CNPJs a serem validados deve ser maior que zero.'], 400);
        }

        $processedCount = 0;

        // Obter os CNPJs pendentes com o filtro
        $cnpjs = Cnpj::where('created_at', '>=', $createdAfter)
            ->whereNull('dados')
            ->take($totalCount)
            ->get();

        if ($cnpjs->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Nenhum CNPJ pendente para validar.']);
        }

        // Processar os CNPJs em lotes de 3
        $batches = $cnpjs->chunk(3); // Divide os CNPJs em lotes de 3

        foreach ($batches as $batch) {
            foreach ($batch as $cnpj) {
                try {
                    $url = "https://receitaws.com.br/v1/cnpj/{$cnpj->cnpj}";
                    $response = Http::get($url);

                    if ($response->ok()) {
                        $dados = $response->json();

                        // Atualizar os dados do CNPJ na tabela
                        $cnpj->dados = json_encode($dados);
                        $cnpj->save();

                        // Atualizar ou criar registro na tabela `cnpj_detalhes`
                        CnpjDetalhes::updateOrCreate(
                            ['cnpj' => $dados['cnpj']],
                            [
                                'tipo' => $dados['tipo'] ?? null,
                                'porte' => $dados['porte'] ?? null,
                                'nome' => $dados['nome'] ?? null,
                                'fantasia' => $dados['fantasia'] ?? null,
                                'abertura' => isset($dados['abertura']) ? \Carbon\Carbon::createFromFormat('d/m/Y', $dados['abertura'])->format('Y-m-d') : null,
                                'atividade_principal' => isset($dados['atividade_principal'][0]['text']) ? $dados['atividade_principal'][0]['text'] : null,
                                'data_situacao' => isset($dados['data_situacao']) ? \Carbon\Carbon::createFromFormat('d/m/Y', $dados['data_situacao'])->format('Y-m-d') : null,
                                'natureza_juridica' => $dados['natureza_juridica'] ?? null,
                                'logradouro' => $dados['logradouro'] ?? null,
                                'numero' => $dados['numero'] ?? null,
                                'complemento' => $dados['complemento'] ?? null,
                                'cep' => $dados['cep'] ?? null,
                                'bairro' => $dados['bairro'] ?? null,
                                'municipio' => $dados['municipio'] ?? null,
                                'uf' => $dados['uf'] ?? null,
                                'email' => $dados['email'] ?? null,
                                'telefone' => $dados['telefone'] ?? null,
                                'situacao' => $dados['situacao'] ?? null,
                                'capital_social' => isset($dados['capital_social']) ? (float) str_replace(['.', ','], ['', '.'], $dados['capital_social']) : null,
                                'ultima_atualizacao' => isset($dados['ultima_atualizacao']) ? \Carbon\Carbon::parse($dados['ultima_atualizacao'])->format('Y-m-d H:i:s') : null,
                            ]
                        );

                        $processedCount++;
                    } else {
                        Log::warning("Erro ao consultar CNPJ {$cnpj->cnpj}: {$response->status()}");
                    }
                } catch (\Exception $e) {
                    Log::error("Erro ao validar CNPJ: {$cnpj->cnpj}", ['exception' => $e]);
                }
            }

            // Pausa para respeitar o limite de 3 consultas por minuto
            usleep(2000000); // Pausa de 2 segundos entre cada lote
        }

        return response()->json([
            'success' => true,
            'message' => 'Validação concluída!',
            'processed_count' => $processedCount,
            'total_count' => $totalCount,
        ]);
    }





    public function listUploadedFiles()
    {
        // Lista todos os arquivos no diretório uploads
        $files = Storage::files('uploads');

        // Retorna a lista de arquivos com nomes e tamanhos
        $fileDetails = [];
        foreach ($files as $file) {
            $fileDetails[] = [
                'name' => basename($file),
                'size' => Storage::size($file),
                'last_modified' => date('Y-m-d H:i:s', Storage::lastModified($file)),
            ];
        }

        return response()->json($fileDetails);
    }

    public function processFile(Request $request)
    {
        $fileName = $request->input('file');

        if (Storage::exists("uploads/{$fileName}")) {
            $path = storage_path("app/uploads/{$fileName}");
            $file = fopen($path, 'r');
            // Adicione lógica de processamento aqui...

            fclose($file);

            return response()->json(['success' => 'Arquivo processado com sucesso!']);
        }

        return response()->json(['error' => 'Arquivo não encontrado!'], 404);
    }


    public function deleteFile(Request $request)
    {
        $fileName = $request->input('file');

        if (Storage::exists("uploads/{$fileName}")) {
            Storage::delete("uploads/{$fileName}");
            return response()->json(['success' => 'Arquivo excluído com sucesso!']);
        }

        return response()->json(['error' => 'Arquivo não encontrado!'], 404);
    }

    public function validateCnpjsByDate(Request $request)
    {
        $validatedData = $request->validate([
            'created_after' => 'required|date', // Valida se a data foi enviada e é válida
        ]);

        $createdAfter = $validatedData['created_after'];
        $totalCount = (int) $request->input('count', 3); // Total de CNPJs a serem processados
        $processedCount = 0;

        while ($processedCount < $totalCount) {
            // Determinar quantos CNPJs ainda precisam ser processados
            $remaining = $totalCount - $processedCount;
            $batchSize = min(3, $remaining); // Máximo de 3 por lote

            // Filtrar os CNPJs criados após a data informada e que ainda não foram validados
            $cnpjs = Cnpj::where('created_at', '>=', $createdAfter)
                ->whereNull('dados') // Apenas CNPJs não validados
                ->take($batchSize)
                ->get();

            if ($cnpjs->isEmpty()) {
                break; // Nenhum CNPJ restante para processar
            }

            foreach ($cnpjs as $cnpj) {
                try {
                    $url = "https://receitaws.com.br/v1/cnpj/{$cnpj->cnpj}";
                    $response = Http::get($url);

                    if ($response->ok()) {
                        $dados = $response->json();

                        // Atualizar os dados do CNPJ
                        $cnpj->dados = json_encode($dados);
                        $cnpj->save();

                        // Atualizar ou criar registro na tabela `cnpj_detalhes`
                        CnpjDetalhes::updateOrCreate(
                            ['cnpj' => $dados['cnpj']],
                            [
                                'tipo' => $dados['tipo'] ?? null,
                                'porte' => $dados['porte'] ?? null,
                                'nome' => $dados['nome'] ?? null,
                                'fantasia' => $dados['fantasia'] ?? null,
                                'abertura' => isset($dados['abertura']) ? \Carbon\Carbon::createFromFormat('d/m/Y', $dados['abertura'])->format('Y-m-d') : null,

                                'atividade_principal' => isset($dados['atividade_principal'][0]['text']) ? $dados['atividade_principal'][0]['text'] : null,

                                'data_situacao' => isset($dados['data_situacao']) ? \Carbon\Carbon::createFromFormat('d/m/Y', $dados['data_situacao'])->format('Y-m-d') : null,
                                'natureza_juridica' => $dados['natureza_juridica'] ?? null,
                                'logradouro' => $dados['logradouro'] ?? null,
                                'numero' => $dados['numero'] ?? null,
                                'complemento' => $dados['complemento'] ?? null,
                                'cep' => $dados['cep'] ?? null,
                                'bairro' => $dados['bairro'] ?? null,
                                'municipio' => $dados['municipio'] ?? null,
                                'uf' => $dados['uf'] ?? null,
                                'email' => $dados['email'] ?? null,
                                'telefone' => $dados['telefone'] ?? null,
                                'situacao' => $dados['situacao'] ?? null,
                                'capital_social' => isset($dados['capital_social']) ? (float) str_replace(['.', ','], ['', '.'], $dados['capital_social']) : null,
                                'ultima_atualizacao' => isset($dados['ultima_atualizacao']) ? \Carbon\Carbon::parse($dados['ultima_atualizacao'])->format('Y-m-d H:i:s') : null,
                            ]
                        );

                        $processedCount++;
                    }
                } catch (\Exception $e) {
                    Log::error("Erro ao validar CNPJ: {$cnpj->cnpj}", ['exception' => $e]);
                }
            }

            // Pausa para respeitar o limite de 3 consultas por minuto
            if ($processedCount < $totalCount) {
                sleep(60); // Pausa de 1 minuto
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Validação concluída! {$processedCount} CNPJs validados.",
            'processed_count' => $processedCount,
            'total_count' => $totalCount,
        ]);
    }

    public function clearPendingCnpjs()
    {
        try {
            // Deleta apenas os registros onde 'dados' está vazio ou NULL
            $deletedCount = DB::table('cnpjs')
                ->whereNull('dados')
                ->orWhere('dados', '')
                ->delete();

            return response()->json([
                'message' => "$deletedCount registros pendentes foram apagados com sucesso!"
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao apagar os CNPJs pendentes.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
