<?php
namespace apps\api\service\v1\articles;

/**
 * 文章分类
 *
 * @author  Zix <zix2002@gmail.com>
 * @version 2.0 , 2016-09-15
 */

use apps\api\service\v1\ApiService;
use apps\common\service\MerArticlesCatalogService;

class CatalogService extends ApiService {
  
  //允许的请求方式
  public $allowRequestMethod = [
    'get' => 'GET - 取文章分类'
  ];
  
  /**
   * 传参 如:
   * "title" => ['标题' , '默认值' ]
   * "status" => ['状态' , 1 , ["0" => '禁用' , 1 => '启用'] ]
   */
  public $defaultParams = [
    'get' => [
      'merId' => [ '商户ID' , '' , PARAM_REQUIRED ] ,
    ]
  ];
  
  /**
   * 返回结果示例 如:
   *
   * 'user_id'     => '用户ID',
   */
  public $defaultResponse = [
    'get' => [
      "id"   => "分类ID catalogId" ,
      "sort" => "排序" ,
      "text" => "分类名称" ,
    ]
  ];
  
  private static $instance;
  
  public static function instance( $params = [] ) {
    if ( self::$instance == NULL ) {
      self::$instance         = new CatalogService();
      self::$instance->params = $params;
    }
    
    return self::$instance;
  }
  
  //接口响应方法
  function response() {
    if ( ! $this->validParams() ) {
      return api_result( $this->error , 500 );
    }
    
    $data = $this->get( $this->params['merId'] );
    
    return api_result( '查询成功' , 0 , [ 'rows' => $data ] );
  }
  
  function get( $merId ) {
    $cacheName = 'DMGApp-ArticlesCatalog';
    $data      = cache( $cacheName );
    if ( empty( $data ) ) {
      $MerArticlesCatalog = MerArticlesCatalogService::instance();
      
      $data = $MerArticlesCatalog->getByPidWithMerId( 0 , $merId );
      $data = $this->formatData( $data );
      cache( $cacheName , $data , 3600 );
    }
    
    return $data;
  }
  
}
