<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CmsFeaturePageSize extends Migration
{
    public function up()
    {
        Schema::table('cms_cat', function (Blueprint $table) {
            $table->integer('pageSize')->nullable()->comment('');
        });
    }

    
    public function down()
    {

    }
}
