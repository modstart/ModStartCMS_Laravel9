<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMemberDataStatistic extends Migration
{
    
    public function up()
    {

        Schema::create('member_data_statistic', function (Blueprint $table) {

            $table->bigIncrements('id');
            $table->timestamps();

            $table->bigInteger('sizeLimit')->nullable()->comment('');
            $table->bigInteger('sizeUsed')->nullable()->comment('');
        });

    }

    
    public function down()
    {
    }
}
