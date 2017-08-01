<?php namespace apps\ask\controller;
/**
 * Ask 控制器基类
 *
 * @author 老哥 <dominator88@qq.com>
 * @version 2.0 @ 2017-07-19
 */



use apps\common\controller\AskSysBase;
use apps\common\service\SysFuncPrivilegeService;
use apps\common\service\SysFuncService;
use apps\common\service\SysRolePermissionService;
use think\View;

class Ask extends AskSysBase {
	
	public function __construct() {
		parent::__construct();
		//从session取用户信息
		$this->user = session( config( 'sessionName' ) );

		$this->_authUser();
	}

	/**
	 * 验证用户信息
	 *
	 * @return bool|\think\response\Json|\think\response\Redirect
	 */
	public function _authUser() {
		//验证用户登录
		if ( ! $this->user ) {

			//如果未登录
			if ( request()->isAjax() ) {
				//如果是ajax 请求
				exit( json( ajax_arr( '请先登录', 401 ) )->send() );
			}

			//直接跳转
			$redirect = full_uri(
				"{$this->module}/auth/signIn",
				[ 'redirect' => urlencode( request()->url( TRUE ) ) ]
			);
			exit( redirect( $redirect )->send() );
		}

		//修复用户头像
		if ( empty( $this->user['icon'] ) ) {
			$this->user['icon'] = full_img_uri( 'static/themes/global/img/avatar6.jpg' );
		} else {
			$this->user['icon'] = full_img_uri($this->user['icon']);
		}

		//取得操作的别名
		$SysFuncPrivilegeService = SysFuncPrivilegeService::instance();
		$privilege               = $SysFuncPrivilegeService->getByAction( $this->action );

		//验证授权
		$SysRolePermission = SysRolePermissionService::instance();
		$permission        = $SysRolePermission->checkRoleFuncPrivilege(
			$this->user['roles']['role_id'],
			$this->module,
			$this->controller,
			$privilege
		);
//if($this->controller == 'Setting') {var_dump( $permission);exit;}
		if ( ! $permission ) {
			$this->accessDenied();
		}

	//	$this->merId = $this->user['merchant']['id'];
		return TRUE;
	}

	/**
	 * 访问限制
	 * @return \think\response\Json
	 */
	private function accessDenied() {
		if ( ! request()->isAjax() ) {
			game_over( '无此权限' );
		}

		//如果是AJAX请求
		exit( json( ajax_arr( "无此权限[$this->action]" , 403 ))->send() );
	}

	/**
	 * 初始化页面Data
	 * @param string $pageTitle
	 */
	public function _init( $pageTitle = '新页面' ) {
		parent::_init( $pageTitle );

		$SysFunc = SysFuncService::instance();
		$this->_addData(
			'menuData',
			$SysFunc->getMenuByRoles(
				$this->user['roles']['role_id'],
				$this->module )
		);
		$this->_addData('user' , $this->user );
	}

	/**
	 * 根据Layout 显示页面
	 *
	 * @param string $view
	 *
	 * @return string
	 */
	public function _displayWithLayout( $view = 'index' ) {
		$pageView = new View();

		$pageView->assign( $this->data );

		//生成layout数据
		$layoutData = [
			'header'      => $pageView->fetch( 'public/header' ),
			'css'         => $this->_makeCss(),
			'sidebarMenu' => $pageView->fetch( 'public/sidebar_menu' ),
			'body'        => $pageView->fetch( $view ),
			'js'          => $this->_makeJs(),
			'footer'      => $pageView->fetch( 'public/footer' )
		];

		$pageView->assign( $layoutData );

		return $pageView->fetch( 'public/layout' );
	}

	
}