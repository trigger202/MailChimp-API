<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMailChimpMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('MailChimpMembers', function (Blueprint $table) {
            /*TODO -- make listid and email primary key*/
            $table->increments('d');
            $table->string('list_id')->foriegn()->references('MailChimpLists')->on('id');
            $table->string('email'); /*member can subscribe to two different lists*/

            $table->string("status");
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
        Schema::dropIfExists('members');
    }
}
