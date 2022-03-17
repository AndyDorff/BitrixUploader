<?php

use Aniart\BitrixUploader\Models\BitrixEntity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBXUploadEntities extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create((new BitrixEntity())->table, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('entity_id');
            $table->string('entity_type');
            $table->json('data');
            $table->string('version');
            $table->unsignedBigInteger('bagisto_id')->nullable();
            $table->timestamps();

            $table->unique(['entity_id', 'entity_type']);
            $table->index('bagisto_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists((new BitrixEntity())->table);
    }
}
