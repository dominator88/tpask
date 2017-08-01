<?php
namespace apps\api\service\v1\article;

/**
 * 文章详情
 *
 * @author  Zix <zix2002@gmail.com>
 * @version 2.0 , 2016-09-18
 */

use apps\api\service\v1\ApiService;
use apps\common\service\MerArticlesService;

class IndexService extends ApiService {
  
  //允许的请求方式
  public $allowRequestMethod = [
    'get' => 'GET - 取文章详情'
  ];
  
  /**
   * 传参 如:
   * "title" => ['标题' , '默认值' ]
   * "status" => ['状态' , 1 , ["0" => '禁用' , 1 => '启用'] ]
   */
  public $defaultParams = [
    'get' => [
      "merId"     => [ '商户ID' , 1 , PARAM_REQUIRED ] ,
      'articleId' => [ '文章ID' , '' , PARAM_POSITIVE ] ,
      "token"     => [ '用户Token' , '' ] ,
    ]
  ];
  
  /**
   * 返回结果示例 如:
   *
   * 'user_id'     => '用户ID',
   */
  public $defaultResponse = [
    'get' => [
      "id"          => "ID" ,
      "catalog_id"  => "分类ID" ,
      "sort"        => "排序" ,
      "title"       => "标题" ,
      "icon"        => [ "图标" , 'formatIcon' ] ,
      "status"      => "状态" ,
      "created_at"  => "创建时间" ,
      "is_favorite" => "是否已收藏" ,
      "is_like"     => "是否已点赞" ,
      'comments'    => '评论数' ,
      'likes'       => '点赞数' ,
      'pv'          => '浏览数' ,
      "uri"         => [ "访问uri" , 'formatUri' ] ,
      'shareUri'    => [ '分享uri' , 'formatShareUri' ]
    ]
  ];
  
  private static $instance;
  
  public static function instance( $params = [] ) {
    if ( self::$instance == NULL ) {
      self::$instance         = new IndexService();
      self::$instance->params = $params;
    }
    
    return self::$instance;
  }
  
  //接口响应方法
  function response() {
    if ( ! $this->validParams() ) {
      return api_result( $this->error , 500 );
    }
    
    //检验token
    $this->validToken();
    
    $MerArticles = MerArticlesService::instance();
    
    $data = $MerArticles->getByIdWithMerAndUser(
      $this->params['articleId'] ,
      $this->params['merId'] ,
      $this->userId
    );
    
    $data = $this->formatData( $data );
    $MerArticles->incPv( $this->params['articleId'] );
    
    return api_result( '查询成功' , 0 , $data );
  }
  
  
  public function formatUri( $value , $row = [] ) {
    return base_uri() . 'article/' . $row['id'] . '?from=api';
  }
  
  public function formatShareUri( $value , $row = [] ) {
    return base_uri() . 'article/' . $row['id'] . '?from=share';
  }
  
  
}
