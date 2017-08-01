<?php
namespace apps\api\service\v1\user;

/**
 * 用户购物车
 *
 * @author  Zix <zix2002@gmail.com>
 * @version 2.0 , 2016-10-19
 */

use apps\api\service\v1\ApiService;
use apps\common\service\MerUserCartService;

class CartService extends ApiService {
  
  //允许的请求方式
  public $allowRequestMethod = [
    'get'    => 'GET - 取用户购物车' ,
    'post'   => 'POST - 添加商品到购物车' ,
    'put'    => 'PUT - 删除购物车商品' ,
    'delete' => 'DELETE - 清空购物车' ,
  ];
  
  /**
   * 传参 如:
   * 'title' => ['标题' , '默认值' , '验证方式'] //验证方式可选
   * 'status' => ['状态' , 1 , ["0" => '禁用' , 1 => '启用'] ]
   */
  public $defaultParams = [
    'get'    => [
      'token' => [ '用户Token' , '' , PARAM_REQUIRED ] ,
      'merId' => [ '机构ID' , 1 , PARAM_REQUIRED ]
    ] ,
    'post'   => [
      'token' => [ '用户Token' , '' , PARAM_REQUIRED ] ,
      'merId' => [ '机构ID' , 1 , PARAM_REQUIRED ] ,
      'items' => [ '商品' , [ 'id' => '商品ID' , 'qty' => "数量" ] , 'array' ]
    ] ,
    'put'    => [
      'token' => [ '用户Token' , '' , PARAM_REQUIRED ] ,
      'merId' => [ '机构ID' , 1 , PARAM_REQUIRED ] ,
      'items' => [ '购物车ID(json字符串)' , [ 'id' => '购物车ID' ] , 'array' ]
    ] ,
    'delete' => [
      'token' => [ '用户Token' , '' , PARAM_REQUIRED ] ,
      'merId' => [ '机构ID' , 1 , PARAM_REQUIRED ] ,
    ]
  
  ];
  
  /**
   * 返回结果示例 如:
   *
   * 'user_id'     => '用户ID',
   * 'icon' => ['头像' , 'formatIcon'] , //第二个值为格式化方法
   */
  public $defaultResponse = [
    'get'    => [
      "id"             => "购物车ID" ,
      "mer_id"         => "机构ID" ,
      "user_id"        => "用户ID" ,
      "goods_id"       => "商品ID" ,
      "goods_name"     => "商品名称" ,
      "goods_icon"     => [ "商品图片" , 'formatIcon' ] ,
      "qty"            => "数量" ,
      "price"          => "单价" ,
      "sub_total"      => "小计" ,
      "current_price"  => "最新价格" ,
      "current_status" => '商品当前状态' ,
      "need_update"    => [ '是否需要更新' , 'formatNeedUpdate' ]
    ] ,
    'post'   => [
      "id"             => "购物车ID" ,
      "mer_id"         => "机构ID" ,
      "user_id"        => "用户ID" ,
      "goods_id"       => "商品ID" ,
      "goods_name"     => "商品名称" ,
      "goods_icon"     => [ "商品图片" , 'formatIcon' ] ,
      "qty"            => "数量" ,
      "price"          => "单价" ,
      "sub_total"      => "小计" ,
      "current_price"  => "最新价格" ,
      "current_status" => '商品当前状态' ,
      "need_update"    => [ '是否需要更新' , 'formatNeedUpdate' ]
    ] ,
    'put'    => [
      "id"             => "购物车ID" ,
      "mer_id"         => "机构ID" ,
      "user_id"        => "用户ID" ,
      "goods_id"       => "商品ID" ,
      "goods_name"     => "商品名称" ,
      "goods_icon"     => [ "商品图片" , 'formatIcon' ] ,
      "qty"            => "数量" ,
      "price"          => "单价" ,
      "sub_total"      => "小计" ,
      "current_price"  => "最新价格" ,
      "current_status" => '商品当前状态' ,
      "need_update"    => [ '是否需要更新' , 'formatNeedUpdate' ]
    ] ,
    'delete' => [
      "id"             => "购物车ID" ,
      "mer_id"         => "机构ID" ,
      "user_id"        => "用户ID" ,
      "goods_id"       => "商品ID" ,
      "goods_name"     => "商品名称" ,
      "goods_icon"     => [ "商品图片" , 'formatIcon' ] ,
      "qty"            => "数量" ,
      "price"          => "单价" ,
      "sub_total"      => "小计" ,
      "current_price"  => "最新价格" ,
      "current_status" => '商品当前状态' ,
      "need_update"    => [ '是否需要更新' , 'formatNeedUpdate' ]
    ]
  ];
  
