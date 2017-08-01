<?php
namespace apps\api\service\v1\user;

/**
 * 用户收藏
 *
 * @author  Zix <zix2002@gmail.com>
 * @version 2.0 , 2016-09-21
 */

use apps\api\service\v1\ApiService;
use apps\common\service\MerUserFavoritesService;

class FavoritesService extends ApiService {
  
  //允许的请求方式
  public $allowRequestMethod = [
    'get'  => 'GET - 取收藏' ,
    'post' => 'POST - 添加或取消收藏'
  ];
  
  /**
   * 传参 如:
   * 'title' => ['标题' , '默认值' , '验证方法']
   * 'status' => ['状态' , 1 , ["0" => '禁用' , 1 => '启用'] ]
   */
  public $defaultParams = [
    'get'  => [
      'token' => [ '用户Token' , '' , PARAM_REQUIRED ] ,
      'type'  => [ '类型' , 'article' , [ 'article' => '文章' , 'event' => '活动' ] ] ,
    ] ,
    'post' => [
      'token'  => [ '用户Token' , '' , PARAM_REQUIRED ] ,
      'type'   => [ '类型' , 'article' , [ 'article' => '文章' , 'event' => '活动' ] ] ,
      'typeId' => [ '对应ID' , '' , PARAM_POSITIVE ]
    ]
  ];
  
  /**
   * 返回结果示例 如:
   *
   * 'user_id'     => '用户ID',
   */
  public $defaultResponse = [
    'get'  => [
      "id"             => "type id" ,
      "title"          => "标题" ,
      "desc"           => "描述" ,
      "icon"           => [ "图标" , 'formatIcon' ] ,
      "created_at"     => "创建时间" ,
      "fav_id"         => "收藏ID" ,
      "fav_created_at" => "收藏时间"
    ] ,
    'post' => [] ,
  ];
  
  private static $instance;
  
  public static function instance( $params = [] ) {
    if ( self::$instance == NULL ) {
      self::$instance         = new FavoritesService();
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
    
    if ( request()->method() == 'GET' ) {
      $data = $this->getFavorites();
      $data = $this->formatData( $data );
      
      return api_result( '查询成功' , 0 , [ 'rows' => $data ] );
    }
    
    return $this->setFavorites();
  }
  
  private function getFavorites() {
    $MerUserFavorites       = MerUserFavoritesService::instance();
    $this->params['userId'] = $this->userId;
    
    return $MerUserFavorites->getByUserWithType( $this->params );
    
  }
  
  /**
   * 设置或取消用户收藏
   *
   * @return array
   */
  private function setFavorites() {
    $MerUserFavorites = MerUserFavoritesService::instance();
    
    return $MerUserFavorites->post(
      $this->params['type'] ,
      $this->params['typeId'] ,
      $this->userId
    );
  }
  
  
}
