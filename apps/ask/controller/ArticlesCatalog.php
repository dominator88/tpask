<?php
namespace apps\ask\controller;

/**
 * AskArticlesCatalog Controller
 *
 * @author Zix <zix2002@gmail.com>
 * @version 2.0 , 2016-09-15
 */


use apps\common\service\AskArticlesCatalogService;

class ArticlesCatalog extends Ask {
	
	/**
	 * AskArticlesCatalog constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->_initClassName( __CLASS__ );
		$this->service = AskArticlesCatalogService::instance();
	}
	
	//页面入口
	public function index() {
		$this->_init( '问题分类' );
		
		//uri
		$this->_addParam( 'uri', [
			'menu'         => '/ask/ArticlesCatalog/index',
			'upload'       => full_uri( 'ask/articlescatalog/upload' ),
			'albumCatalog' => full_uri( 'ask/articlescatalog/read_album_catalog' ),
			'album'        => full_uri( 'ask/articlescatalog/read_album' ),
		] );
		
		//查询参数
		$this->_addParam( 'query', [
			'keyword'  => input( 'param.keyword', '' ),
			'status'   => input( 'param.status', '' ),
			'page'     => input( 'param.page', 1 ),
			'pageSize' => input( 'param.pageSize', 10 ),
			'sort'     => input( 'param.sort', 'id' ),
			'order'    => input( 'param.order', 'DESC' ),
		] );
		
		//上传参数
		$this->_addParam( 'uploadParam', [
			'width'       => 300,
			'height'      => 300,
			'saveAsAlbum' => TRUE,
			'albumTag'    => '图标',
		] );
		
		//相册参数
		$this->_addParam( 'albumParam', [
			'defaultTag' => '图标',
			'pageSize'   => 12,
		] );
		
		//其他参数
		$this->_addParam( [
			'defaultRow' => $this->service->getDefaultRow(),
			'status'     => $this->service->status,
		] );
		
		//需要引入的 css 和 js
		$this->_addCssLib( 'node_modules/jcrop-0.9.12/css/jquery.Jcrop.min.css' );
		$this->_addJsLib( 'node_modules/jcrop-0.9.12/js/jquery.Jcrop.min.js' );
		$this->_addJsLib( 'static/plugins/dmg-ui/Uploader.js' );
		$this->_addJsLib( 'static/plugins/dmg-ui/TreeGrid.js' );
		
		return $this->_displayWithLayout();
	}
	
	
	/**
	 * 读取
	 * @return \think\response\Json
	 */
	function read() {
		$config = [
			'status'  => input( 'get.status', '' ),
			'keyword' => input( 'get.keyword', '' ),
			'sort'    => input( 'get.sort', 'id' ),
			'order'   => input( 'get.order', 'DESC' ),
		];
		
		$data['rows'] = $this->service->getByCond( $config );
		
		return json( ajax_arr( '查询成功', 0, $data ) );
	}
	
	function insert() {
		$data           = input( 'post.' );
		
		return json( $this->service->insert( $data ) );
	}
	
	function update() {
		$id   = input( 'get.id', '' );
		$data = input( 'post.' );
		
		return json( $this->service->update( $id, $data ) );
	}
	
}