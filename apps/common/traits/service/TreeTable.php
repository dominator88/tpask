<?php
/**
 * TreeTable Trait for Service
 *
 * @author Zix <zix2002@gmail.com>
 * @version 1.0 @ 2016-09-08
 */

namespace apps\common\traits\service;

trait TreeTable {
	
	//取等级
	public function getLevel( $pid ) {
		if ( empty( $pid ) ) {
			return 1;
		}
		$data = $this->getById( $pid );
		if ( empty( $data ) ) {
			return 1;
		}
		
		return $data['level'] + 1;
	}
	
	/**
	 * 根据id 查询
	 *
	 * @param $id
	 *
	 * @return mixed
	 */
	public function getById( $id ) {
		return $this->model->find( $id );
	}
	
	/**
	 * 根据pid 查询
	 *
	 * @param $pid
	 *
	 * @return mixed
	 */
	public function getByPid( $pid ) {
		return $this->model->where( 'pid', $pid )->select();
	}
	
	//转换树key
	private function treeToArray( $arr, $key ) {
		$ret = [];
		foreach ( $arr as $val ) {
			if ( isset( $val[ $key ] ) ) {
				$val[ $key ] = $this->treeToArray( $val[ $key ], $key );
			}
			$ret[] = $val;
		}
		
		return $ret;
	}
	
	/**
	 *
	 * 添加数据
	 *
	 * @param $data
	 *
	 * @return array
	 */
	public function insert( $data ) {
		try {
			if ( empty( $data ) ) {
				throw new \Exception( '数据不能为空' );
			}
			
			$data['level'] = $this->getLevel( $data['pid'] );
			$id            = $this->model->insertGetId( $data );
			
			return ajax_arr( '创建成功', 0, [ 'id' => $id ] );
		} catch ( \Exception $e ) {
			return ajax_arr( $e->getMessage(), 500 );
		}
	}
	
	/**
	 * 根据ID 更新数据
	 *
	 * @param $id
	 * @param $data
	 *
	 * @return array
	 */
	public function update( $id, $data ) {
		try {
			if ( $data['pid'] == $id ) {
				throw new \Exception( '不能选自己做上级' );
			}
			
			$data['level'] = $this->getLevel( $data['pid'] );
			$rows          = $this->model->where( 'id', $id )->update( $data );
			if ( $rows == 0 ) {
				return ajax_arr( "未更新任何数据", 0 );
			}
			
			return ajax_arr( "更新成功", 0 );
		} catch ( \Exception $e ) {
			return ajax_arr( $e->getMessage(), 500 );
		}
	}
	
	/**
	 * 根据ID 删除数据
	 *
	 * @param $ids //string | array
	 *
	 * @return array
	 */
	public function destroy( $ids ) {
		try {
			//查看是否有下级数据
			$childrenData = $this->getByPid( $ids );
			if ( ! empty( $childrenData ) ) {
				throw new \Exception( '还有下级数据,不能参数' );
			}
			
			//删除数据
			$rows = $this->model->delete( $ids );
			if ( $rows == 0 ) {
				return ajax_arr( '未删除任何数据', 0 );
			}
			
			return ajax_arr( "成功删除{$rows}行数据", 0 );
		} catch ( \Exception $e ) {
			return ajax_arr( $e->getMessage(), 500 );
		}
	}
	
	
	public function getFamilyId( $param ) {
		
		$default = [
			'field'       => [ '*' ],
			'pid'         => 0 ,
			'status'      => 1,
			'childrenKey' => 'children',
			'withSelf'    => TRUE,
		];
		
		$param = extend( $default, $param );
		
		$this->model->field( $param['field'] );
		if ( $param['status'] !== '' ) {
			$this->model->where( 'status', $param['status'] );
		}
		$this->model->order( 'level ASC , sort ASC ' );
		$data = $this->model->select();
		
		
		//echo $this->model->getLastSql();
		$result = [];
		$index  = [];
		
		foreach ( $data as $row ) {
			if ( $row['pid'] == $param['pid'] ) {
				$result[ $row['id'] ] = $row;
				$index[ $row['id'] ]  = &$result[ $row['id'] ];
			} else {
				$index[ $row['pid'] ][ $param['childrenKey'] ][ $row['id'] ] = $row;
				
				$index[ $row['id'] ] = &$index[ $row['pid'] ][ $param['childrenKey'] ][ $row['id'] ];
			}
		}
		
		$idsArr = $this->_getFamilyId( $result, $param['childrenKey'] );
		if ( $param['withSelf'] ) {
			$idsArr[] = $param['pid'];
		}
		
		return $idsArr;
	}
	
	function _getFamilyId( $result, $childrenKey = 'children' ) {
		$arr = [];
		foreach ( $result as $id => $item ) {
			$arr[] = $id;
			if ( isset( $item[ $childrenKey ] ) ) {
				$arr = array_merge( $arr, $this->_getFamilyId( $item[ $childrenKey ], $childrenKey ) );
			}
		}
		
		return $arr;
	}
	
}