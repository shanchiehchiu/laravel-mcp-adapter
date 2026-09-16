<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 先跑 `php artisan make:migration create_example_items_table --create=example_items`
 * 建立帶正確日期前綴的檔名，再把這支內容貼進去。只是給
 * ExampleMcpTools::listExampleItems() 一張真的資料表可以查，接上真正資料
 * 時整支砍掉。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('example_items', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('example_items');
    }
};
