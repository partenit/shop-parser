<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('shop_id')->unsigned();
            $table->string('code', 30)->comment('Артикул');
            $table->string('name')->comment('Название товара');
            $table->string('url')->comment('URL страницы товара');
            $table->text('description')->nullable()->comment('Описание товара');
            $table->tinyInteger('is_available')->comment('Наличие товара, 1 - есть, 0 - нет');
            $table->index('code');
            $table->index('name');
            $table->index('shop_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
};
