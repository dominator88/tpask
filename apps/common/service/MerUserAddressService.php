<?php namespace apps\common\service;
/**
 * MerUserAddress Service
 *
 * @author  Zix
 * @version 2.0 2016-10-13
 */


class MerUserAddressService extends BaseService {
  
  //引入 GridTable trait
  use \apps\common\traits\service\GridTable;
  
  const MAX_COUNT_PRE_USER = 5;
  
  //状态
  public $status = [
    0 => '禁用' ,
    1 => '启用' ,
  ];
  
  //类实例
  private static $instance;
  
  //生成类单例
  public static function instance() {
    if ( self::$instance == NULL ) {
      self::$instance        = new MerUserAddressService();
      self::$instance->model = db( 'MerUserAddress' );
    }
    
    return self::$instance;
  }
  
  //取默认值
  function getDefaultRow() {
    return [
      'id'         => '' ,
      'user_id'    => '' ,
      'name'       => '' ,
      'phone'      => '' ,
      'area_id'    => '0' ,
      'address'    => '' ,
      'postcode'   => '' ,
      'status'     => '1' ,
      'is_default' => '0' ,
    ];
  }
  
  /**
   * 根据id 和用户ID 查询
   *
   * @param $id
   * @param $userId
   *
   * @return mixed
   */
  public function getByIdWithUser( $id , $userId ) {
    return $this->model
      ->field( [ '*' , 'full_area_name(area_id) area_text' ] )
      ->where( 'id' , $id )
      ->where( 'user_id' , $userId )
      ->find( $id );
  }
  
  /**
   * 根据条件查询
   *
   * @param $param
   *
   * @return array|number
   */
  public function getByCond( $param ) {
    $default = [
      'field'    => [ '*' , 'full_area_name(area_id) area_text' ] ,
      'keyword'  => '' ,
      'userId'   => '' ,
      'status'   => '' ,
      'page'     => 1 ,
      'pageSize' => 10 ,
      'sort'     => 'id' ,
      'order'    => 'DESC' ,
      'count'    => FALSE ,
      'getAll'   => FALSE
    ];
    
    $param = extend( $default , $param );
    
    if ( ! empty( $param['keyword'] ) ) {
      $this->model->where( 'name' , 'like' , "%{$param['keyword']}%" );
    }
    
    if ( $param['userId'] !== '' ) {
      $this->model->where( 'user_id' , $param['userId'] );
    }
    
    if ( $param['status'] !== '' ) {
      $this->model->where( 'status' , $param['status'] );
    }
    
    if ( $param['count'] ) {
      return $this->model->count();
    }
    
    $this->model->field( $param['field'] );
    
    if ( ! $param['getAll'] ) {
      $this->model->limit( ( $param['page'] - 1 ) * $param['pageSize'] , $param['pageSize'] );
    }
    
    $order[] = "{$param['sort']} {$param['order']}";
    $this->model->order( $order );
    
    $data = $this->model->select();
    
    //echo $this->model->getLastSql();
    
    return $data ? $data : [];
  }
  
  /**
   * 新增收货地址
   *
   * @param $data
   *
   * @return array
   */
  public function insert( $data ) {
    if ( empty( $data['user_id'] ) ) {
      return ajax_arr( '请填写用户ID' , 500 );
    }
    
    if ( empty( $data['name'] ) ) {
      return ajax_arr( '请填写收货人名称' , 500 );
    }
    
    if ( empty( $data['phone'] ) ) {
      return ajax_arr( '请填写收货人手机号' , 500 );
    }
    
    if ( empty( $data['area_id'] ) ) {
      return ajax_arr( '请填写收货人区域' , 500 );
    }
    
    if ( empty( $data['address'] ) ) {
      return ajax_arr( '请填写收货人详细地址' , 500 );
    }
    
    $oldData = $this->getByCond( [
      'userId' => $data['user_id']
    ] );
    
    $oldCount = count( $oldData );
    
    if ( $oldCount >= self::MAX_COUNT_PRE_USER ) {
      return ajax_arr( '每个用户只能添加5个收货地址' , 500 );
    }
    
    if ( $oldCount == 0 ) {
      $data['is_default'] = 1;
    }
    
    try {
      $id = $this->model->insertGetId( $data );
      if ( $data['is_default'] == 1 && $oldCount > 0 ) {
        //将其他的设置为非默认
        $this->model
          ->where( 'user_id' , $data['user_id'] )
          ->where( 'id' , 'neq' , $id )
          ->update( [
            'is_default' => 0
          ] );
      }
      
      
      return ajax_arr( '添加收货地址成功' , 0 );
    } catch ( \Exception $e ) {
      return ajax_arr( $e->getMessage() , 500 );
    }
  }
  
  public function update( $id , $data ) {
    if ( empty( $data['user_id'] ) ) {
      return ajax_arr( '请填写用户ID' , 500 );
    }
    
    if ( empty( $data['name'] ) ) {
      return ajax_arr( '请填写收货人名称' , 500 );
    }
    
    if ( empty( $data['phone'] ) ) {
      return ajax_arr( '请填写收货人手机号' , 500 );
    }
    
    if ( empty( $data['area_id'] ) ) {
      return ajax_arr( '请填写收货人区域' , 500 );
    }
    
    if ( empty( $data['address'] ) ) {
      return ajax_arr( '请填写收货人详细地址' , 500 );
    }
    
    $oldData = $this->getByCond( [
      'userId' => $data['user_id']
    ] );
    
    $oldCount = count( $oldData );
    
    if ( $oldCount == 1 ) {
      $data['is_default'] = 1;
    }
    
    try {
      $this->model->where( 'id' , $id )->update( $data );
      if ( $data['is_default'] == 1 && $oldCount > 1 ) {
        //将其他的设置为非默认
        $this->model
          ->where( 'user_id' , $data['user_id'] )
          ->where( 'id' , 'neq' , $id )
          ->update( [
            'is_default' => 0
          ] );
        
      }
      
      return ajax_arr( '更新收货地址成功' , 0 );
    } catch ( \Exception $e ) {
      return ajax_arr( $e->getMessage() , 500 );
    }
  }
  
  /**
   * 删除
   *
   * @param $id
   * @param $userId
   *
   * @return array
   */
  public function destroy( $id , $userId ) {
    
    $oldData = $this->getByCond( [
      'userId' => $userId
    ] );
    
    $hasRow    = FALSE;
    $isDefault = FALSE;
    foreach ( $oldData as $item ) {
      if ( $item['id'] == $id ) {
        $hasRow = TRUE;
        if ( $item['is_default'] == 1 ) {
          $isDefault = TRUE;
        }
      }
    }
    
    if ( ! $hasRow ) {
      return ajax_arr( '记录未找到' , 500 );
    }
    
    try {
      $this->model->delete( $id );
      if ( count( $oldData ) > 1 && $isDefault ) {
        //设置一个默认地址
        $this->model
          ->where( 'user_id' , $userId )
          ->limit( 1 )
          ->update( [ 'is_default' => 1 ] );
      }
      
      return ajax_arr( '删除收货地址成功' , 0 );
    } catch ( \Exception $e ) {
      return ajax_arr( $e->getMessage() , 500 );
    }
  }
  
}