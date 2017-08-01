<?php
/**
 * SysRole Service
 *
 * @author Zix
 * @version 2.0 2016-05-09
 */

namespace apps\common\service;

class SysRoleService extends BaseService {
	
	//引入 GridTable trait
	use \apps\common\traits\service\GridTable;
	
	
	//状态
	var $status = [
		0 => '禁用',
		1 => '启用',
	];
	
	var $rank = [
		1  => '1级',
		2  => '2级',
		3  => '3级',
		4  => '4级',
		5  => '5级',
		6  => '6级',
		7  => '7级',
		8  => '8级',
		9  => '9级',
		10 => '10级',
	];
	
	var $mp_rank = [
		1 => '1级',
		2 => '2级',
		3 => '3级',
		4 => '4级',
		5 => '5级',
	];
	//类实例
	private static $instance;
	
	//生成类单例
	public static function instance() {
		if ( self::$instance == NULL ) {
			self::$instance        = new SysRoleService();
			self::$instance->model = db( 'SysRole' );
		}
		
		return self::$instance;
	}
	
	//取默认值
	function getDefaultRow() {
		return [
			'id'     => '',
			'sort'   => '99',
			'type'   => 'backend',
			'mer_id' => '0',
			'name'   => '',
			'status' => '1',
			'desc'   => '',
			'expand' => '',
			'rank'   => '1',
		];
	}
	
	//根据条件查询
	function getByCond( $param ) {
		$default = [
			'field'    => '',
			'keyword'  => '',
			'status'   => '',
			'module'   => 'backend',
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
		
		$this->model->where( 'rank', 'lt', 10 );
		if ( $param['module'] !== '' ) {
			$this->model->where( 'module', $param['module'] );
		}
		
		if ( $param['status'] !== '' ) {
			$this->model->where( 'status', $param['status'] );
		}
		
		
		if ( $param['count'] ) {
			return $this->model->count();
		} else {
			$this->model->field( $param['field'] );
			
			if ( $param['getAll'] === FALSE ) {
				$this->model->limit( ( $param['page'] - 1 ) * $param['pageSize'], $param['pageSize'] );
			}
			
			$order[] = "{$param['sort']} {$param['order']}";
			$this->model->order( $order );
			
			return $this->model->select();
			//echo $this->model->getLastSql();
		}
	}
	
	/**
	 * 根据模块获取角色
	 *
	 * @param $module
	 *
	 * @return mixed
	 */
	function getByModule( $module ) {
		
		$data = $this->model
			->where( 'id', 'neq', config( 'superAdminId' ) )
			->where( 'module', $module )
			->order( 'rank desc' )
			->select();
		
		//echo $this->model->getLastSql();
		return $data ;
	}
}