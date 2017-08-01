<?php namespace apps\common\service;

/**
 * SysSms Service
 *
 * @author Zix
 * @version 2.0 2016-09-18
 */

use sms\SmsSdk;

class SysSmsService extends BaseService {
	
	public $error = '';
	
	const CaptchaVerifyPeriod = 15; //验证码 验证有效期 单位:分钟
	
	//引入 GridTable trait
	use \apps\common\traits\service\GridTable;
	
	
	public $type = [
		'captcha' => '验证码'
	];
	
	//状态
	public $status = [
		-1 => '未发送',
		0   => '未验证',
		1   => '已验证',
	];
	
	//类实例
	private static $instance;
	
	//生成类单例
	public static function instance() {
		if ( self::$instance == NULL ) {
			self::$instance        = new SysSmsService();
			self::$instance->model = db( 'SysSms' );
		}
		
		return self::$instance;
	}
	
	//取默认值
	function getDefaultRow() {
		return [
			'id'          => '',
			'type'        => 'captcha',
			'phone'       => '',
			'content'     => '',
			'temp_id'     => '',
			'create_time' => date( 'Y-m-d H:i:s' ),
			'valid_time'  => '',
			'send_time'   => '',
			'message_id'  => '',
			'status'      => '0',
		];
	}
	
	/**
	 * 根据条件查询
	 *
	 * @param $param
	 *
	 * @return array|number
	 */
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
			'getAll'   => FALSE
		];
		
		$param = extend( $default, $param );
		
		if ( ! empty( $param['keyword'] ) ) {
			$this->model->where( 'name', 'like', "%{$param['keyword']}%" );
		}
		
		if ( $param['status'] !== '' ) {
			$this->model->where( 'status', $param['status'] );
		}
		
		if ( $param['count'] ) {
			return $this->model->count();
		}
		
		$this->model->field( $param['field'] );
		
		if ( ! $param['getAll'] ) {
			$this->model->limit( ( $param['page'] - 1 ) * $param['pageSize'], $param['pageSize'] );
		}
		
		$order[] = "{$param['sort']} {$param['order']}";
		$this->model->order( $order );
		
		$data = $this->model->select();
		
		//echo $this->model->getLastSql();
		
		return $data ? $data : [];
	}
	
	/**
	 * 创建验证码
	 *
	 * @param $phone
	 * @param bool $sendImmediately
	 *
	 * @return array
	 */
	public function createByCaptcha( $phone, $sendImmediately = FALSE ) {
		$oldData = $this->getByPhoneInOneMin( $phone );
		
		if ( ! empty( $oldData ) ) {
			return ajax_arr( '请1分钟后再试试', 500 );
		}
		
		$data = [
			'phone'   => $phone,
			'content' => $this->makeCaptcha(),
			'temp_id' => config( 'custom.sms' )['captchaTempId']
		];
		
		try {
			$id = $this->model->insertGetId( $data );
			
			if ( $sendImmediately ) {
				if ( ! $this->sendCaptcha( $phone, $data['content'], $data['temp_id'] ) ) {
					throw new \Exception( $this->error );
				}
				
				$this->model->where( 'id', $id )->update( [
					'sent_at' => date( 'Y-m-d H:i:s' ),
					'status'  => 0
				] );
			}
			
			return ajax_arr( '创建成功', 0 );
		} catch ( \Exception $e ) {
			return ajax_arr( $e->getMessage(), 500 );
		}
	}
	
	/**
	 * 生成验证码
	 *
	 * @return int
	 */
	private function makeCaptcha() {
		return mt_rand( 100000, 999999 );
	}
	
	
	/**
	 * 发送验证码
	 *
	 * @param $phone
	 * @param $captcha
	 * @param $tempId
	 *
	 * @return bool
	 */
	public function sendCaptcha( $phone, $captcha, $tempId ) {
		$this->error  = '';
		$serverIP     = config( 'custom.sms' )['serverIP'];
		$serverPort   = config( 'custom.sms.' )['serverPort'];
		$softVersion  = config( 'custom.sms' )['softVersion'];
		$accountSid   = config( 'custom.sms' )['accountSid'];
		$accountToken = config( 'custom.sms' )['accountToken'];
		$appId        = config( 'custom.sms' )['appId'];
		
		$SmsSdk = new SmsSdk( $serverIP, $serverPort, $softVersion );
		$SmsSdk->setAccount( $accountSid, $accountToken );
		$SmsSdk->setAppId( $appId );
		
		$data = [ $captcha, self::CaptchaVerifyPeriod . '分钟' ];
		
		$result = $SmsSdk->sendTemplateSMS( $phone, $data, $tempId );
		if ( $result == NULL ) {
			$this->error = '返回错误';
			
			return FALSE;
		}
		if ( $result->statusCode != 0 ) {
			$this->error = (string) $result->statusMsg;
			
			return FALSE;
		}
		
		return TRUE;
	}
	
	/**
	 * 取1分钟内发送的验证码
	 *
	 * @param $phone
	 *
	 * @return mixed
	 */
	public function getByPhoneInOneMin( $phone ) {
		$data = $this->model
			->where( 'phone', $phone )
			->whereTime( 'created_at', '>', time() - 60 )
			->find();
		
		return $data;
	}
	
	/**
	 * 校验验证码
	 *
	 * @param $phone
	 * @param $captcha
	 *
	 * @return bool
	 */
	public function validCaptcha( $phone, $captcha ) {
		
		//获取验证码
		$data = $this->model
			->where( 'phone', $phone )
			->where( 'status', 0 )
			->whereTime( 'sent_at', '>', time() - self::CaptchaVerifyPeriod * 60 )
			->order('sent_at DESC')
			->find();
		
//		echo  $this->model->getLastSql();
		if ( ! $data ) {
			$this->error = '验证码未找到';
			
			return FALSE;
		}
		
		if ( $data['content'] != $captcha ) {
			$this->error = '验证码不正确';
			
			return FALSE;
		}
		
		//验证成功
		$this->model->where( 'id', $data['id'] )->update( [
			'verified_at' => date( 'Y-m-d H:i:s' ),
			'status'      => 1,
		] );
		
		return TRUE;
	}
	
}