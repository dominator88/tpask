<?php namespace apps\common\service;
/**
 * SysApiLog Service
 *
 * @author Zix
 * @version 2.0 2016-09-17
 */


class SysApiLogService extends BaseService {
	
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
			self::$instance        = new SysApiLogService();
			self::$instance->model = db( 'SysApiLog' );
		}
		
		return self::$instance;
	}
	
	//取默认值
	function getDefaultRow() {
		return [
			'id'         => '',
			'uri'        => '',
			'ip'         => '',
			'created_at' => date( 'Y-m-d H:i:s' ),
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
	 * 记录请求
	 * @return array
	 */
	public function log() {
		$data = [
			'uri' => request()->url( TRUE ),
			'ip'  => request()->ip( 0, TRUE )
		];
		
		//目前保存到数据库
		//目标 保存到 缓存 5分钟 报错一次数据库,需要缓存配合
		//每日凌晨处理数据,并清除旧数据
		
		return $this->insert($data);
	}
	
	/**
	 * 删除旧数据
	 */
	public function deleteOldData() {
		
	}
	
}