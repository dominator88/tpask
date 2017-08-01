<?php
namespace apps\api\service\v1\goods;

/**
 * 商品
 *
 * @author  Zix <zix2002@gmail.com>
 * @version 2.0 , 2016-10-13
 */

use apps\api\service\v1\ApiService;
use apps\common\service\MerGoodsService;

class IndexService extends ApiService {
  
  //允许的请求方式
  public $allowRequestMethod = [
    'get' => 'GET - 取商品' ,
  ];
  
  /**
   * 传参 如:
   * 'title' => ['标题' , '默认值' , '验证方式'] //验证方式可选
   * 'status' => ['状态' , 1 , ["0" => '禁用' , 1 => '启用'] ]
   */
  public $defaultParams = [
    'get' => [
      'merId'     => [ '商户ID' , 1 , PARAM_REQUIRED ] ,
      'token'     => [ '用户Token' , '' , PARAM_REQUIRED ] ,
      'keyword'   => [ '用户Token' , '' ] ,
      'type'      => [ '商品类型' , '' ] ,
      'catalogId' => [ '商品分类' , '' ] ,
      'currency'  => [ '货币类型' , 'cny' , [ 'cny' => '人民币' , 'points' => '积分' ] ] ,
      'page'      => [ '页码' , 1 , PARAM_REQUIRED ] ,
      'pageSize'  => [ '每页行数' , 6 , PARAM_REQUIRED ] ,
    ]
  ];
  
  /**
   * 返回结果示例 如:
   *
   * 'user_id'     => '用户ID',
   * 'icon' => ['头像' , 'formatIcon'] , //第二个值为格式化方法
   */
  public $defaultResponse = [
    'get' => [
      "id"           => "商品ID" ,
      "mer_id"       => "机构ID" ,
      "name"         => "商品名称" ,
      "highlight"    => "亮点" ,
      "icon"         => [ '图标' , 'formatIcon' ] ,
      "desc"         => "描述" ,
      "start_time"   => "销售开始时间" ,
      "end_time"     => "销售结束时间" ,
      "currency"     => "货币" ,
      "price_market" => "市场价" ,
      "price"        => "销售价" ,
      "points"       => "可获积分" ,
      "status"       => "状态" ,
      "recommend"    => "是否推荐" ,
      "hot"          => "是否热卖" ,
      "cheap"        => "是否优惠" ,
      "sales"        => "销售数" ,
      "comments"     => "评论数" ,
      "pv"           => "浏览量" ,
      "catalog_id"   => "分类ID" ,
      "catalog_text" => "分类名称" ,
      "catalog_type" => "分类类型"
    ] ,
  ];
  
  private static $instance;
  
  public static function instance( $params = [] ) {
    if ( self::$instance == NULL ) {
      self::$instance         = new IndexService();
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
      case 'GET':
        $data = $this->get();
        
        return api_result( '查询成功' , 0 , [ 'rows' => $this->formatData( $data ) ] );
      default:
        return api_result( '未知请求' , 500 );
    }
  }
  
  /**
   * get 的响应方法
   *
   * @return array|number
   */
  public function get() {
    $MerGoods = MerGoodsService::instance();
    
    return $MerGoods->getByCond( [
      'merId'     => $this->params['merId'] ,
      'type'      => $this->params['type'] ,
      'keyword'   => $this->params['keyword'] ,
      'catalogId' => $this->params['catalogId'] ,
      'currency'  => $this->params['currency'] ,
      'page'      => $this->params['page'] ,
      'pageSize'  => $this->params['pageSize'] ,
    ] );
    
  }
  
  
  /**
   * 转换 接口数据 Goods 参数 到 数组 , 键值为 goodsId
   *
   * @param $goodsString
   *
   * @return array
   */
  public function transGoods( $goodsString ) {
    $goodsArr = json_decode( $goodsString , TRUE );
    
    $newGoods = [];
    foreach ( $goodsArr as $item ) {
      if ( empty( $item['id'] ) || empty( $item['qty'] ) ) {
        continue;
      }
      
      $newGoods[ $item['id'] ] = $item['qty'];
    }
    
    return $newGoods;
  }
  
  /**
   * 检查并取商品数据
   *
   * @param $goods
   *
   * @return array
   */
  public function checkGoods( $goods ) {
    $ids = [];
    
    foreach ( $goods as $id => $qty ) {
      $ids[] = $id;
      
      if ( $qty > 10 ) {
        return ajax_arr( '商品不能超过 10 件' , 500 );
      }
    }
    
    $MerGoods = MerGoodsService::instance();
    $data     = $MerGoods->getByCond( [
      'ids'    => $ids ,
      'status' => 1
    ] );
    
    return ajax_arr( '查询成功' , 0 , [ 'rows' => $data ] );
  }
  
  
}
