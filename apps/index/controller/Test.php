<?php
namespace apps\index\controller;

use apps\common\controller\IndexBase;
use apps\common\model\EcmMember;
use apps\common\model\EcmUser;


use think\queue\Queue;

class Test extends IndexBase {

    public function __construct() {
        parent::__construct();

        $this->_initClassName( __CLASS__ );
    }

    public function index() {
        $this->_init( '首页' );
        $user = EcmMember::get(1);
       echo $user->profile->token;

    //    return $this->_displayWithLayout( 'index' , 'public/home_layout' );
    }
}