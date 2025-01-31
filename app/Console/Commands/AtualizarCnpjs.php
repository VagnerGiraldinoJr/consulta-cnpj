<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cnpj;
use App\Models\CnpjDetalhes;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;


class AtualizarCnpjs extends Command
{
    // Nome do comando
    protected $signature = 'atualizar:cnpjs';

    // Descrição do comando
    protected $description = 'Consulta CNPJs na API e salva os resultados na tabela de detalhes';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Limite da API (3 consultas por minuto)
        $limite_consultas = 3;
        $tempo_entre_lotes = 60; // Segundos

        // Seleciona CNPJs que ainda não foram processados
        $cnpjs = Cnpj::whereNull('ultima_atualizacao')->take($limite_consultas)->get();

        if ($cnpjs->isEmpty()) {
            $this->info('Nenhum CNPJ pendente para atualizar.');
            return;
        }

        $this->info('Iniciando atualização de CNPJs...');

        foreach ($cnpjs as $cnpj) {
            $url = "https://receitaws.com.br/v1/cnpj/{$cnpj->cnpj}";

            try {
                $response = Http::get($url);

                if ($response->successful()) {
                    $dados = $response->json();

                    // Salva os dados na tabela de detalhes
                    CnpjDetalhes::updateOrCreate(
                        ['cnpj' => $dados['cnpj']],
                        [
                            'tipo' => $dados['tipo'],
                            'porte' => $dados['porte'],
                            'nome' => $dados['nome'],
                            'fantasia' => $dados['fantasia'],
                            'abertura' => $dados['abertura'],

                            'atividade_principal' => isset($dados['atividade_principal'][0]['text']) ? $dados['atividade_principal'][0]['text'] : null,

                            'atividades_secundarias' => json_encode($dados['atividades_secundarias']),
                            'natureza_juridica' => $dados['natureza_juridica'],
                            'logradouro' => $dados['logradouro'],
                            'numero' => $dados['numero'],
                            'complemento' => $dados['complemento'],
                            'cep' => $dados['cep'],
                            'bairro' => $dados['bairro'],
                            'municipio' => $dados['municipio'],
                            'uf' => $dados['uf'],
                            'email' => $dados['email'],
                            'telefone' => $dados['telefone'],
                            'situacao' => $dados['situacao'],
                            'data_situacao' => $dados['data_situacao'],
                            'capital_social' => $dados['capital_social'],
                            'qsa' => json_encode($dados['qsa']),
                            'simples' => json_encode($dados['simples']),
                            'simei' => json_encode($dados['simei']),
                            'ultima_atualizacao' => Carbon::now(),
                        ]
                    );

                    // Marca o CNPJ como atualizado
                    $cnpj->ultima_atualizacao = Carbon::now();
                    $cnpj->save();

                    $this->info("CNPJ {$cnpj->cnpj} atualizado com sucesso.");
                } else {
                    $this->error("Erro ao consultar CNPJ {$cnpj->cnpj}: {$response->status()}");
                }
            } catch (\Exception $e) {
                $this->error("Erro ao consultar CNPJ {$cnpj->cnpj}: {$e->getMessage()}");
            }

            // Respeita o limite de consultas
            sleep(intval($tempo_entre_lotes / $limite_consultas));
        }

        $this->info('Atualização de CNPJs concluída.');
    }
}
