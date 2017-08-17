<?php
namespace apps\ask\controller;

/**
 * Answers Controller
 *
 * @author  MRZ <zsh2088@gmail.com>
 * @version 2.0 , 2017-07-20
 */



use apps\common\service\MerUserCommentsService;
use apps\common\service\AskAnswersService;

class Answers extends Ask {
  
  /**
   * Answers constructor.
   */
  public  function __construct() {
    parent::__construct();
    $this->_initClassName( __CLASS__ );
    $this->service = AskAnswersService::instance();
  }
  
  //页面入口
  public function index() {
    $this->_init( '回答管理' );
    
    //uri
    $this->_addParam( 'uri' , [
      'preview'      => "{$this->baseUri}question/" ,
      'readComment'  => full_uri( 'ask/answers/read_comment' ) ,
      'setComment'   => full_uri( 'ask/answers/set_comment' ) ,
      'albumCatalog' => full_uri( 'ask/answers/read_album_catalog' ) ,
      'album'        => full_uri( 'ask/answers/read_album' ) ,
      'upload'       => full_uri( 'ask/answers/upload' ) ,

    ] );
    
    //查询参数
    $this->_addParam( 'query' , [
      'keyword'   => input( 'get.keyword' , '' ) ,
      'catalogId' => input( 'get.catalogId' , '' ) ,
      'status'    => input( 'get.status' , '' ) ,
      'page'      => input( 'get.page' , 1 ) ,
      'pageSize'  => input( 'get.pageSize' , 10 ) ,
      'sort'      => input( 'get.sort' , 'id' ) ,
      'order'     => input( 'get.order' , 'DESC' ) ,
    ] );
    
    $this->_addParam( 'commentQuery' , [
      'page'     => 1 ,
      'pageSize' => 10 ,
    ] );
    
    //上传参数
    $this->_addParam( 'uploadParam' , [
      'width'       => 480 ,
      'height'      => 250 ,
      'saveAsAlbum' => TRUE ,
      'albumTag'    => '文章' ,

    ] );
    
    //相册参数
    $this->_addParam( 'albumParam' , [
      'defaultTag' => '文章' ,
      'pageSize'   => 12 ,
    ] );
    
    $this->_addParam( 'contentParam' , [
      'saveAsAlbum' => FALSE ,
    ] );
    

    $MerUserComments    = MerUserCommentsService::instance();
    $this->_addParam( [
      'defaultRow'    => $this->service->getDefaultRow() ,
      'status'        => $this->service->status ,
      'commentStatus' => $MerUserComments->status ,

    ] );
    
    //需要引入的 css 和 js
    
    $this->_addCssLib( 'node_modules/bootstrap-datetime-picker/css/bootstrap-datetimepicker.min.css' );
    $this->_addJsLib( 'node_modules/bootstrap-datetime-picker/js/bootstrap-datetimepicker.min.js' );
    $this->_addJsLib( 'node_modules/bootstrap-datetime-picker/js/locales/bootstrap-datetimepicker.zh-CN.js' );
    
    $this->_addCssLib( 'node_modules/jcrop-0.9.12/css/jquery.Jcrop.min.css' );
    $this->_addJsLib( 'node_modules/jcrop-0.9.12/js/jquery.Jcrop.min.js' );
    $this->_addJsLib( 'static/plugins/dmg-ui/Uploader.js' );
    //$this->_addJsLib( 'node_modules/kindeditor/kindeditor-all-min.js' );
    //$this->_addJsLib( 'node_modules/kindeditor/lang/zh-CN.js' );
    $this->_addCssLib( 'node_modules/simplemde/dist/simplemde.min.css' );
    $this->_addJsLib( 'node_modules/simplemde/dist/simplemde.min.js' );
    $this->_addJsLib( 'static/plugins/dmg-ui/TableGrid.js' );
    
    
    return $this->_displayWithLayout();
  }
  
  /**
   * 读取
   * @return \think\response\Json
   */
  public function read() {
    $config = [
      'catalogId' => input( 'get.catalogId' , '' ) ,
      'status'    => input( 'get.status' , '' ) ,
      'keyword'   => input( 'get.keyword' , '' ) ,
      'page'      => input( 'get.page' , 1 ) ,
      'pageSize'  => input( 'get.pageSize' , 10 ) ,
      'sort'      => input( 'get.sort' , 'id' ) ,
      'order'     => input( 'get.order' , 'DESC' ) ,
        'withQuestion' => TRUE,
    ];

    $data['rows']    = $this->service->getByCond( $config );
    $config['count'] = TRUE;
    $data['total']   = $this->service->getByCond( $config );
    
    return json( ajax_arr( '查询成功' , 0 , $data ) );
  }
  
  public function insert() {
    $data = input( 'post.' );
    
    if ( empty( $data['start_at'] ) ) {
      unset( $data['start_at'] );
    }
    
    if ( empty( $data['end_at'] ) ) {
      unset( $data['end_at'] );
    }

    $result         = $this->service->insert( $data );

    
    return json( $result );
  }
  
  public function update() {
    $id   = input( 'get.id' );
    $data = input( 'post.' );
    
    if ( empty( $data['start_at'] ) ) {
      unset( $data['start_at'] );
    }
    
    if ( empty( $data['end_at'] ) ) {
      unset( $data['end_at'] );
    }
    
    $result = $this->service->update( $id , $data );



    return json( $result );
  }
  
  /**
   * 取文章评论
   *
   * @return \think\response\Json
   */
  public function read_comment() {
    $params = input( 'get.' );
    
    $params['type']  = 'article';
    $MerUserComments = MerUserCommentsService::instance();
    
    $data['rows']    = $MerUserComments->getByCond( $params );
    $params['count'] = TRUE;
    $data['total']   = $MerUserComments->getByCond( $params );
    
    return json( ajax_arr( '加载评论成功' , 0 , $data ) );
  }
  
  /**
   * 设置评论审核或取消
   *
   * @return \think\response\Json
   */
  public function set_comment() {
    $id     = input( 'get.id' );
    $status = input( 'get.status' , 0 );
    
    $MerUserComments = MerUserCommentsService::instance();
    $result          = $MerUserComments->setStatus( $id , $status );
    
    return json( $result );
  }
  
}