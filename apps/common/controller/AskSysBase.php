<?php
/**
 * Sys 控制器基类
 *
 * @author  Zix <zix2002@gmail.com>
 * @version 2.0 @ 2016-09-08
 */

namespace apps\common\controller;

use apps\common\service\AskAlbumCatalogService;
use apps\common\service\MerAlbumService;
use apps\common\service\SysAreaService;
use apps\common\service\UploadService;
use think\View;

class AskSysBase {
  
  public $user       = NULL;

  public $baseUri    = '';
  public $module     = ''; //功能 不分大小写
  public $controller = ''; //控制器
  public $className  = ''; //控制器 区分大小写
  public $action     = ''; //操作
  public $service    = NULL;
  
  public $data = [
    'pageTitle'  => '' , //页面title
    'jsLib'      => [] , //自定义js uri
    'cssLib'     => [] , //自定义css uri
    'param'      => [   //页面需要用到的参数
      'uri' => [] ,
    ] ,
    'initPageJs' => TRUE , //是否加载本页面 js
    'jsCode'     => [ //其余js代码
      //"Layout.setSidebarMenuActiveLink('match');"
    ] ,
  ];
  
  public function __construct() {
    $request          = request();
    $this->baseUri    = $request->domain() . '/';
    $this->module     = $request->module();
    $this->controller = $request->controller();
    $this->action     = $request->action();
  }
  
  /**
   * 初始化页面
   *
   * @param string $pageTitle
   */
  public function _init( $pageTitle = '新页面' ) {
    
    $currentBaseUri = "{$this->baseUri}{$this->module}/{$this->controller}/";
    
    $this->data['param']['pageTitle'] = $pageTitle;
    $this->data['param']['uri']       = [
      'base'    => $this->baseUri ,
      'module'  => "{$this->baseUri}{$this->module}/index/index" ,
      'img'     => config( 'custom.imgUri' ) ,
      'menu'    => "" ,
      'this'    => full_uri( $currentBaseUri . $this->action ) ,
      'chPwd'   => full_uri( "{$this->baseUri}{$this->module}/auth/changePassword" ) ,
      'read'    => full_uri( $currentBaseUri . 'read' ) ,
      'insert'  => full_uri( $currentBaseUri . 'insert' ) ,
      'update'  => full_uri( $currentBaseUri . 'update' , [ 'id' => '' ] ) ,
      'destroy' => full_uri( $currentBaseUri . 'destroy' ) ,
    ];
  }
  
  /**
   * 取实际类型名
   *
   * @param $className
   */
  public function _initClassName( $className ) {
    $classNameArr    = explode( '\\' , $className );
    $this->className = $classNameArr[ count( $classNameArr ) - 1 ];
  }
  
  /**
   * 生成页面js uri
   *
   * @return string
   */
  public function _getPageJsPath() {
    //$js_file_name = substr( preg_replace( '/[A-Z]/', '_\0', $this->className ), 1 );
    return "static/js/{$this->module}/{$this->className}.js";
  }
  
  /**
   * 添加自定义js library
   *
   * @param $uri
   */
  public function _addJsLib( $uri ) {
    $this->data['jsLib'][] = $uri;
  }
  
  public function _addJsCode( $code ) {
    $this->data['jsCode'][] = $code;
  }
  
  /**
   * 添加自定义css library
   *
   * @param $uri
   */
  public function _addCssLib( $uri ) {
    $this->data['cssLib'][] = $uri;
  }
  
  /**
   * 添加页面所需的参数
   *
   * @param $key
   * @param $value
   */
  public function _addParam( $key , $value = '' ) {
    if ( is_array( $key ) ) {
      foreach ( $key as $k => $v ) {
        $this->data['param'][ $k ] = $v;
      }
    } else {
      if ( is_array( $value ) ) {
        if ( isset( $this->data['param'][ $key ] ) ) {
          $this->data['param'][ $key ] = array_merge( $this->data['param'][ $key ] , $value );
        } else {
          $this->data['param'][ $key ] = $value;
        }
      } else {
        $this->data['param'][ $key ] = $value;
      }
    }
  }
  
  public function _addData( $key , $value = '' ) {
    if ( is_array( $key ) ) {
      foreach ( $key as $k => $v ) {
        $this->data[ $k ] = $v;
      }
    } else {
      if ( is_array( $value ) ) {
        if ( isset( $this->data[ $key ] ) ) {
          $this->data[ $key ] = array_merge( $this->data[ $key ] , $value );
        } else {
          $this->data[ $key ] = $value;
        }
      } else {
        $this->data[ $key ] = $value;
      }
    }
  }
  
