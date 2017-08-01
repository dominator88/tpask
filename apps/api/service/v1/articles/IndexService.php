<?php
namespace apps\api\service\v1\articles;

/**
 * 文章列表
 *
 * @author  Zix <zix2002@gmail.com>
 * @version 2.0 , 2016-09-14
 */

use apps\api\service\v1\ApiService;
use apps\common\service\MerArticlesService;

class IndexService extends ApiService {
  
  //允许的请求方式
  public $allowRequestMethod = [
    'get' => 'GET - 取文章列表'
  ];
  
  /**
   * 传参 如:
   * "title" => ['标题' , '默认值' ]
   * "status" => ['状态' , 1 , ["0" => '禁用' , 1 => '启用'] ]
   */
  public $defaultParams = [
    'get' => [
      "token"     => [ '用户Token' , '' ] ,
      "merId"     => [ '机构ID' , 1 , PARAM_REQUIRED ] ,
      "catalogId" => [ '分类ID' , '1' ] ,
      "page"      => [ '页码' , '1' , PARAM_POSITIVE ] ,
      "pageSize"  => [ '每页行数' , '6' , PARAM_POSITIVE ] ,
    ]
  ];
  
  /**
   * 返回结果示例 如:
   *
   * 'user_id'     => '用户ID',
   */
  public $defaultResponse = [
    'get' => [
      "id"           => 'ID' ,
      "catalog_id"   => "分类ID" ,
      "sort"         => "排序" ,
      "title"        => "标题" ,
      "icon"         => [ "图标" , 'formatIcon' ] ,
      "status"       => '状态' ,
      "created_at"   => "创建日期" ,
      "catalog_text" => "分类名称" ,
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
    
    $MerArticles                    = MerArticlesService::instance();
    $this->params['withoutContent'] = TRUE;
    $this->params['status']         = 1;
    $data                           = $MerArticles->getByCond( $this->params );
    $data                           = $this->formatData( $data );
    
    return api_result( '查询成功' , 0 , [ 'rows' => $data ] );
  }
  
}
