<?php
/**
 * SysRolePermission Service
 *
 * @author Zix
 * @version 2.0 2016-05-11
 */

namespace apps\common\service;

use think\Db;

class SysRolePermissionService extends BaseService {
	
	//引入 GridTable trait
	use \apps\common\traits\service\GridTable;
	
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
			self::$instance        = new SysRolePermissionService();
			self::$instance->model = db( 'SysRolePermission' );
		}
		
		return self::$instance;
	}
	
	//根据条件查询
	function getByCond( $param ) {
		$default = [
			'field'    => '',
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
		
		//echo $this->model->_sql();
		
		return $data ? $data : [];
	}
	
	/**
	 * 根据角色获取 权限
	 *
	 * @param $roleId
	 *
	 * @return array
	 */
	public function getPrivilegeByRole( $roleId ) {
		$data = $this->model->where( 'role_id', $roleId )->select();
		
		if ( empty( $data ) ) {
			return $data;
		}
		
		$newData = [];
		foreach ( $data as $row ) {
			$newData[] = $row['privilege_id'];
		}
		
		return $newData;
	}
	
	/**
	 * 根据角色获取 授权
	 *
	 * @param $roleId
	 *
	 * @return mixed
	 */
	function getByRole( $roleId ) {
		return $this->model->where( 'role_id', $roleId )->select();
	}
	
	/**
	 * 检查角色权限
	 *
	 * @param $roleId
	 * @param $module
	 * @param $func
	 * @param $privilege
	 *
	 * @return array|bool
	 */
	function checkRoleFuncPrivilege( $roleId, $module, $func, $privilege ) {
		if ( $roleId == config( 'superAdminId' ) ) {
			return TRUE;
		}
		
		if ( empty( $privilege ) ) {
			return FALSE;
		}
		
		$funcUri = "$module/$func/index";
		
		$data = $this->model
			->field( 'DISTINCT fp.name' )
			->alias( 'rp' )
			->where( 'rp.role_id', 'in', $roleId )
			->where( 'f.uri', $funcUri )
			->join( 'sys_func_privilege fp', 'fp.id = rp.privilege_id' )
			->join( 'sys_func f', 'f.id = fp.func_id' )
			->select();
		
//		echo $this->model->getLastSql();
		if ( empty( $data ) ) {
			return FALSE;
		}
		
		$fixData = [];
		foreach ( $data as $row ) {
			$fixData [] = $row ['name'];
		}
		//echo $privilege_name;
		if ( ! in_array( $privilege, $fixData ) ) {
			return FALSE;
		}
		
		return $fixData;
	}
	
	/**
	 * 根据功能删除
	 *
	 * @param $funcId
	 *
	 * @return array
	 */
	function destroyByFunc( $funcId ) {
		try {
			$sql = db( 'sys_func_privilege' )->where( 'func_id', $funcId )->buildSql();
			$this->model->where( 'privilege_id', 'in', $sql );
			$this->model->delete();
			
			return ajax_arr( '删除成功', 0 );
		} catch ( \Exception $e ) {
			return ajax_arr( $e->getMessage(), 500 );
		}
	}
	
	/**
	 * 更新角色授权
	 *
	 * @param $roleId
	 * @param $privilegeArr
	 *
	 * @return array
	 */
	function updateRolePermission( $roleId, $privilegeArr ) {
		Db::startTrans();
		try {
			$oldPrivilegeData = $this->getPrivilegeByRole( $roleId );

			$needAdd    = array_diff( $privilegeArr, $oldPrivilegeData );
			$needDelete = array_diff( $oldPrivilegeData, $privilegeArr );
			
			if ( ! empty( $needDelete ) ) {
				$this->model
					->where( 'role_id', $roleId )
					->where( 'privilege_id', 'in', $needDelete )
					->delete();;
			}
			if ( ! empty( $needAdd ) ) {
				$addData = [];
				foreach ( $needAdd as $privilegeId ) {
					$addData[] = [
						'role_id'      => $roleId,
						'privilege_id' => $privilegeId
					];
				}
				$this->model->insertAll( $addData );
			}
			
			Db::commit();
			
			return ajax_arr( '修改权限成功了', 0 );
		} catch ( \Exception $e ) {
			Db::rollback();
			
			return ajax_arr( $e->getMessage() . '--- here', 500 );
		}
	}
	
}