  /**
   * 生成自定义js html代码
   *
   * @return string
   */
  public function _makeJs() {
    $html = [];
    
    //引用页面JS文件
    if ( $this->data['initPageJs'] ) {
      $this->data['jsLib'][]  = $this->_getPageJsPath();
      $this->data['jsCode'][] = $this->className . '.init();';
    }
    foreach ( $this->data['jsLib'] as $item ) {
      $html[] = '<script src="' . $item . '" type="text/javascript"></script>';
    }
    
    $html[] = '<script type="text/javascript">';
    $html[] = 'var Param = ' . json_encode( $this->data['param'] );
    $html[] = '$(function(){';
    
    foreach ( $this->data['jsCode'] as $row ) {
      $html[] = $row;
    }
    
    $html[] = '});';
    $html[] = '</script>';
    
    return join( "\n" , $html );
  }
  
  /**
   * 生成自定义 css 代码
   * @return string
   */
  public function _makeCss() {
    $html = [];
    foreach ( $this->data['cssLib'] as $item ) {
      $html[] = '<link href="' . $item . '" rel="stylesheet">';
    }
    
    return join( "\n" , $html );
  }
  
  
  /**
   * 404 页面
   *
   * @return View
   */
  public function _empty() {
    $this->_init( '页面未找到' );
    
    return view( 'public/404' , $this->data );
  }
  
  /**
   * 文件上传
   *
   * @return \think\response\Json
   */
  public function upload() {
    $param          = input( 'post.' );
    $param['isKE']  = input( 'get.isKE' , 0 );
    $Upload         = UploadService::instance();
    
    return json( $Upload->doUpload( $param ) );
  }
  
  /**
   * 取相册列表
   *
   * @return \think\response\Json
   */
  public function read_album() {
    $MerAlbum = MerAlbumService::instance();
    
    $config = [
      'field'    => [ 'a.id' , 'a.uri' , 'a.mimes' , 'a.desc' , 'a.img_size' ] ,
      'catalog'  => input( 'get.catalog' , '' ) ,
      'sort'     => 'id' ,
      'order'    => 'DESC' ,
      'status'   => 1 ,
      'page'     => input( 'get.page' , 1 ) ,
      'pageSize' => input( 'get.pageSize' , 12 ) ,
    ];
    
    $result['rows']  = $MerAlbum->getByCond( $config );
    $config['count'] = TRUE;
    $result['total'] = $MerAlbum->getByCond( $config );
    
    exit( json( $result )->send() );
  }
  
  /**
   * 取相册分类
   *
   * @return \think\response\Json
   */
  public function read_album_catalog() {
    $AskAlbumCatalog = AskAlbumCatalogService::instance();
    $result          = $AskAlbumCatalog->getByCond( [
      'field'  => [ 'id' , 'tag' ] ,
      'sort'   => 'sort' ,
      'order'  => 'ASC' ,
      'status' => 1 ,
      'getAll' => TRUE
    ] );
    
    exit( json( $result )->send() );
  }
  
  /**
   * 取区域信息
   * @return \think\response\Json
   */
  public function read_area() {
    $SysArea = SysAreaService::instance();
    
    $pid       = input( 'get.pid' , 0 );
    $cacheName = config( 'custom.areaCachePrefix' ) . $pid;
    
    $data = cache( $cacheName );
    if ( empty( $data ) ) {
      $data = $SysArea->getByCond( [
        'pid'    => input( 'get.pid' , 0 ) ,
        'getAll' => TRUE
      ] );
      cache( $cacheName , $data , 86400 );
    }
    
    return json( ajax_arr( '查询成功' , 0 , $data ) );
  }
  
  /**
   * 新建
   *
   * @return \think\response\Json
   */
  public function insert() {
    $data = input( 'post.' );
    
    return json( $this->service->insert( $data ) );
  }
  
  /**
   * 更新
   *
   * @return \think\response\Json
   */
  public function update() {
    $id   = input( 'get.id' );
    $data = input( 'post.' );
    
    return json( $this->service->update( $id , $data ) );
  }
  
  /**
   * 删除
   * @return \think\response\Json
   */
  public function destroy() {
    $data = input( 'post.' );
    
    return json( $this->service->destroy( $data['ids'] ) );
  }
}