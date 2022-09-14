<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMemberMoneyChargeOrder extends Migration
{
    
    public function up()
    {
        Schema::create('member_money_charge_order', function (Blueprint $table) {

            $table->increments('id');
            $table->timestamps();

            $table->integer('memberUserId')->nullable()->comment('排序');
            $table->decimal('money', 20, 2)->nullable()->comment('');

            
            $table->tinyInteger('status')->nullable()->comment('默认');

            $table->index(['memberUserId']);
            $table->index(['created_at']);

        });
    }

    
    public function down()
    {

    }
}
