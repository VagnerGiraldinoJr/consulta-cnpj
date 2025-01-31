<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCnpjDetalhesTable extends Migration
{
    public function up()
    {
        Schema::create('cnpj_detalhes', function (Blueprint $table) {
            $table->id();
            $table->string('cnpj', 20)->unique(); // Limitando a 20 caracteres para CNPJ formatado
            $table->string('tipo', 50)->nullable();
            $table->string('porte', 50)->nullable();
            $table->string('nome', 255)->nullable();
            $table->string('fantasia', 255)->nullable();
            $table->date('abertura')->nullable(); // Mudando para tipo `date` para armazenar a data corretamente
            $table->json('atividade_principal')->nullable();
            $table->json('atividades_secundarias')->nullable();
            $table->string('natureza_juridica', 255)->nullable();
            $table->string('logradouro', 255)->nullable();
            $table->string('numero', 50)->nullable();
            $table->string('complemento', 255)->nullable();
            $table->string('cep', 20)->nullable();
            $table->string('bairro', 100)->nullable();
            $table->string('municipio', 100)->nullable();
            $table->string('uf', 10)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('telefone', 50)->nullable();
            $table->string('situacao', 50)->nullable();
            $table->date('data_situacao')->nullable(); // Mudando para tipo `date`
            $table->decimal('capital_social', 15, 2)->nullable(); // Usando tipo decimal para valores monetários
            $table->json('qsa')->nullable();
            $table->json('simples')->nullable();
            $table->json('simei')->nullable();
            $table->timestamp('ultima_atualizacao')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cnpj_detalhes');
    }
}
