<?php


namespace Module\Member\Web\Controller;


use Module\Member\Support\MemberLoginCheck;

class MemberMoneyChargeController extends MemberFrameController implements MemberLoginCheck
{
    
    private $api;

    
    public function __construct(\Module\Member\Api\Controller\MemberMoneyChargeController $api)
    {
        parent::__construct();
        $this->api = $api;
    }

    public function index()
    {
        return $this->view('memberMoneyCharge.index', [
            'pageTitle' => '钱包充值',
        ]);
    }
}
