<?php namespace apps\api\controller;

/**
 * 接口响应函数
 *
 * @author  Zix
 * @version 2.0 2016-09-13
 */


use apps\api\service\v1\ApiService;
use think\Env;

class Index {
  
  public $api = NULL;
  
  public function __construct() {
    $this->api        = ApiService::instance();
    $this->api->debug = FALSE;
  }
  
  public function index( $version , $directory , $action = 'index' ) {

    //取 http 头
    $header = [
      'timestamp'       => request()->header( 'timestamp' ) ,
      'signature'       => request()->header( 'signature' ) ,
      'device'          => request()->header( 'device' ) ,
      'deviceOsVersion' => request()->header( 'device-os-version' ) ,
      'appVersion'      => request()->header( 'app-version' ) ,
      'apiVersion'      => $version ,
    ];
    
    //取api
    $api = $this->api;
    $api->logStat( $header );
    $api->log( 'headerData' , $header );
    
    // 检查时间戳
    if ( ! $this->api->validTimestamp( $header['timestamp'] ) ) {
      exit( json( $api->getError( 405 ) )->send() );
    }
    $this->api->log( 'request ' , request()->method() );
    
    // 取参数
    $params = input( strtolower( request()->method() ) . '.' );
    $api->log( 'params' , $params );
    
    //取时间戳
    $params['timestamp'] = $header['timestamp'];

    //检查签名
    if ( ! $this->api->validSignature( $params , $header['signature'] ) ) {
      exit( json( $api->getError( 406 ) )->send() );
    }
    
    //合并参数
    $params = array_merge( $params , $header );
    $this->api->log( 'params' , $params );
    
    // 参数错误
    if ( ! is_array( $params ) || empty( $params ) ) {
      exit( json( $api->getError( 400 ) )->send() );
    }
    
    $result = $this->response( $version , $directory , $action , $params );
    $api->log( '请求结束' );
    
    return json( $result );
  }
  
  /**
   * 响应辅助函数
   *
   * @param $version
   * @param $directory
   * @param $action
   * @param $params
   *
   * @return array
   */
  private function response( $version , $directory , $action , $params ) {
    
    $action  = ucfirst( $action );
    $version = strtolower( $version );
    $class   = '\\apps\\api\\service\\' . $version . '\\' . $directory . '\\' . $action . 'Service';
    $this->api->log( 'service file' , $class );

    //检查是否存在响应文件
    if ( ! class_exists( $class ) ) {
      return $this->api->getError( 404 );
    }

    //初始化响应类
    $instance = $class::instance( $params );
    //检查请求方式
    if ( ! $this->checkRequestMethod( $instance->allowRequestMethod ) ) {
      return $this->api->getError( 408 );
    }

      return $instance->response();
  }
  
  /**
   * 检查 请求方式是否允许
   *
   * @param array $allowRequestMethod
   *
   * @return bool
   */
  private function checkRequestMethod( $allowRequestMethod = [] ) {
    $requestMethod = strtolower( request()->method() );
    if ( empty( $allowRequestMethod ) ) {
      return FALSE;
    }
    
    return isset( $allowRequestMethod[ $requestMethod ] );
  }
}
