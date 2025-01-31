<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUltimaAtualizacaoToCnpjsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cnpjs', function (Blueprint $table) {
            $table->timestamp('ultima_atualizacao')->nullable();
        });
    }

    public function down()
    {
        Schema::table('cnpjs', function (Blueprint $table) {
            $table->dropColumn('ultima_atualizacao');
        });
    }
}
