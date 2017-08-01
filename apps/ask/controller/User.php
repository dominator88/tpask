<?php  namespace apps\ask\controller;

/**
 * MerUser Controller
 *
 * @author  Zix <zix2002@gmail.com>
 * @version 2.0 , 2016-09-16
 */


use apps\common\service\MerUserAddressService;
use apps\common\service\AskUserService;

class User extends Ask {
  
  /**
   * MerUser constructor.
   */
  public function __construct() {
    parent::__construct();
    $this->_initClassName( __CLASS__ );
    $this->service = AskUserService::instance();
  }
  
  //页面入口
  public function index() {
    $this->_init( '用户管理' );
    
    //uri
    $this->_addParam( 'uri' , [
      //user
      'resetPwd'       => full_uri( 'ask/user/reset_pwd' , [ 'id' => '' ] ) ,
      //address
      'readAddress'    => full_uri( 'ask/meruseraddress/read' , [ 'userId' => '' ] ) ,
      'insertAddress'  => full_uri( 'ask/meruseraddress/insert' ) ,
      'updateAddress'  => full_uri( 'ask/meruseraddress/update' , [ 'id' => '' ] ) ,
      'destroyAddress' => full_uri( 'ask/meruseraddress/destroy' ) ,
      
      'upload'       => full_uri( 'ask/user/upload' ) ,
      'albumCatalog' => full_uri( 'ask/user/read_album_catalog' ) ,
      'album'        => full_uri( 'ask/user/read_album' ) ,
      'area'         => full_uri( 'ask/user/read_area' , [ 'pid' => '' ] ) ,
    ] );
    
    //查询参数
    $this->_addParam( 'query' , [
      'keyword'  => input( 'get.keyword' , '' ) ,
      'status'   => input( 'get.status' , '' ) ,
      'page'     => input( 'get.page' , 1 ) ,
      'pageSize' => input( 'get.pageSize' , 10 ) ,
      'sort'     => input( 'get.sort' , 'id' ) ,
      'order'    => input( 'get.order' , 'DESC' ) ,
    ] );
    
    //收货地址
    $this->_addParam( 'queryAddress' , [
      'userId'   => input( 'get.userId' , '' ) ,
      'page'     => input( 'get.page' , 1 ) ,
      'pageSize' => input( 'get.pageSize' , 10 ) ,
      'sort'     => input( 'get.sort' , 'id' ) ,
      'order'    => input( 'get.order' , 'DESC' ) ,
    ] );
    
    //上传参数
    $this->_addParam( 'uploadParam' , [

      'width'       => 300 ,
      'height'      => 300 ,
      'saveAsAlbum' => TRUE ,
      'albumTag'    => '头像' ,
    ] );
    
    //相册参数
    $this->_addParam( 'albumParam' , [

      'defaultTag' => '头像' ,
      'pageSize'   => 12 ,
    ] );
    
    //其他参数
    $MerUserAddress = MerUserAddressService::instance();
    $this->_addParam( [
      'defaultRow'        => $this->service->getDefaultRow() ,
      'addressDefaultRow' => $MerUserAddress->getDefaultRow() ,
      'status'            => $this->service->status ,
      'regFrom'           => $this->service->regFrom ,
      'sex'               => $this->service->sex ,
      'resetPwd'          => config( 'defaultPwd' )
    ] );
    
    //需要引入的 css 和 js
    $this->_addCssLib( 'node_modules/jcrop-0.9.12/css/jquery.Jcrop.min.css' );
    $this->_addJsLib( 'node_modules/jcrop-0.9.12/js/jquery.Jcrop.min.js' );
    $this->_addJsLib( 'static/plugins/dmg-ui/Uploader.js' );
    $this->_addJsLib( 'static/plugins/dmg-ui/AreaSelection.js' );
    $this->_addJsLib( 'static/plugins/dmg-ui/TableGrid.js' );
    
    return $this->_displayWithLayout();
  }
  
  
  /**
   * 读取
   * @return \think\response\Json
   */
  function read() {
    $config = [

      'status'   => input( 'get.status' , '' ) ,
      'keyword'  => input( 'get.keyword' , '' ) ,
      'page'     => input( 'get.page' , 1 ) ,
      'pageSize' => input( 'get.pageSize' , 10 ) ,
      'sort'     => input( 'get.sort' , 'id' ) ,
      'order'    => input( 'get.order' , 'DESC' ) ,
    ];
    
    $data['rows']    = $this->service->getByCond( $config );
    $config['count'] = TRUE;
    $data['total']   = $this->service->getByCond( $config );
    
    return json( ajax_arr( '查询成功' , 0 , $data ) );
  }
  
  function insert() {
    $data = input( 'post.' );
    

    $result         = $this->service->insert( $data );
    
    return json( $result );
  }
  
  function update() {
    $id   = input( 'get.id' );
    $data = input( 'post.' );
    

    $result         = $this->service->update( $id , $data );
    
    return json( $result );
  }
  
  function reset_pwd() {
    $id = input( 'get.id' );
    
    $result = $this->service->resetPwd( $id , config( 'defaultPwd' ) );
    
    return json( $result );
    
  }
  
}