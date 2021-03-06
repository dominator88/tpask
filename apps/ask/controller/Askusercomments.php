<?php
namespace apps\ask\controller;

/**
 * AskUserComments Controller
 *
 * @author  Zix <zix2002@gmail.com>
 * @version 2.0 , 2016-10-12
 */


use apps\common\service\AskUserCommentsService;

class AskUserComments extends Ask {
  
  /**
   * AskUserComments constructor.
   */
  public function __construct() {
    parent::__construct();
    $this->_initClassName( __CLASS__ );
    $this->service = AskUserCommentsService::instance();
  }
  
  //页面入口
  public function index() {
    $this->_init( 'AskUserComments' );
    
    //uri
    $this->_addParam( 'uri' , [
     //   'preview' => "{$this->baseUri}askusercomments/" ,
    
    
    ] );
    
    //查询参数
    $this->_addParam( 'query' , [
      'keyword'  => input( 'get.keyword' , '' ) ,
      'status'   => input( 'get.status' , '' ) ,
      'type'     => input( 'get.type' ) ,
      'typeId'   => input( 'get.typeId' ) ,
      'page'     => input( 'get.page' , 1 ) ,
      'pageSize' => input( 'get.pageSize' , 10 ) ,
      'sort'     => input( 'get.sort' , 'id' ) ,
      'order'    => input( 'get.order' , 'DESC' )
    ] );
    
    
    //其他参数
    $this->_addParam( [
      'defaultRow' => $this->service->getDefaultRow() ,
      'status'     => $this->service->status ,
    ] );
    
    //需要引入的 css 和 js
    
    
    $this->_addJsLib( 'static/plugins/dmg-ui/TableGrid.js' );
    
    
    return $this->_displayWithLayout();
  }
  
  /**
   * 读取
   * @return \think\response\Json
   */
  public function read() {
    $config = [
      'status'   => input( 'get.status' , '' ) ,
      'keyword'  => input( 'get.keyword' , '' ) ,
      'type'     => input( 'get.type' ) ,
      'typeId'   => input( 'get.typeId' ) ,
      'page'     => input( 'get.page' , 1 ) ,
      'pageSize' => input( 'get.pageSize' , 10 ) ,
      'sort'     => input( 'get.sort' , 'id' ) ,
      'order'    => input( 'get.order' , 'DESC' )
    ];
    
    $data['rows']    = $this->service->getByCond( $config );
    $config['count'] = TRUE;
    $data['total']   = $this->service->getByCond( $config );
    
    return json( ajax_arr( '查询成功' , 0 , $data ) );
  }
  
  
}