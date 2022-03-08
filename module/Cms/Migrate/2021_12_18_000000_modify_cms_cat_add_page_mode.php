<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use ModStart\Core\Dao\ModelUtil;
use Module\Cms\Type\CmsMode;

class ModifyCmsCatAddPageMode extends Migration
{
    
    public function up()
    {
        Schema::table('cms_model', function (Blueprint $table) {

            
            $table->tinyInteger('mode')->nullable()->comment('');
            $table->string('pageTemplate', 100)->nullable()->comment('');
            $table->string('formTemplate', 100)->nullable()->comment('');

        });
        Schema::table('cms_cat', function (Blueprint $table) {

            $table->string('pageTemplate', 100)->nullable()->comment('');
            $table->string('formTemplate', 100)->nullable()->comment('');

        });

        ModelUtil::updateAll('cms_model', [
            'mode' => CmsMode::LIST_DETAIL,
        ]);

    }

    
    public function down()
    {

    }
}
