<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class MemberMemberVipDesc extends Migration
{
    
    public function up()
    {
        Schema::table('member_vip_set', function (Blueprint $table) {

            $table->string('desc', 200)->nullable()->comment('');

        });

    }

    
    public function down()
    {

    }
}
