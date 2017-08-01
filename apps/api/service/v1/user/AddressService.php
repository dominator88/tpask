<?php
namespace apps\api\service\v1\user;

/**
 * 用户收获地址
 *
 * @author  Zix <zix2002@gmail.com>
 * @version 2.0 , 2016-10-13
 */

use apps\api\service\v1\ApiService;
use apps\common\service\MerUserAddressService;

class AddressService extends ApiService {
  
  //允许的请求方式
  public $allowRequestMethod = [
    'get'    => 'GET - 取用户收货地址' ,
    'post'   => 'POST - 新增用户收货地址' ,
    'put'    => 'PUT - 编辑用户收货地址' ,
    'delete' => 'DELETE - 删除用户收货地址'
  ];
  
  /**
   * 传参 如:
   * 'title' => ['标题' , '默认值' , '验证方式'] //验证方式可选
   * 'status' => ['状态' , 1 , ["0" => '禁用' , 1 => '启用'] ]
   */
  public $defaultParams = [
    'get'  => [
      'token' => [ '用户Token' , '' , PARAM_REQUIRED ] ,
    ] ,
    'post' => [
      'token'     => [ '用户Token' , '' , PARAM_REQUIRED ] ,
      'name'      => [ '收货人姓名' , '' , PARAM_REQUIRED ] ,
      'phone'     => [ '收货人手机号' , '' , PARAM_REQUIRED ] ,
      'areaId'    => [ '区域ID' , '' , PARAM_REQUIRED ] ,
      'address'   => [ '详细地址' , '' , PARAM_REQUIRED ] ,
      'isDefault' => [ '是否默认' , 1 , [ 0 => '否' , 1 => '是' ] ] ,
    ] ,
    'put'  => [
      'token'     => [ '用户Token' , '' , PARAM_REQUIRED ] ,
      'addressId' => [ '对应收货地址ID' , '' , PARAM_REQUIRED ] ,
      'name'      => [ '收货人姓名' , '' , PARAM_REQUIRED ] ,
      'phone'     => [ '收货人手机号' , '' , PARAM_REQUIRED ] ,
      'areaId'    => [ '区域ID' , '' , PARAM_REQUIRED ] ,
      'address'   => [ '详细地址' , '' , PARAM_REQUIRED ] ,
      'isDefault' => [ '是否默认' , 1 , [ 0 => '否' , 1 => '是' ] ] ,
    ] ,
    
    'delete' => [
      'token'     => [ '用户Token' , '' , PARAM_REQUIRED ] ,
      'addressId' => [ '对应收货地址ID' , '' , PARAM_REQUIRED ] ,
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
      "id"         => "收货地址ID" ,
      "user_id"    => "用户ID" ,
      "name"       => "收货人姓名" ,
      "phone"      => "收货人手机" ,
      "area_id"    => "区域ID" ,
      "area_text"  => "区域text" ,
      "address"    => "详细地址" ,
      "is_default" => "是否默认" ,
    ] ,
    'post'   => [
      "id"         => "收货地址ID" ,
      "user_id"    => "用户ID" ,
      "name"       => "收货人姓名" ,
      "phone"      => "收货人手机" ,
      "area_id"    => "区域ID" ,
      "area_text"  => "区域text" ,
      "address"    => "详细地址" ,
      "is_default" => "是否默认" ,
    ] ,
    'put'    => [
      "id"         => "收货地址ID" ,
      "user_id"    => "用户ID" ,
      "name"       => "收货人姓名" ,
      "phone"      => "收货人手机" ,
      "area_id"    => "区域ID" ,
      "area_text"  => "区域text" ,
      "address"    => "详细地址" ,
      "is_default" => "是否默认" ,
    ] ,
    'delete' => [
      "id"         => "收货地址ID" ,
      "user_id"    => "用户ID" ,
      "name"       => "收货人姓名" ,
      "phone"      => "收货人手机" ,
      "area_id"    => "区域ID" ,
      "area_text"  => "区域text" ,
      "address"    => "详细地址" ,
      "is_default" => "是否默认" ,
    ] ,
  ];
  
  private static $instance;
  
  public static function instance( $params = [] ) {
    if ( self::$instance == NULL ) {
      self::$instance         = new AddressService();
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
        $data = $this->formatData( $data );
        
        return api_result( '查询成功' , 0 , [ 'rows' => $data ] );
      case 'POST' :
        return $this->post();
      case 'PUT' :
        return $this->put();
      case 'DELETE' :
        return $this->delete();
    }
  }
  
  /**
   * get 的响应方法
   *
   * @return array|number
   */
  public function get() {
    $MerUserAddress = MerUserAddressService::instance();
    
    return $MerUserAddress->getByCond( [
      'userId' => $this->userId ,
      'getAll' => TRUE
    ] );
  }
  
  /**
   * post 的响应方法
   *
   * @return array
   */
  public function post() {
    $MerUserAddress = MerUserAddressService::instance();
    
    $insertData = [
      'user_id'    => $this->userId ,
      'name'       => $this->params['name'] ,
      'phone'      => $this->params['phone'] ,
      'area_id'    => $this->params['areaId'] ,
      'address'    => $this->params['address'] ,
      'is_default' => $this->params['isDefault'] ,
    ];
    
    $result = $MerUserAddress->insert( $insertData );
    if ( $result['code'] != 0 ) {
      return $result;
    }
    
    $data = $this->get();
    
    return api_result( '新增收货地址成功' , 0 , [ 'rows' => $data ] );
  }
  
  public function put() {
    $MerUserAddress = MerUserAddressService::instance();
    
    $updateData = [
      'user_id'    => $this->userId ,
      'name'       => $this->params['name'] ,
      'phone'      => $this->params['phone'] ,
      'area_id'    => $this->params['areaId'] ,
      'address'    => $this->params['address'] ,
      'is_default' => $this->params['isDefault'] ,
    ];
    
    $result = $MerUserAddress->update( $this->params['addressId'] , $updateData );
    if ( $result['code'] != 0 ) {
      return $result;
    }
    
    $data = $this->get();
    
    return api_result( '编辑收货地址成功' , 0 , [ 'rows' => $data ] );
  }
  
  
  public function delete() {
    $MerUserAddress = MerUserAddressService::instance();
    
    $result = $MerUserAddress->destroy( $this->params['addressId'] , $this->userId );
    if ( $result['code'] != 0 ) {
      return $result;
    }
    
    $data = $this->get();
    
    return api_result( '删除收货地址成功' , 0 , [ 'rows' => $data ] );
  }
  
}
