<?php
namespace apps\api\service\v1\goods;

/**
 * 商品分类
 *
 * @author  Zix <zix2002@gmail.com>
 * @version 2.0 , 2016-10-21
 */

use apps\api\service\v1\ApiService;
use apps\common\service\MerGoodsCatalogService;

class CatalogService extends ApiService {
  
  //允许的请求方式
  public $allowRequestMethod = [
    'get' => 'GET - 取商品分类' ,
  ];
  
  /**
   * 传参 如:
   * 'title' => ['标题' , '默认值' , '验证方式'] //验证方式可选
   * 'status' => ['状态' , 1 , ["0" => '禁用' , 1 => '启用'] ]
   */
  public $defaultParams = [
    'get' => [
      'merId' => [ '机构ID' , 1 , PARAM_REQUIRED ] ,
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
      "id"   => "分类ID" ,
      "type" => "分类类型" ,
      "pid"  => "上级ID" ,
      "text" => "分类名称" ,
      "icon" => "图标" ,
      "desc" => "描述" ,
    ] ,
  ];
  
  private static $instance;
  
  public static function instance( $params = [] ) {
    if ( self::$instance == NULL ) {
      self::$instance         = new CatalogService();
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
    switch ( request()->method() ) {
      case 'GET' :
        $data = $this->get( $this->params['merId'] );
        
        return api_result( '查询成功' , 0 , [ 'rows' => $data ] );
      default :
        return api_result( '未知请求类型' , 500 );
    }
  }
  
  /**
   * get 的响应方法
   *
   * @param $merId
   *
   * @return array|mixed
   */
  public function get( $merId ) {
    $cacheName = 'DMGApp-GoodsCatalog';
    $data      = cache( $cacheName );
    
    if ( empty( $data ) ) {
      $MerGoodsCatalog = MerGoodsCatalogService::instance();
      
      $data = $MerGoodsCatalog->getByCond( [
        'merId'  => $merId ,
        'status' => 1 ,
      ] );
      $data = $this->formatData( $data );
      cache( $cacheName , $data , 3600 );
    }
    
    return $data;
  }
}
