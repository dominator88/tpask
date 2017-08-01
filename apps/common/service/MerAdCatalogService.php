<?php namespace apps\common\service;
/**
 * MerAdCatalog Service
 *
 * @author Zix
 * @version 2.0 2016-09-16
 */


class MerAdCatalogService extends BaseService {
	
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
			self::$instance        = new MerAdCatalogService();
			self::$instance->model = db( 'MerAdCatalog' );
		}
		
		return self::$instance;
	}
	
	//取默认值
	function getDefaultRow() {
		return [
			'id'         => '',
			'mer_id'     => '',
			'text'       => '',
			'width'      => '0',
			'height'     => '0',
			'status'     => '1',
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
			'field'        => [],
			'merId'        => '',
			'keyword'      => '',
			'status'       => '',
			'page'         => 1,
			'pageSize'     => 10,
			'sort'         => 'id',
			'order'        => 'DESC',
			'count'        => FALSE,
			'getAll'       => FALSE,
			'textWithSize' => FALSE,
		];
		
		$param = extend( $default, $param );
		
		if ( ! empty( $param['keyword'] ) ) {
			$this->model->where( 'text', 'like', "%{$param['keyword']}%" );
		}
		
		if ( $param['merId'] !== '' ) {
			$this->model->where( 'mer_id', $param['merId'] );
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
		
		if ( $param['textWithSize'] ) {
			$data = $this->textWithSize( $data );
		}
		return $data ? $data : [];
	}
	
	private function textWithSize( $data ) {
		foreach ( $data as &$item ) {
			$item['text'] .= "({$item['width']}*{$item['height']})";
		}
		return $data;
	}
	
}