<?php
namespace apps\index\controller;

use apps\common\controller\IndexBase;


use apps\common\service\AskCategoryService;
use think\queue\Queue;

class Category extends IndexBase {

    public function __construct() {
        parent::__construct();

        $this->_initClassName( __CLASS__ );
    }

    public function index() {

        $param = [
            'status' => 1,
            'getAll' => true,
        ];
        $AskCategory = AskCategoryService::instance();
        $data = $AskCategory->getByCond($param);
        return json( ajax_arr( '查询成功', 0 , $data ));
    }


}