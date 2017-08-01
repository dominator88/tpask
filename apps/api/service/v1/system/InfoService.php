<?php namespace apps\api\service\v1\system;

/**
 * 系统信息
 *
 * @author  Zix
 * @version 2.0 2016-09-13
 */

use apps\api\service\v1\ApiService;
use apps\api\service\v1\goods\CatalogService as GoodsCatalogService;
use apps\api\service\v1\articles\CatalogService as ArticlesCatalogService;

class InfoService extends ApiService {
  
  /**
   * 允许请求的方式
   *
   * @var array
   */
  public $allowRequestMethod = [
    'get' => 'GET - 取系统信息'
  ];
  
  /**
   * 传参 如:
   * "title" => ['标题' , '默认值' ]
   * "status" => ['状态' , 1 , ["0" => '禁用' , 1 => '启用'] ]
   */
  public $defaultParams = [
    'get' => [
      'merId' => [ '机构ID' , 1 , PARAM_REQUIRED ] ,
      'type'  => [ 'app类型' , 'news' , [ 'news' => '资讯类' , 'mall' => '商城类' ] ] ,
    ]
  ];
  
  //返回结果示例
  public $defaultResponse = [
    'get' => [
      'goodsCatalog'    => [
        "id"   => "分类ID" ,
        "type" => "分类类型" ,
        "pid"  => "上级ID" ,
        "text" => "分类名称" ,
        "icon" => "图标" ,
        "desc" => "描述" ,
      ] ,
      'articlesCatalog' => [
        "id"   => "分类ID catalogId" ,
        "sort" => "排序" ,
        "text" => "分类名称" ,
      ]
    ]
  ];
  
  private static $instance;
  
  public static function instance( $params = [] ) {
    if ( self::$instance == NULL ) {
      self::$instance         = new InfoService();
      self::$instance->params = $params;
    }
    
    return self::$instance;
  }
  
  //接口响应方法
  public function response() {
    if ( ! $this->validParams() ) {
      return api_result( $this->error , 500 );
    }
    
    $data = [];
    if ( $this->params['type'] == 'news' ) {
      //文章分类
      $ArticlesCatalog = ArticlesCatalogService::instance();
      
      $data['articlesCatalog']['rows'] = $ArticlesCatalog->get( $this->params['merId'] );
    } else if ( $this->params['type'] == 'mall' ) {
      //商品分类
      $GoodsCatalog = GoodsCatalogService::instance();
      
      $data['goodsCatalog']['rows'] = $GoodsCatalog->get( $this->params['merId'] );
    }
    
    return api_result( '查询成功' , 0 , $data );
  }
  
}