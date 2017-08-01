<?php
namespace apps\api\service\v1\article;

/**
 * 取文章点赞用户
 *
 * @author  Zix <zix2002@gmail.com>
 * @version 2.0 , 2016-09-29
 */

use apps\api\service\v1\ApiService;
use apps\common\service\MerUserLikesService;

class LikesService extends ApiService {
  
  //允许的请求方式
  public $allowRequestMethod = [
    'get' => 'GET - 取取文章点赞用户' ,
  ];
  
  /**
   * 传参 如:
   * 'title' => ['标题' , '默认值' , '验证方式'] //验证方式可选
   * 'status' => ['状态' , 1 , ["0" => '禁用' , 1 => '启用'] ]
   */
  public $defaultParams = [
    'get' => [
      'articleId' => [ '文章ID' , '' , PARAM_REQUIRED ] ,
      'token'     => [ '用户Token' , '' ] ,
    ] ,
  ];
  
  /**
   * 返回结果示例 如:
   *
   * 'user_id'     => '用户ID',
   * 'icon' => ['头像' , 'formatIcon'] , //第二个值为格式化方法
   */
  public $defaultResponse = [
    'get' => [
      "id"         => "点赞ID" ,
      "user_id"    => "用户ID" ,
      "type_id"    => "文章ID" ,
      "created_at" => "点赞时间" ,
      "nickname"   => "用户昵称" ,
      "phone"      => [ "手机号" , "formatPhone" ] ,
      "icon"       => [ "用户头像" , 'formatIcon' ]
    ]
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
    
    
    if ( ! $this->validParams() ) {
      return api_result( $this->error , 500 );
    }
    
    //处理业务
    $data = $this->get();
    $data = $this->formatData( $data );
    
    return api_result( '查询成功' , 0 , [ 'rows' => $data ] );
  }
  
  /**
   * get 的响应方法
   *
   * @return array|number
   */
  public function get() {
    $MerUserLikes = MerUserLikesService::instance();
    
    return $MerUserLikes->getByCond( [
      'type'     => 'article' ,
      'typeId'   => $this->params['articleId'] ,
      'withUser' => TRUE
    ] );
    
  }
  
}
