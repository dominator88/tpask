<?php namespace apps\ask\controller;

use apps\common\service\AuthService;
use apps\common\controller\SysBase;
use think\View;

class Auth extends SysBase {
	
	private $bgImg = [
		'static/themes/global/img/6.jpg',
		'static/themes/global/img/7.jpg',
		'static/themes/global/img/8.jpg',
		'static/themes/global/img/9.jpg',
		'static/themes/global/img/10.jpg',
	];
	
	/**
	 * 构造函数
	 * Auth constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->_initClassName( __CLASS__ );
	}
	
	/**
	 * 登录页面
	 *
	 * @return string
	 */
	public function signIn() {
		$redirect = input( 'get.redirect', '' );
		$redirect = empty( $redirect ) ? full_uri( "{$this->baseUri}/{$this->module}/index/index" ) : urldecode( $redirect );
		
//		if ( $this->_authUser() === TRUE ) {
//			return redirect( $redirect );
//		}
		
		$this->_init( '管理平台登录' );
		
		$this->data['initPageJs'] = FALSE;
		$this->_addParam( 'uri', [
			'doLogin'  => full_uri( "{$this->module}/auth/doSignIn" ),
			'redirect' => $redirect,
		] );
		
		$cookieUserInfo = cookie( config( 'sessionName' ) );
		$this->_addParam( 'defaultData', [
			'userInfo' => $cookieUserInfo,
			'password' => '',
			'remember' => empty( $cookieUserInfo ) ? '' : 1
		] );
		
		shuffle( $this->bgImg );
		$this->_addParam( 'bg', $this->bgImg );
		
		$this->data['jsCode'][] = 'Auth.initLogin()';
		$this->data['js']       = $this->_makeJs();
		
		$View = new View();
		$View->assign( $this->data );
		
		return $View->fetch();
	}
	
	/**
	 * 登录操作
	 *
	 * @return \think\response\Json
	 */
	function doSignIn() {
		$loginData = input( 'post.' );
		
		$Auth = AuthService::instance();
		
		$result = $Auth->sysSignIn(
			$this->module,
			$loginData['userInfo'],
			$loginData['password'],
			isset( $loginData['remember'] ),
			config( 'sessionName' )
		);
		
		return json( $result );
	}
	
	/**
	 * 登出
	 *
	 * @return \think\response\Redirect
	 */
	function signOut() {
		$Auth = AuthService::instance();
		$Auth->signOut( config( 'sessionName' ) );
		
		return redirect( full_uri( "{$this->module}/auth/signIn" ) );
	}
	
	
}
