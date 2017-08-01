<?php
/**
 * MerUserDevice Service
 *
 * @author Zix
 * @version 2.0 2016-09-13
 */

namespace apps\common\service;

class MerUserDeviceService extends BaseService {
	
	//引入 GridTable trait
	use \apps\common\traits\service\GridTable;
	
	public $device = [
		'iphone'  => 'iPhone',
		'ipad'    => 'iPad',
		'android' => '安卓',
		'pc'      => 'PC',
		'mac'     => 'MAC',
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
			self::$instance        = new MerUserDeviceService();
			self::$instance->model = db( 'MerUserDevice' );
		}
		
		return self::$instance;
	}
	
	//取默认值
	function getDefaultRow() {
		return [
			'id'                => '',
			'user_id'           => '',
			'token'             => '',
			'device'            => '',
			'device_os_version' => '',
			'app_version'       => '',
			'api_version'       => '',
			'registration_id'   => '',
			'updated_at'        => date( 'Y-m-d H:i:s' ),
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
			'field'    => [],
			'keyword'  => '',
			'status'   => '',
			'page'     => 1,
			'pageSize' => 10,
			'sort'     => 'id',
			'order'    => 'DESC',
			'count'    => FALSE,
			'getAll'   => FALSE
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
		
		$this->model->field( $param['field'] );
		
		if ( ! $param['getAll'] ) {
			$this->model->limit( ( $param['page'] - 1 ) * $param['pageSize'], $param['pageSize'] );
		}
		
		$order[] = "{$param['sort']} {$param['order']}";
		$this->model->order( $order );
		
		$data = $this->model->select();
		
		//echo $this->model->getLastSql();
		
		return $data ? $data : [];
	}
	
	/**
	 * 根据 token 取设备
	 *
	 * @param $token
	 * @param string $device //设备类型
	 * @param bool $withUser
	 *
	 * @return array
	 */
	public function getByToken( $token, $device = '', $withUser = FALSE ) {
		$this->model->alias( 'ud' );
		
		$this->model->where( 'ud.token', $token );
		if ( $device !== '' ) {
			$this->model->where( 'ud.device', $device );
		}
		
		if ( $withUser ) {
			$this->model->field( 'u.*' )
			            ->join( 'mer_user u on u.id = ud.user_id', 'left' );
		} else {
			$this->model->field( 'ud.*' );
		}
		
		$data = $this->model->find();
		
		return $data ? $data : [];
	}
	
	/**
	 * 根据 用户ID 取设备
	 *
	 * @param $userId
	 * @param string $device
	 *
	 * @return array
	 */
	public function getByUser( $userId, $device = '' ) {
		$this->model->where( 'user_id', $userId );
		if ( $device !== '' ) {
			$this->model->where( 'device', $device );
		}
		
		$data = $this->model->find();
		
		return $data ? $data : [];
	}
	
	function updateByUser( $userId, $userData ) {
		try {
			$oldData                = $this->getByUser( $userId, $userData['device'] );
			$userData['updated_at'] = date( 'Y-m-d H:i:s' );
			if ( empty( $oldData ) ) {
				//没有则添加
				$userData['user_id'] = $userId;
				$this->model->insert( $userData );
			} else {
				//有 则更新
				if ( isset( $userData['device'] ) ) {
					$this->model->where( 'device', $userData['device'] );
				}
				$this->model->where( 'user_id', $userId )->update( $userData );
			}
			
			//将 用户ID 绑定到 激光注册ID
			if ( isset( $userData['registration_id'] ) && ! empty( $userData['registration_id'] ) ) {
				$SysPush = SysPushService::instance();
				$SysPush->bindByUserId( $userData['registration_id'], $userId );
			}
			
			return ajax_arr( '成功', 0 );
		} catch ( \Exception $e ) {
			return ajax_arr( $e->getMessage(), 500 );
		}
	}
	
	
}