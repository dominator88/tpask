<?php namespace apps\common\service;
/**
 * AskArticlesCategory Service
 *
 * @author Zix
 * @version 2.0 2016-09-15
 */


class AskArticlesCategoryService extends BaseService {
	
	
	//引入 TreeTable trait
	use \apps\common\traits\service\TreeTable;
	
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
			self::$instance        = new AskArticlesCategoryService();
			self::$instance->model = db( 'AskArticlesCategory' );
		}
		
		return self::$instance;
	}
	
	//取默认值
	function getDefaultRow() {
		return [
			'id'         => '',
			'pid'        => '0',
			'mer_id'     => '',
			'sort'       => '99',
			'text'       => '',
			'icon'       => '',
			'desc'       => '',
			'level'      => '1',
			'status'     => '1',
			'created_at' => date( 'Y-m-d H:i:s' ),
		];
	}
	
	
	//根据条件查询
	public function getByCond( $param ) {
		$default = [
			'field'  => [],
			'merId'  => '',
			'pid'    => 0,
			'status' => '',
			'key'    => 'children'
		];
		$param   = extend( $default, $param );
		
		if ( $param['merId'] !== '' ) {
			$this->model->where( 'mer_id', $param['merId'] );
		}
		
		if ( $param['status'] !== '' ) {
			$this->model->where( 'status', $param['status'] );
		}
		
		$data = $this->model
			->field( $param['field'] )
			->order( 'level ASC , sort ASC ' )
			->select();
		
		//echo $this->model->_sql();
		
		$result = [];
		$index  = [];
		
		foreach ( $data as $row ) {
			if ( $row['pid'] == $param['pid'] ) {
				$result[ $row['id'] ] = $row;
				$index[ $row['id'] ]  = &$result[ $row['id'] ];
			} else {
				$index[ $row['pid'] ][ $param['key'] ][ $row['id'] ] = $row;
				
				$index[ $row['id'] ] = &$index[ $row['pid'] ][ $param['key'] ][ $row['id'] ];
			}
		}
		
		return $this->treeToArray( $result, $param['key'] );
	}
	
	/**
	 * 获取根分类
	 *
	 * @param $pid
	 * @param $merId
	 *
	 * @return mixed
	 */
	function getByPidWithMerId( $pid, $merId ) {
		return $this->model
			->where( 'pid', $pid )
			->where( 'mer_id', $merId )
			->where( 'status', 1 )
			->order( 'sort' )
			->select();
	}
	
}