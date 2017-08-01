<?php
/**
 * Api Trait
 *
 * @author Zix <zix2002@gmail.com>
 * @version 1.0 @ 2016-09-14
 */

namespace apps\common\traits\api;

use apps\common\service\MerUserDeviceService;

define( 'REQUIRED', 'required' );

trait Service {
	
	public $userId = '';
	public $merId = '';
	public $error = '';
	public $errCode = 500;
	
	/**
	 * 验证登录
	 *
	 * @param $token
	 * @param $device
	 *
	 * @return mixed
	 */
	public function validToken( $token, $device = '' ) {
		$this->userId = '';
		$this->error  = 500;
		
		if ( empty( $token ) ) {
			//参数错误
			$this->error = '请填写token';
			
			return FALSE;
		} else {
			$MerUserDevice = MerUserDeviceService::instance();
			$deviceData    = $MerUserDevice->getByToken( $token, $device );
			if ( empty( $deviceData ) ) {
				//数据未找到
				$this->error   = '认证失败';
				$this->errCode = 403;
				
				return FALSE;
			}
			
			$this->userId = $deviceData['user_id'];
			
			return TRUE;
		}
	}
	
	public function validParam( $param, $rule = 'required' ) {
		$this->error   = '';
		$this->errCode = 500;
		switch ( $rule ) {
			case  'required' :
				if ( empty( trim( $param ) ) ) {
					$this->error = "$param 不能为空";
					
					return FALSE;
				}
		}
		
		return TRUE;
	}
}