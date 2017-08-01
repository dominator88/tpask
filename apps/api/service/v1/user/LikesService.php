<?php
namespace apps\api\service\v1\user;

/**
 * 用户点赞
 *
 * @author  Zix <zix2002@gmail.com>
 * @version 2.0 , 2016-09-27
 */

use apps\api\service\v1\ApiService;
use apps\common\service\MerUserLikesService;

class LikesService extends ApiService {
  
  //允许的请求方式
  public $allowRequestMethod = [
    'get'    => 'GET - 取用户点赞' ,
    'post'   => 'POST - 设置用户点赞' ,
    'delete' => 'DELETE - 取消用户点赞'
  ];
  
  /**
   * 传参 如:
   * 'title' => ['标题' , '默认值' , '验证方式'] //验证方式可选
   * 'status' => ['状态' , 1 , ["0" => '禁用' , 1 => '启用'] ]
   */
  public $defaultParams = [
    'get'    => [
      'token' => [ '用户Token' , '' , PARAM_REQUIRED ] ,
      'type'  => [ '类型' , 'article' , [ 'article' => '文章' , 'goods' => '商品' , 'event' => '活动' , ] ]
    ] ,
    'post'   => [
      'token'  => [ '用户Token' , '' , PARAM_REQUIRED ] ,
      'type'   => [ '类型' , 'article' , [ 'article' => '文章' , 'goods' => '商品' , 'event' => '活动' , ] ] ,
      'typeId' => [ '类型对应ID' , '' , PARAM_REQUIRED ]
    ] ,
    'delete' => [
      'token'  => [ '用户Token' , '' , PARAM_REQUIRED ] ,
      'type'   => [ '类型' , 'article' , [ 'article' => '文章' , 'goods' => '商品' , 'event' => '活动' , ] ] ,
      'typeId' => [ '类型对应ID' , '' , PARAM_REQUIRED ]
    ]
  ];
  
  /**
   * 返回结果示例 如:
   *
   * 'user_id'     => '用户ID',
   * 'icon' => ['头像' , 'formatIcon'] , //第二个值为格式化方法
   */
  public $defaultResponse = [
    'get'    => [] ,
    'post'   => [] ,
    'delete' => []
  ];
  
  private static $instance;
  
  public static function instance( $params = [] ) {
    if ( self::$instance == NULL ) {
      self::$instance         = new LikesService();
      self::$instance->params = $params;
    }
    
    return self::$instance;
  }
  
  /**
   * 接口响应方法
   *
   * @return array
   */
  public function response() {
    //验证用户
    if ( ! $this->validToken() ) {
      return api_result( $this->error , $this->errCode );
    }
    
    if ( ! $this->validParams() ) {
      return api_result( $this->error , 500 );
    }
    
    //处理业务
    if ( request()->method() == 'GET' ) {
      $data = $this->get();
      $data = $this->formatData( $data );
      
      return api_result( '查询成功' , 0 , [ 'rows' => $data ] );
    } elseif ( request()->method() == 'POST' ) {
      return $this->post();
    } else {
      return $this->delete();
    }
    
    
  }
  
  /**
   * get 的响应方法
   *
   * @return array|number
   */
  public function get() {
    $MerUserLikes = MerUserLikesService::instance();
    
    return $MerUserLikes->getByCond( [
      'userId' => $this->userId ,
      'type'   => $this->params['type'] ,
    ] );
  }
  
  /**
   * post 的响应方法
   *
   * @return array
   */
  public function post() {
    $MerUserLikes = MerUserLikesService::instance();
    
    return $MerUserLikes->post(
      $this->params['type'] ,
      $this->params['typeId'] ,
      $this->userId
    );
  }
  
  public function delete() {
    $MerUserLikes = MerUserLikesService::instance();
    
    return $MerUserLikes->delete(
      $this->userId ,
      $this->params['type'] ,
      $this->params['typeId']
    );
  }
  
}
