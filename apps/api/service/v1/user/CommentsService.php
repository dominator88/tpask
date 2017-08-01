<?php
namespace apps\api\service\v1\user;

/**
 * 用户评论
 *
 * @author  Zix <zix2002@gmail.com>
 * @version 2.0 , 2016-09-27
 */

use apps\api\service\v1\ApiService;
use apps\common\service\MerUserCommentsService;

class CommentsService extends ApiService {
  
  //允许的请求方式
  public $allowRequestMethod = [
    'get'  => 'GET - 取用户评论' ,
    'post' => 'POST - 写用户评论'
  ];
  
  /**
   * 传参 如:
   * 'title' => ['标题' , '默认值' , '验证方式'] //验证方式可选
   * 'status' => ['状态' , 1 , ["0" => '禁用' , 1 => '启用'] ]
   */
  public $defaultParams = [
    'get'  => [
      'token' => [ '用户Token' , '' , PARAM_REQUIRED ] ,
      'type'  => [ '类型' , 'article' , [ 'article' => '文章' , 'goods' => '商品' , 'event' => '活动' , ] ]
    ] ,
    'post' => [
      'token'   => [ '用户Token' , '' , PARAM_REQUIRED ] ,
      'type'    => [ '类型' , 'article' , [ 'article' => '文章' , 'goods' => '商品' , 'event' => '活动' , ] ] ,
      'typeId'  => [ '类型对应ID' , '' , PARAM_REQUIRED ] ,
      'content' => [ '评论内容' , '' , PARAM_REQUIRED ]
    ]
  
  ];
  
  /**
   * 返回结果示例 如:
   *
   * 'user_id'     => '用户ID',
   * 'icon' => ['头像' , 'formatIcon'] , //第二个值为格式化方法
   */
  public $defaultResponse = [
    'get'  => [
      "id"         => "ID" ,
      "user_id"    => "用户ID" ,
      "type"       => "评论类型" ,
      "type_id"    => "类型对应ID" ,
      "content"    => "内容" ,
      "reply"      => "回复" ,
      "status"     => "状态" ,
      "created_at" => "评论时间" ,
      "nickname"   => "用户昵称" ,
      "phone"      => [ "用户手机号" , 'formatPhone' ] ,
      "icon"       => [ "用户头像" , 'formatIcon' ]
    ] ,
    'post' => []
  ];
  
  private static $instance;
  
  public static function instance( $params = [] ) {
    if ( self::$instance == NULL ) {
      self::$instance         = new CommentsService();
      self::$instance->params = $params;
    }
    
    return self::$instance;
  }
  
  //接口响应方法
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
    }
    
    
    return $this->post();
  }
  
  private function get() {
    $MerUserComments        = MerUserCommentsService::instance();
    $this->params['userId'] = $this->userId;
    
    return $MerUserComments->getByCond( [
      'userId' => $this->userId ,
      'type'   => $this->params['type'] ,
    ] );
  }
  
  private function post() {
    $MerUserComments = MerUserCommentsService::instance();
    
    return $MerUserComments->post(
      $this->params['type'] ,
      $this->params['typeId'] ,
      $this->userId ,
      $this->params['content']
    );
  }
  
}
