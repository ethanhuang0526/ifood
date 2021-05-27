<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestaurantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('名稱');
            $table->text('categories')->nullable()->comment('類別');
            $table->string('image')->comment('圖片');
            $table->string('address')->comment('地址');
            $table->decimal('score', 5, 2)->comment('平均分數');
            $table->text('min_price')->comment('最低價格');
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
        Schema::dropIfExists('restaurants');
    }
}
