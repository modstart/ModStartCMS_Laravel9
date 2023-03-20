<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMemberLoginLog extends Migration
{
    
    public function up()
    {
        Schema::create('member_login_log', function (Blueprint $table) {

            $table->bigIncrements('id');
            $table->timestamps();

            $table->bigInteger('memberUserId')->nullable()->comment('用户ID');

            
            $table->tinyInteger('deviceType')->nullable()->comment('用户名');
            $table->string('ip', 20)->nullable()->comment('用户名');
            $table->string('userAgent', 400)->nullable()->comment('用户名');
            $table->string('ipLocation', 100)->nullable()->comment('IP地址信息');

            $table->index(['memberUserId']);

        });
    }

    
    public function down()
    {

    }
}
