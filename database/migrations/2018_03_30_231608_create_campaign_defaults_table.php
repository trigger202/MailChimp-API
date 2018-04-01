<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCampaignDefaultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_defaults', function (Blueprint $table) {
            $table->increments('id');
            $table->string('list_id')->unique()->forign()->references('MailChimpLists')->on('id')->onDelete('cascade');
            $table->string('from_name');
            $table->string('from_email');
            $table->string('subject');
            $table->string('language');
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
        Schema::dropIfExists('campaign_defaults');
    }
}
