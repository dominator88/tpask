<?php namespace apps\common\service;
/**
 * SysMerchant Service
 *
 * @author Zix
 * @version 2.0 2016-09-13
 */


class SysMerchantService extends BaseService {
	
	//引入 GridTable trait
	use \apps\common\traits\service\GridTable;
	
	public $forTest = [
		0 => '否' ,
		1 => '是'
 	];
	
	//状态
	public $status = [
		0 => '禁用',
		1 => '启用',
	];
	
	//类实例
	private static $instance;
	
	//生成类单例
	public static function instance() {
		if ( self::$instance == NULL ) {
			self::$instance        = new SysMerchantService();
			self::$instance->model = db( 'SysMerchant' );
		}
		
		return self::$instance;
	}
	
	//取默认值
	function getDefaultRow() {
		return [
			'id'              => '',
			'sort'            => '999',
			'name'            => '',
			'icon'            => '',
			'phone'           => '',
			'contact'         => '',
			'email'           => '',
			'id_card'         => '',
			'status'          => '0',
			'area'            => '',
			'address'         => '',
			'settled_amount'  => '0.00',
			'balance'         => '0.00',
			'withdraw_amount' => '0.00',
			'create_time'     => date( 'Y-m-d H:i:s' ),
			'apply_user_id'   => '',
			'for_test'        => '0',
		];
	}
	
	/**
	 * 根据条件查询
	 *
	 * @param $param
	 *
	 * @return array|number
	 */
	function getByCond( $param ) {
		$default = [
			'field'       => [],
			'keyword'     => '',
			'status'      => '',
			'page'        => 1,
			'pageSize'    => 10,
			'sort'        => 'id',
			'order'       => 'DESC',
			'count'       => FALSE,
			'getAll'      => FALSE,
			'withSysUser' => FALSE
		];
		
		$param = extend( $default, $param );
		
		if ( ! empty( $param['keyword'] ) ) {
			$this->model->where( 'name', 'like', "%{$param['keyword']}%" );
		}
		
		if ( $param['status'] !== '' ) {
			$this->model->where( 'status', $param['status'] );
		}
		
		if ( $param['count'] ) {
			return $this->model->count();
		}
		
		$param['field'] = ['*' , 'full_area_name( area ) as full_area_name'];
		$this->model->field( $param['field'] );
		
		if ( ! $param['getAll'] ) {
			$this->model->limit( ( $param['page'] - 1 ) * $param['pageSize'], $param['pageSize'] );
		}
		
		$order[] = "{$param['sort']} {$param['order']}";
		$this->model->order( $order );
		
		$data = $this->model->select();
		
		if ( $param['withSysUser'] ) {
			$data = $this->withSysUser( $data );
		}
		
		//echo $this->model->getLastSql();
		
		return $data ? $data : [];
	}
	
	/**
	 * 查询 商户的管理用户
	 * @param $data
	 *
	 * @return mixed
	 */
	private function withSysUser( $data ) {
		if ( empty( $data ) ) {
			return $data ;
		}
		$merIds = [];
		foreach ( $data as $item ) {
			$merIds[] = $item['id'];
		}
		
		$userData = db('MerSysUser')
			->alias('msu')
			->field(['msu.*' , 'su.username' , 'su.phone'])
			->join('sys_user su' , 'su.id = msu.sys_user_id' , 'left')
			->where('msu.mer_id' , 'in' , $merIds )
			->select();
		
		$newUserData = [];
		foreach ( $userData as $item ) {
			$newUserData[$item['mer_id']][] = $item ;
		}
		
		foreach ( $data as &$row ) {
			if( isset( $newUserData[$row['id']] ) ) {
				$row['sys_user'] = $newUserData[$row['id']] ;
			} else {
				$row['sys_user'] = '';
			}
		}
		
		return $data ;
	}
	
	/**
	 * 查询测试商户
	 * @return mixed
	 */
	public function getForTest() {
		return $this->model->where( 'for_test', 1 )->select();
	}
	
	/**
	 * 根据管理员查询 商户
	 *
	 * @param $userId
	 *
	 * @return array|false|\PDOStatement|string|\think\Model
	 */
	public function getBySysUser( $userId ) {
		$data = db( 'MerSysUser' )->field( 'm.*' )
		                          ->alias( 'su' )
		                          ->where( 'su.sys_user_id', $userId )
		                          ->join( 'sys_merchant m', 'm.id = su.mer_id' )
		                          ->find();
		
		return $data ? $data : [];
	}
	
}