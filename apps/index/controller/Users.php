<?php
namespace apps\index\controller;

use apps\common\controller\IndexBase;

class Users extends IndexBase {
  
  public function __construct() {
    parent::__construct();
    
    $this->_initClassName( __CLASS__ );
  }
  
  /**
   * 用户详情
   *
   * @param int $id
   *
   * @return string
   */
  public function detail( $id = 1 ) {
    $this->_init( '用户详情' );
   
    return $this->_displayWithLayout('detail');
  }
}
