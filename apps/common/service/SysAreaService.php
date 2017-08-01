<?php namespace apps\common\service;
/**
 * SysArea Service
 *
 * @author Zix
 * @version 2.0 2016-09-20
 */


class SysAreaService extends BaseService {
	
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
			self::$instance        = new SysAreaService();
			self::$instance->model = db( 'SysArea' );
		}
		
		return self::$instance;
	}
	
	//取默认值
	function getDefaultRow() {
		return [
			'id'     => '',
			'pid'    => '',
			'text'   => '',
			'tip'    => '',
			'status' => '0',
			'level'  => '0',
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
			'pid'      => '',
			'status'   => '',
			'page'     => 1,
			'pageSize' => 10,
			'sort'     => 'id',
			'order'    => 'ASC',
			'count'    => FALSE,
			'getAll'   => FALSE
		];
		
		$param = extend( $default, $param );
		
		if ( ! empty( $param['keyword'] ) ) {
			$this->model->where( 'name', 'like', "%{$param['keyword']}%" );
		}
		
		if ( $param['pid'] !== '' ) {
			$this->model->where( 'pid', $param['pid'] );
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
	
}