  private static $instance;
  
  public static function instance( $params = [] ) {
    if ( self::$instance == NULL ) {
      self::$instance         = new CartService();
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
    switch ( request()->method() ) {
      case 'GET' :
        $data = $this->get();
        
        return api_result( '查询成功' , 0 , [ 'rows' => $data ] );
      case 'POST' :
        return $this->post();
      case 'PUT' :
        return $this->put();
      case 'DELETE' :
        return $this->delete();
      default:
        return ajax_arr( '未知请求' , 500 );
    }
  }
  
  /**
   * get 的响应方法
   *
   * @return array|number
   */
  public function get() {
    $MerUserCart = MerUserCartService::instance();
    
    $data = $MerUserCart->getByCond( [
      'merId'            => $this->params['merId'] ,
      'userId'           => $this->userId ,
      'withCurrentPrice' => TRUE
    ] );

//    $goodsIds = [];
//    foreach ( $data as $item ) {
//      $goodsIds[] = $item['goods_id'];
//    }
    
    return $this->formatData( $data );
  }
  
  /**
   * post 的响应方法
   *
   * @return array
   */
  public function post() {
    try {
      //检查 items
      $items = json_decode( $this->params['items'] , TRUE );
      
      $newItems = [];
      foreach ( $items as $item ) {
        if ( ! empty( $item['id'] ) && ! empty( $item['qty'] ) ) {
          $newItems[ $item['id'] ] = $item['qty'];
        }
      }
      if ( empty( $newItems ) ) {
        return ajax_arr( '请填写商品' , 500 );
      }
      
      $MerUserCart = MerUserCartService::instance();
      $result      = $MerUserCart->insert( [
        'merId'  => $this->params['merId'] ,
        'userId' => $this->userId ,
        'items'  => $newItems
      ] );
      
      if ( $result['code'] != 0 ) {
        return $result;
      }
      
      $data = $this->get();
      
      return api_result( '添加商品到购物车成功' , 0 , [ 'rows' => $data ] );
    } catch ( \Exception $e ) {
      return ajax_arr( $e->getMessage() , 500 );
    }
  }
  
  /**
   * 删除购物车商品
   *
   * @return array
   */
  public function put() {
    $items    = json_decode( $this->params['items'] , TRUE );
    $newItems = [];
    foreach ( $items as $item ) {
      if ( ! empty( $item['id'] ) ) {
        $newItems[] = $item['id'];
      }
    }
    
    if ( empty( $newItems ) ) {
      return ajax_arr( '请填写购物车ID' , 500 );
    }
    
    $MerUserCart = MerUserCartService::instance();
    $result      = $MerUserCart->delete( $newItems , $this->params['merId'] , $this->userId );
    if ( $result['code'] != 0 ) {
      return $result;
    }
    $data = $this->get();
    
    return api_result( '删除购物车商品成功' , 0 , [ 'rows' => $data ] );
  }
  
  /**
   * 清空购物车
   *
   * @return array
   */
  public function delete() {
    $MerUserCart = MerUserCartService::instance();
    $result      = $MerUserCart->destroy( $this->params['merId'] , $this->userId );
    if ( $result['code'] != 0 ) {
      return $result;
    }
    
    return api_result( '清空购物车成功' , 0 , [ 'rows' => [] ] );
  }
  
  public function formatNeedUpdate( $value , $row = [] ) {
    if ( $row['current_status'] != 1 ) {
      return TRUE;
    }
    
    return $row['current_price'] != $row['price'];
  }
  
}
