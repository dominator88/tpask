<?php namespace apps\ask\controller;
/**
 * Website Controller
 *
 * @author zsh <zsh2088@gmail.com>
 * @version 2.0 , 2017-07-22
 */

use apps\common\service\AskWebsiteService;
use think\View;

class Website extends Ask {

	/**
	 * Website constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->_initClassName( __CLASS__ );
		$this->service = AskWebsiteService::instance();
	}

	//页面入口
	public function index() {
		$this->_init( 'Website' );

		//uri
		$this->_addParam( 'uri', [
			'upload'       => full_uri( 'ask/website/upload' ),
'albumCatalog' => full_uri( 'ask/website/read_album_catalog' ),
'album'        => full_uri( 'ask/website/read_album' ),
			
			
		] );

		//查询参数
		$this->_addParam( 'query', [
			'keyword'  => input( 'get.keyword', '' ),
			'status'   => input( 'get.status', '' ),
			'page'     => input( 'get.page', 1 ),
			'pageSize' => input( 'get.pageSize', 10 ),
      'sort'     => input( 'get.sort', 'id' ),
      'order'    => input( 'get.order', 'DESC' ),
		] );

    //上传参数
$this->_addParam('uploadParam' , [
  'width'       => 300 ,
  'height'      => 300 ,
  'saveAsAlbum' => TRUE,
  'albumTag'    => '默认相册',
]);

//相册参数
$this->_addParam( 'albumParam', [
  'defaultTag' => '默认相册',
  'pageSize'   => 12,
] );

		//其他参数
		$this->_addParam( [
			'defaultRow' => $this->service->getDefaultRow() ,
			'status' => $this->service->status ,
		] );

		//需要引入的 css 和 js
		
		
		$this->_addCssLib('node_modules/jcrop-0.9.12/css/jquery.Jcrop.min.css');
$this->_addJsLib('node_modules/jcrop-0.9.12/js/jquery.Jcrop.min.js');
$this->_addJsLib( 'static/plugins/dmg-ui/Uploader.js' );
		

		$this->_addJsLib( 'static/plugins/dmg-ui/TableGrid.js' );
    

		return $this->_displayWithLayout();
	}

	/**
 * 读取
 * @return \think\response\Json
 */
public function read() {


    $View = new View();

   // $View->assign( $data );
    $ret['html'] = $View->fetch( 'index' );

  return $ret;
}
	

}