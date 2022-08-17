<?php

use Illuminate\Database\Migrations\Migration;
use ModStart\Core\Dao\ModelUtil;

class UpgradeMemberUploadCategoryId extends Migration
{
    
    public function up()
    {
        ModelUtil::update('member_upload', [
            'uploadCategoryId' => 0,
        ], [
            'uploadCategoryId' => -1,
        ]);
    }

    
    public function down()
    {
    }
}
