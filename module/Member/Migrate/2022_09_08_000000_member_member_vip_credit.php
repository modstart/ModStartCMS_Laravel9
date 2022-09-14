<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class MemberMemberVipCredit extends Migration
{
    
    public function up()
    {
        Schema::table('member_vip_set', function (Blueprint $table) {

            $table->tinyInteger('creditPresentEnable')->nullable()->comment('');
            $table->integer('creditPresentValue')->nullable()->comment('');

        });
    }

    
    public function down()
    {

    }
}
