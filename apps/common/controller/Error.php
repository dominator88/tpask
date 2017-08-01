<?php
namespace apps\common\controller;

class Error extends SysBase {
	/**
	 * Error constructor.
	 */
	public function __construct() {
		parent::__construct();
	}
	
	public function index() {
		$this->_init( '页面未找到' );
		return view( 'public/404' , $this->data  );
	}
	
	public function test() {
		
	}
}
