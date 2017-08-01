<?php namespace apps\ask\controller;



class Index extends Ask {
	public function __construct() {
		parent::__construct();
		$this->_initClassName( __CLASS__ );
	}


	/**
	 * 页面显示接口
	 *
	 * @return string
	 */
	public function index() {
		$this->_init( '首页' );
		
		$this->_addJsLib( 'node_modules/waypoints/lib/jquery.waypoints.min.js' );
		$this->_addJsLib( 'node_modules/jquery.counterup/jquery.counterup.min.js' );
		$this->_addJsLib( 'node_modules/echarts/dist/echarts.min.js' );
		
		$stat = cache( 'stat' );
		if ( ! $stat ) {
			$stat = [
				'articles' => db( 'mer_articles' )->count(),
				'users'    => db( 'mer_user' )->count( 'id' ),
				'api'      => db( 'sys_api_log' )->whereTime( 'created_at', '>', date( 'Y-m-d' ) )->count(),
				'download' => 0,
			];
			cache( 'stat', $stat, 300 );
		}
		
		$charts = $this->_getCharts( $stat );
		
		$this->_addData( 'stat', $stat );
		$this->_addParam( 'charts', $charts );
		
		return $this->_displayWithLayout();
	}
	
	/**
	 * 获取图表数据
	 *
	 * @param $stat
	 *
	 * @return array
	 */
	private function _getCharts( $stat ) {
		$data = db( 'sys_statistics' )->order( 'created_at ASC' )->limit( 29 )->select();
		
		$period = [];
		$users  = [];
		$api    = [];
		foreach ( $data as $item ) {
			$period[] = substr( $item['created_at'], 5, 5 );
			$users[]  = $item['users_today'];
			$api[]    = $item['api'];
		}
		
		$period[] = date( 'm-d' );
		$users[]  = db( 'mer_user' )->whereTime( 'reg_at', '>', date( 'Y-m-d' ) )->count();
		$api[]    = $stat['api'];
		
		return [
			'users' => [
				'period' => $period,
				'data'   => $users,
			],
			'api'   => [
				'period' => $period,
				'data'   => $api,
			]
		];
	}
}
