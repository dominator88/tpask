<?php
/**
 * MerAlbum Service
 *
 * @author Zix
 * @version 2.0 2016-06-22
 */

namespace apps\common\service;

use think\Db;

class MerAlbumService extends BaseService {
	//引入 GridTable trait
	use \apps\common\traits\service\GridTable;
	
	//状态
	var $status = [
		0 => '禁用',
		1 => '启用',
	];
	
	//类实例
	private static $instance;
	
	//生成类单例
	public static function instance() {
		if ( self::$instance == NULL ) {
			self::$instance        = new MerAlbumService();
			self::$instance->model = db( 'MerAlbum' );
		}
		
		return self::$instance;
	}
	
	//取默认值
	function getDefaultRow() {
		return [
			'id'         => '',
			'catalog_id' => '',
			'sort'       => '999',
			'uri'        => '',
			'file_size'  => '',
			'mime'       => '',
			'img_size'   => '',
			'status'     => '1',
		];
	}
	
	//根据条件查询
	function getByCond( $param ) {
		$model = db('MerAlbumTag');
		$default = [
			'field'    => [ 'a.*', 'ac.tag' ],
			'merId'    => '',
			'catalog'  => '',
			'keyword'  => '',
			'status'   => '',
			'page'     => 1,
			'pageSize' => 10,
			'sort'     => 'id',
			'order'    => 'DESC',
			'count'    => FALSE,
			'getAll'   => FALSE,
		];
		
		$param = extend( $default, $param );
		
		if ( ! empty( $param['keyword'] ) ) {
			$model->where( 'a.desc', 'like', "%{$param['keyword']}%" );
		}
		
		if ( $param['merId'] === '' ) {
			$model->where( 'a.mer_id', 'null' );
		} else {
			$model->where( 'a.mer_id', $param['merId'] );
		}
		
		if ( $param['status'] !== '' ) {
			$model->where( 'a.status', $param['status'] );
		}
		
		if ( $param['catalog'] !== '' ) {
			$model->where( 'tag.catalog_id', $param['catalog'] );
		}
		
		$model->alias( 'tag' );
		$model->join( 'mer_album a', 'a.id = tag.album_id', 'left' );
		$model->join( 'mer_album_catalog ac', 'ac.id = tag.catalog_id', 'left' );
		
		if ( $param['count'] ) {
			return $model->count();
		} else {
			$model->field( $param['field'] );

			
			if ( ! $param['getAll'] ) {
				$model->limit( ( $param['page'] - 1 ) * $param['pageSize'], $param['pageSize'] );
			}
			
			$order[] = "{$param['sort']} {$param['order']}";
			$model->order( $order );
			
			$data = $model->select();
			//echo $model->getLastSql();
		}
		
		return $data;
	}
	
	function insert( $data, $tags = '' ) {
		$MerAlbumCatalog = MerAlbumCatalogService::instance();
		Db::startTrans();
		try {
			$mer_id = isset( $data['mer_id'] ) ? $data['mer_id'] : '';
			
			if ( empty( $mer_id ) ) {
				unset( $data['mer_id'] );
			}
			$id = $this->model->insertGetId( $data );
			
			$tags     = empty( $tags ) ? '默认相册' : $tags;
			$ret_save = $MerAlbumCatalog->saveByTags( $mer_id, $tags, $id, $data['uri'] );
			
			if ( $ret_save['code'] != 0 ) {
				throw new \Exception( $ret_save['msg'] );
			}
			
			Db::commit();
			
			return ajax_arr( '添加成功', 0, [ 'id' => $id ] );
		} catch ( \Exception $e ) {
			Db::rollback();
			
			return ajax_arr( $e->getMessage(), 500 );
		}
	}
	
	//删除
	function destroy( $ids ) {
		Db::startTrans();
		try {
			$ret = $this->model->delete( $ids );
			if ( $ret > 0 ) {
				$MerAlbumTag = db( 'MerAlbumTag' );
				$MerAlbumTag->where( 'album_id', 'in', $ids )->delete();
			}
			Db::commit();
			
			return ajax_arr( '删除成功', 0 );
		} catch ( \Exception $e ) {
			Db::rollback();
			
			return ajax_arr( $e->getMessage(), 500 );
		}
		
	}
}