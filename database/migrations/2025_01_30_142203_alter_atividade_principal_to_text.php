<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterAtividadePrincipalToText extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cnpj_detalhes', function (Blueprint $table) {
            $table->text('atividade_principal')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cnpj_detalhes', function (Blueprint $table) {
            $table->json('atividade_principal')->change();
        });
    }
}
