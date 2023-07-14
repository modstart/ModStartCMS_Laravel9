<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifyCmsContentDetailTemplate extends Migration
{
    
    public function up()
    {

        Schema::table('cms_content', function (Blueprint $table) {

            $table->string('detailTemplate', 100)->nullable()->comment('');

        });

    }

    
    public function down()
    {

    }
}
