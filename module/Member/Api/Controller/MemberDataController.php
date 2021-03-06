<?php


namespace Module\Member\Api\Controller;

use Illuminate\Routing\Controller;
use ModStart\Admin\Auth\Admin;
use ModStart\Data\FileManager;
use ModStart\Data\UeditorManager;
use Module\Member\Auth\MemberUser;
use Module\Member\Support\MemberLoginCheck;


class MemberDataController extends Controller implements MemberLoginCheck
{
    
    public function fileManager($category)
    {
        return FileManager::handle(
            $category,
            'member_upload',
            'member_upload_category',
            MemberUser::id()
        );
    }
}
