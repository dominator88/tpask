<?php
namespace apps\common\service;
use think\image\Exception;

/**
 * AskAlbumCatalog Service
 *
 * @author Zix
 * @version 2.0 2016-09-09
 */

class AskAlbumCatalogService extends BaseService {
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
			self::$instance        = new AskAlbumCatalogService();
			self::$instance->model = db( 'AskAlbumCatalog' );
		}
		
		return self::$instance;
	}
	
	//取默认值
	function getDefaultRow() {
		return [
			'id'         => '',
			'sort'       => '999',
			'text'       => '',
			'icon'       => '',
			'is_default' => 0,
		];
	}
	
	//根据条件查询
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
			'getAll'   => FALSE,
		];
		
		$param = extend( $default, $param );
		
		if ( ! empty( $param['keyword'] ) ) {
			$this->model->where('name' , 'like' , "%{$param['keyword']}%" );
		}
		

		
		if ( $param['count'] ) {
			return $this->model->count();
		} else {
			$this->model->field( $param['field'] );
			
			if ( ! $param['getAll'] ) {
				$this->model->limit( ( $param['page'] - 1 ) * $param['pageSize'], $param['pageSize'] );
			}
			
			$order[] = "{$param['sort']} {$param['order']}";
			$this->model->order( $order );
			
			$data = $this->model->select();
			//echo $this->model->getLastSql();
		}
		
		return $data ? $data : [];
	}
	
	function getByTag(  $tag, $icon = '', $create_when_not_found = FALSE ) {
		$where['tag'] = $tag;

		
		$data = $this->model->where( $where )->find();
		
		if ( empty( $data ) ) {
			if ( $create_when_not_found ) {
				$new_data = [
					'tag'    => $tag,
					'icon'   => $icon
				];
				

				
				$ret_create = $this->insert( $new_data );
				if ( $ret_create['code'] == 0 ) {
					$new_data['id'] = $ret_create['data']['id'];
					
					return $new_data;
				}
			}
			
			return FALSE;
		}
		
		return $data;
	}
	
	//根据根据多个tag 取分类
	function saveByTags(   $tags, $album_id, $icon ) {
		if ( ! is_array( $tags ) ) {
			$tags = explode( ',', trim( $tags ) );
		}
		
		$new_tag = [];
		foreach ( $tags as $tag ) {
			$tag_data = $this->getByTag(  $tag, $icon, TRUE );
			if ( ! $tag_data ) {
				return ajax_arr( '系统繁忙, 请稍后再试', 500 );
			}
			
			$new_tag[] = [
				'album_id'   => $album_id,
				'catalog_id' => $tag_data['id']
			];
		}
		
		if ( empty( $new_tag ) ) {
			return ajax_arr( '没有要添加的数据', 500 );
		}
		
		$MerAlbumTag = db( 'MerAlbumTag' );
		$ret         = $MerAlbumTag->insertAll( $new_tag );
		
		if ( $ret === FALSE ) {
			return ajax_arr( '系统繁忙, 请稍后再试', 500 );
		} else {
			return ajax_arr( '保存成功', 0 );
		}
	}
	
	function destroyOne( $id ) {
		$MerAlbum  = MerAlbumService::instance();
		$albumData = $MerAlbum->getByCond( [
			'catalogId' => $id
		] );
		
		
		try {
			if ( ! empty( $albumData ) ) {
				throw new Exception( '目录下还有图片, 不能删除' );
			}
			
			$this->model->delete( $id );
			
			return ajax_arr( '删除成功', 0 );
		} catch ( Exception $e ) {
			return ajax_arr( '删除失败', 500 );
		}
	}
	
	
}