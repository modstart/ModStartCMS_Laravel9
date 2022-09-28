<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifyMemberUserRegisterIp extends Migration
{
    
    public function up()
    {
        Schema::table('member_user', function (Blueprint $table) {
            $table->string('registerIp', 20)->nullable()->comment('注册IP');
        });
    }

    
    public function down()
    {

    }
}
