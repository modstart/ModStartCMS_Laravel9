<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class MemberMemberVipVisible extends Migration
{
    
    public function up()
    {
        Schema::table('member_vip_set', function (Blueprint $table) {

            $table->tinyInteger('visible')->nullable()->comment('');

        });

        \ModStart\Core\Dao\ModelUtil::updateAll('member_vip_set', ['visible' => true]);

    }

    
    public function down()
    {

    }
}
