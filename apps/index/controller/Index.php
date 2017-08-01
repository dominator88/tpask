<?php
namespace apps\index\controller;

use apps\common\controller\IndexBase;
use apps\common\service\MerArticlesCatalogService;
use apps\common\service\MerTagsService;

use think\queue\Queue;

class Index extends IndexBase {
  
  public function __construct() {
    parent::__construct();
    
    $this->_initClassName( __CLASS__ );
  }
  
  public function index() {
    $this->_init( '首页' );
    
    
    return $this->_displayWithLayout( 'index' , 'public/home_layout' );
  }
}
