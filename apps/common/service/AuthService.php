<?php
/**
 * Auth Service
 *
 * @author  Zix
 * @version 2.0 2016-09-07
 */

namespace apps\common\service;


use think\Db;
use think\Session;

class AuthService {
  
  //设备类型
  public $os_device = [
    'ios'     => 'iphone' ,
    'android' => 'android' ,
  ];
  
  //cookie 存放时间
  const COOKIE_TIME = 604800;
  
  //类实例
  private static $instance;
  
  public static function instance() {
    if ( self::$instance == NULL ) {
      self::$instance = new AuthService();
    }
    
    return self::$instance;
  }
  
  /**
   * 系统用户登录
   *
   * @param $module        //登录的模块
   * @param $userInfo      //用户姓名或手机号
   * @param $pwd           //密码
   * @param bool $remember //是否记住用户名
   * @param $sessionName   //保存的session name
   *
   * @return array
   */
  function sysSignIn( $module , $userInfo , $pwd , $remember = FALSE , $sessionName ) {
    //用户名密码登录
    if ( empty( $userInfo ) ) {
      return ajax_arr( '用户名或手机不能为空' , 500 );
    }
    
    if ( empty( $pwd ) ) {
      return ajax_arr( '密码不能为空' , 500 );
    }
    
    $SysUser = db( 'SysUser' );
    $SysUser->where( 'username|phone|email' , $userInfo );
    $SysUser->where( 'status' , 1 );
    $SysUser->where( 'module' , $module );
    $userData = $SysUser->find();
    
    if ( empty( $userData ) ) {
      return ajax_arr( '用户未找到' , 500 );
    }
    
    //验证密码
    if ( ! $this->authPwd( $pwd , $userData['password'] ) ) {
      return ajax_arr( '密码不正确' , 500 );
    }
    
    //取用户角色信息
    $SysUserRole = SysUserRoleService::instance();
    $roleData    = $SysUserRole->getByUser( $userData['id'] , TRUE );
    
    if ( empty( $roleData['role_id'] ) ) {
      return ajax_arr( '用户角色未找到' , 500 );
    }
    $userData['roles'] = $roleData;
    $userData['token'] = md5( time() . rand_string() );
    
    unset( $userData['password'] );
    Session::set( $sessionName , $userData );
    $this->afterSysSignIn( $userData , $remember , $sessionName );
    
    if ( $module == 'mp' ) {
      //如果是商户 取商户信息
      $SysMerchant = SysMerchantService::instance();
      $MerData     = $SysMerchant->getBySysUser( $userData['id'] );
      if ( empty( $MerData ) ) {
        return ajax_arr( '商户信息未找到' , 500 );
      }
      $userData['merchant'] = $MerData;
      Session::set( $sessionName , $userData );
    }
    
    return ajax_arr( '登录成功' , 0 );
  }
  
  /**
   * 系统用户登录后的处理
   *
   * @param $userData
   * @param $remember
   * @param $sessionName
   */
  private function afterSysSignIn( $userData , $remember , $sessionName ) {
    if ( ! empty( $remember ) ) {
      cookie( $sessionName , $userData['username'] , self::COOKIE_TIME );
    }
    
    //生产token 写登录时间
    $SysUser = db( 'SysUser' );
    $SysUser->where( 'id' , $userData['id'] )
            ->update( [
              'signed_at' => date( 'Y-m-d H:i:s' ) ,
              'signed_ip' => request()->ip( 0 , TRUE ) ,
              'token'     => $userData['token']
            ] );
  }
  
  
  /**
   * 验证密码
   *
   * @param $str_pwd
   * @param $pwd
   *
   * @return bool
   */
  private function authPwd( $str_pwd , $pwd ) {
    return password_verify( $str_pwd , $pwd );
  }
  
  /**
   * 登出
   *
   * @param $session_name
   *
   * @return bool
   */
  function signOut( $session_name ) {
    session( $session_name , NULL );
    
    return TRUE;
  }
  
  /**
   * 系统用户修改密码
   *
   * @param $userId
   * @param $oldPwd
   * @param $pwd
   * @param $pwdConfirm
   *
   * @return array
   */
  public function sysChangePwd( $userId , $oldPwd , $pwd , $pwdConfirm ) {
    
    if ( empty( $oldPwd ) ) {
      return ajax_arr( '请输入原密码' , 400 );
    }
    
    if ( empty( $pwd ) ) {
      return ajax_arr( '请输入新密码' , 400 );
    }
    
    if ( $pwd != $pwdConfirm ) {
      return ajax_arr( '两次输入的密码不一样' , 400 );
    }
    
    $SysUser  = SysUserService::instance();
    $userData = $SysUser->getById( $userId );
    if ( empty( $userData ) ) {
      return ajax_arr( '用户未找到' , 504 );
    }
    
    if ( ! password_verify( $oldPwd , $userData['password'] ) ) {
      return ajax_arr( '原密码不正确' , 400 );
    }
    
    return $SysUser->update( $userId , [
      'password' => str2pwd( $pwd )
    ] );
  }
  
  /**
   * 用户web登录
   *
   * @param $userInfo //手机号|用户名|邮箱
   * @param $pwd
   * @param $remember
   * @param $sessionName
   *
   * @return array
   */
  public function webSignInByInfo( $userInfo , $pwd , $remember , $sessionName ) {
    $userInfo = trim( $userInfo );
    $pwd      = trim( $pwd );
    
    if ( empty( $userInfo ) ) {
      return ajax_arr( '请填写手机号或email' , 500 );
    }
    
    if ( empty( $pwd ) ) {
      return ajax_arr( '请填写密码' , 500 );
    }
    
    $MerUser  = db( 'MerUser' );
    $userData = $MerUser->where( 'phone|email|username' , $userInfo )->find();
    
    if ( empty( $userData ) ) {
      return ajax_arr( '用户未找到' , 500 );
    }
    
    if ( ! $this->authPwd( $pwd , $userData['password'] ) ) {
      return ajax_arr( '密码不正确' , 500 );
    }
    
    unset( $userData['password'] );
    Session::set( $sessionName , $userData );
    if ( ! empty( $remember ) ) {
      cookie( $sessionName , json_encode( $userData ) );
    }
    $userData = $this->afterWebSignIn( $userData );
    
    return ajax_arr( '登录成功' , 0 , $userData );
  }
  
  /**
   * web用户登录后的处理
   *
   * @param $userData
   *
   * @return mixed
   */
  private function afterWebSignIn( $userData ) {
    unset( $userData['password'] );
    
    //更新用户表
    $loginData = [
      'login_ip' => request()->ip( 0 , TRUE ) ,
      'login_at' => date( 'Y-m-d H:i:s' )
    ];
    $MerUser   = db( 'MerUser' );
    $MerUser->where( 'id' , $userData['id'] )->update( $loginData );
    
    //更新用户设备表
    $MerUserDevice = MerUserDeviceService::instance();
    $deviceData    = [
      'device'     => get_device() ,
      'updated_at' => date( 'Y-m-d H:i:s' )
    ];
    $MerUserDevice->updateByUser( $userData['id'] , $deviceData );
    
    return $userData;
  }
  
  /**
   * 网站用户 email 注册
   *
   * @param $email
   * @param $pwd
   * @param $captcha
   * @param $sessionName
   *
   * @return array
   */
  public function webSignUpByMail( $email , $pwd , $captcha , $sessionName ) {
    $email   = trim( $email );
    $pwd     = trim( $pwd );
    $captcha = trim( $captcha );
    
    if ( ! filter_var( $email , FILTER_VALIDATE_EMAIL ) ) {
      return ajax_arr( '邮件格式不正确' , 500 );
    }
    
    if ( strlen( $pwd ) < 6 ) {
      return ajax_arr( '密码需在6-16个字符' , 500 );
    }
    
    if ( empty( $captcha ) ) {
      return ajax_arr( '请填写验证码' , 500 );
    }
    
    Db::startTrans();
    try {
      //验证sms验证码
      $SysMail = SysMailService::instance();
      if ( ! $SysMail->validCaptcha( $email , $captcha ) ) {
        throw new \Exception( '验证码不正确' );
      }
      
      //检查是否已经邮箱注册过了
      $MerUser = MerUserService::instance();
      
      //开始创建用户
      $addData = [
        'email'    => $email ,
        'password' => str2pwd( $pwd ) ,
        'reg_from' => 'email' ,
        'reg_ip'   => request()->ip( 0 , TRUE ) ,
      ];
      
      $regResult = $MerUser->insertByEmail( $addData );
      
      if ( $regResult['code'] != 0 ) {
        throw new \Exception( $regResult['msg'] );
      }
      
      Db::commit();
      
      //注册完成后直接登录
      return $this->webSignInByInfo( $email , $pwd , 0 , $sessionName );
    } catch ( \Exception $e ) {
      Db::rollback();
      
      return ajax_arr( $e->getMessage() , 500 );
    }
  }
  
  /**
   * 接口用户 手机/email登陆
   *
   * @param $data
   *
   * @return array
   */
  public function apiSignInByUserInfo( $data ) {
    
    if ( empty( $data['userInfo'] ) ) {
      return ajax_arr( '手机号或Email不能为空' , 500 );
    }
    
    if ( empty( $data['pwd'] ) ) {
      return ajax_arr( '密码不能为空' , 500 );
    }
    
    $MerUser  = db( 'MerUser' );
    $userData = $MerUser
      ->where( 'email|phone' , $data['userInfo'] )
      ->where( 'mer_id' , $data['merId'] )
      ->find();
    
    if ( empty( $userData ) ) {
      return ajax_arr( '用户未找到' , 500 );
    }
    
    if ( ! $this->authPwd( $data['pwd'] , $userData['password'] ) ) {
      return ajax_arr( '密码不正确' , 500 );
    }
    
    $userData = $this->afterApiSignIn( $userData , $data );
    
    return ajax_arr( '登录成功' , 0 , $userData );
  }
  
  /**
   * api用户登录后的操作
   *
   * @param $userData
   * @param $metaData
   *
   * @return mixed
   */
  private function afterApiSignIn( $userData , $metaData ) {
    if ( isset( $userData['password'] ) ) {
      unset( $userData['password'] );
    }
    
    $userData['token'] = md5( time() . $userData['username'] . $metaData['device'] . rand_string() );
    
    //更新用户表
    $loginData = [
      'login_ip' => request()->ip( 0 , TRUE ) ,
      'login_at' => date( 'Y-m-d H:i:s' )
    ];
    $MerUser   = db( 'MerUser' );
    $MerUser->where( 'id' , $userData['id'] )->update( $loginData );
    
    //更新用户设备表
    $MerUserDevice = MerUserDeviceService::instance();
    $deviceData    = [
      'token'             => $userData['token'] ,
      'device'            => $metaData['device'] ,
      'device_os_version' => $metaData['deviceOsVersion'] ,
      'app_version'       => $metaData['appVersion'] ,
      'api_version'       => $metaData['apiVersion'] ,
      'registration_id'   => $metaData['registrationId'] ,
    ];
    
    $MerUserDevice->updateByUser( $userData['id'] , $deviceData );

//    $userData['username'] .= $result['msg'];
    
    return $userData;
  }
  
  /**
   * 第三方账户登录
   *
   * @param $data
   *
   * @return array
   */
  public function apiSignInBySns( $data ) {
    $MerUserSns = MerUserSnsService::instance();
    $MerUser    = MerUserService::instance();
    
    $oldData = $MerUserSns->getBySnsUid( $data['snsUid'] , $data['platform'] );
    if ( empty( $oldData ) ) {
      //如果没有找到 先注册用户
      $userResult = $MerUser->insertBySns( $data );
      if ( $userResult['code'] != 0 ) {
        return $userResult;
      }
      $userData = $userResult['data'];
    } else {
      //如果用户存在 取用户数据
      $userData = $MerUser->getById( $oldData['user_id'] );
    }
    //返回用户信息
    $userData = $this->afterApiSignIn( $userData , $data );
    
    return ajax_arr( '登录成功' , 0 , $userData );
  }
  
  /**
   * 根据token取用户信息
   *
   * @param $token
   * @param string $device
   *
   * @return array
   */
  public function apiGetUserByToken( $token , $device = '' ) {
    $MerUserDevice = MerUserDeviceService::instance();
    $userData      = $MerUserDevice->getByToken( $token , $device , TRUE );
    
    if ( ! empty( $userData ) ) {
      unset( $userData['password'] );
    }
    
    $userData['token'] = $token;
    
    return $userData;
  }
  
  /**
   * 用户注册 for API
   *
   * @param $data
   *
   * @return array
   */
  public function apiSignUpByPhone( $data ) {
    $data['userInfo'] = trim( $data['userInfo'] );
    $data['pwd']      = trim( $data['pwd'] );
    
    if ( strlen( $data['userInfo'] ) != 11 ) {
      return ajax_arr( '手机号不正确' , 500 );
    }
    
    Db::startTrans();
    try {
      //验证sms验证码
      $SysSms = SysSmsService::instance();
      if ( ! $SysSms->validCaptcha( $data['userInfo'] , $data['captcha'] ) ) {
        throw new \Exception( '短信验证码不正确' );
      }
      
      if ( strlen( $data['pwd'] ) < 6 ) {
        return ajax_arr( '密码不正确' , 500 );
      }
      
      //验证用户是否重复
      
      //开始创建用户
      $maskPhone = substr_replace( $data['userInfo'] , '****' , 3 , 4 );
      $addData   = [
        'mer_id'     => $data['merId'] ,
        'username'   => $data['username'] ,
        'nickname'   => $data['username'] . '-' . $maskPhone ,
        'phone'      => $data['userInfo'] ,
        'password'   => str2pwd( $data['pwd'] ) ,
        'reg_from'   => $data['type'] ,
        'reg_ip'     => request()->ip( 0 , TRUE ) ,
        'industries' => $data['industries']
      ];
      
      $MerUser   = MerUserService::instance();
      $regResult = $MerUser->insert( $addData );
      
      if ( $regResult['code'] != 0 ) {
        throw new \Exception( $regResult['msg'] );
      }
      
      Db::commit();
      
      //注册完成后直接登录
      $loginData = [
        'userInfo'        => $data['userInfo'] ,
        'pwd'             => $data['pwd'] ,
        'registrationId'  => $data['registrationId'] ,
        'device'          => $data['device'] ,
        'deviceOsVersion' => $data['deviceOsVersion'] ,
        'appVersion'      => $data['appVersion'] ,
        'apiVersion'      => $data['apiVersion']
      ];
      
      return $this->apiSignInByPhone( $loginData );
    } catch ( \Exception $e ) {
      Db::rollback();
      
      return ajax_arr( $e->getMessage() , 500 );
    }
  }
  
  public function apiSignUpByEmail( $data ) {
    $data['userInfo'] = trim( $data['userInfo'] );
    $data['pwd']      = trim( $data['pwd'] );
    
    if ( ! filter_var( $data['userInfo'] , FILTER_VALIDATE_EMAIL ) ) {
      return ajax_arr( '请输入正确的Email' , 500 );
    }
    
    Db::startTrans();
    try {
      //验证sms验证码
      $SysMail = SysMailService::instance();
      if ( ! $SysMail->validCaptcha( $data['userInfo'] , $data['captcha'] ) ) {
        throw new \Exception( $SysMail->getError() );
      }
      
      if ( strlen( $data['pwd'] ) < 6 ) {
        return ajax_arr( '密码不正确' , 500 );
      }
      
      //开始创建用户
      $addData = [
        'mer_id'   => $data['merId'] ,
        'username' => $data['userInfo'] ,
        'nickname' => $data['userInfo'] ,
        'password' => str2pwd( $data['pwd'] ) ,
        'email'    => $data['userInfo'] ,
        'reg_from' => $data['type'] ,
        'reg_ip'   => request()->ip( 0 , TRUE ) ,
      ];
      
      $MerUser   = MerUserService::instance();
      $regResult = $MerUser->insert( $addData );
      
      if ( $regResult['code'] != 0 ) {
        throw new \Exception( $regResult['msg'] );
      }
      
      Db::commit();
      
      //注册完成后直接登录
      $loginData = [
        'merId'           => $data['merId'] ,
        'userInfo'        => $data['userInfo'] ,
        'pwd'             => $data['pwd'] ,
        'registrationId'  => $data['registrationId'] ,
        'device'          => $data['device'] ,
        'deviceOsVersion' => $data['deviceOsVersion'] ,
        'appVersion'      => $data['appVersion'] ,
        'apiVersion'      => $data['apiVersion']
      ];
      
      return $this->apiSignInByUserInfo( $loginData );
    } catch ( \Exception $e ) {
      Db::rollback();
      
      return ajax_arr( $e->getMessage() , 500 );
    }
  }
  
  /**
   * 用户修改密码
   *
   * @param $userId
   * @param $oldPwd
   * @param $pwd
   * @param $pwdConfirm
   *
   * @return array
   */
  public function apiChangePwd( $userId , $oldPwd , $pwd , $pwdConfirm ) {
    if ( empty( $oldPwd ) ) {
      return ajax_arr( '请输入原密码' , 400 );
    }
    
    if ( empty( $pwd ) ) {
      return ajax_arr( '请输入新密码' , 400 );
    }
    
    if ( $pwd != $pwdConfirm ) {
      return ajax_arr( '两次输入的密码不一样' , 400 );
    }
    
    $MerUser = MerUserService::instance();
    
    return $MerUser->updatePwd( $userId , $oldPwd , $pwd );
  }
  
  /**
   * 用户通过手机重置密码
   *
   * @param $merId
   * @param $phone
   * @param $captcha
   * @param $pwd
   *
   * @return array
   */
  public function apiResetPwdByPhone( $merId , $phone , $captcha , $pwd ) {
    $SysSms = SysSmsService::instance();
    if ( ! $SysSms->validCaptcha( $phone , $captcha ) ) {
      return ajax_arr( '验证码不正确' , 500 );
    }
    
    if ( strlen( $pwd ) > 16 || strlen( $pwd ) < 6 ) {
      return ajax_arr( '密码长度不正确' , 500 );
    }
    
    $MerUser = MerUserService::instance();
    
    return $MerUser->resetPwdByPhone( $merId , $phone , $pwd );
  }
  
  /**
   * 用户通过邮件重置密码
   *
   * @param $merId
   * @param $email
   * @param $captcha
   * @param $pwd
   *
   * @return array
   */
  public function apiResetPwdByEmail( $merId , $email , $captcha , $pwd ) {
    $SysMail = SysMailService::instance();
    if ( ! $SysMail->validCaptcha( $email , $captcha ) ) {
      return ajax_arr( '验证码不正确' , 500 );
    }
    
    if ( strlen( $pwd ) > 16 || strlen( $pwd ) < 6 ) {
      return ajax_arr( '密码长度不正确' , 500 );
    }
    
    $MerUser = MerUserService::instance();
    
    return $MerUser->resetPwdByEmail( $merId , $email , $pwd );
  }
  
  //前台用户登录
//	function login_home( $user_info, $pwd, $remember = FALSE ) {
//		$session_name = config( 'session_name' );
//
//		if ( empty( $user_info ) ) {
//			return ajax_arr( '手机号或用户名不能为空', 404 );
//		}
//
//		if ( empty( $pwd ) ) {
//			return ajax_arr( '密码不能为空', 400 );
//		}
//
//		$where = [
//			'username|phone' => $user_info,
//		];
//
//		$MerUser   = db( 'MerUser' );
//		$user_data = $MerUser->where( $where )->find();
//
//		if ( empty( $user_data ) ) {
//			return ajax_arr( '用户未找到', 504 );
//		}
//
//		if ( ! $this->_auth_pwd( $pwd, $user_data['password'] ) ) {
//			return ajax_arr( '密码不正确', 400 );
//		}
//
//		$user_data = $this->_after_home_user_login( $user_data );
//
//		session( $session_name, $user_data );
//		if ( $remember ) {
//			cookie( $session_name, $user_info, 86400 * 7 );
//		}
//
//		return ajax_arr( '登录成功', 0, $user_data );
//	}

//	function _after_home_user_login( $user_data ) {
//		unset( $user_data['password'] );
//		
//		$user_data['token'] = md5( time() . $user_data['username'] . $user_data['create_time'] );
//		
//		//记录最后登录IP和时间
//		$login_data = [
//			'login_ip'   => get_client_ip( '', TRUE ),
//			'login_time' => date( 'Y-m-d H:i:s' ),
//			'token'      => $user_data['token']
//		];
//		
//		$MerUser = db( 'MerUser' );
//		$MerUser->where( 'id = %d', $user_data['id'] )->save( $login_data );
//		
//		$MerUserDevice = MerUserDeviceService::instance();
//		$MerUserDevice->update_by_user( $user_data['id'], $user_data );
//		
//		return $user_data;
//	}
//	
//	//前台用户注册
//	function register_home_by_phone( $data ) {
//		//验证sms验证码
//		$SysSms = SysSmsService::instance();
//		if ( $SysSms->valid_captcha( $data['phone'], $data['sms_captcha'] ) ) {
//			return ajax_arr( '短信验证码不正确', 400 );
//		}
//		
//		if ( strlen( $data['phone'] ) != 11 ) {
//			return ajax_arr( '手机号不正确', 400 );
//		}
//		
//		if ( strlen( $data['password'] ) < 6 ) {
//			return ajax_arr( '密码不正确', 400 );
//		}
//		
//		$mask_phone = substr_replace( $data['phone'], '****', 3, 4 );
//		$new_data   = [
//			'username'   => "user_{$mask_phone}_" . rand_string(),
//			'nickname'   => $mask_phone,
//			'phone'      => $data['phone'],
//			'password'   => str2pwd( $data['password'] ),
//			'reg_from'   => 'phone',
//			'reg_device' => agent_device(),
//			'reg_ip'     => get_client_ip( 0, TRUE ),
//		];
//		
//		$MerUser = db( 'MerUser' );
//		$user_id = $MerUser->add( $new_data );
//		
//		if ( $user_id === FALSE ) {
//			return ajax_arr( '注册失败,请稍后再试', 500 );
//		}
//		
//		$this->login_home( $data['phone'], $data['password'] );
//		
//		return ajax_arr( '注册成功', 0 );
//	}
  
  /*
  //社交网络 账户登录
  function login_sns( $meta_data ) {
    //查询系统中是否有该用户
    $MerUserSns = MerUserSnsService::instance();
    $user_data  = $MerUserSns->get_by_um_uid( $meta_data['um_uid'], $meta_data['platform'], TRUE );
    
    $MerUser = db( 'MerUser' );
    $MerUser->startTrans();
    
    try {
      if ( empty( $user_data ) ) {
        //如果没有,先创建用户
        $user_id = $this->_register_by_sns( $meta_data );
        if ( $user_id === FALSE || empty( $user_id ) ) {
          throw new \Exception( '创建用户失败' );
        }
        
        //再绑定用户sns
        $sns_data = [
          'user_id'  => $user_id,
          'platform' => $meta_data['platform'],
          'um_uid'   => $meta_data['um_uid'],
          'username' => $meta_data['username'],
          'icon'     => $meta_data['icon']
        ];
        
        $ret_create_sns = $MerUserSns->create( $sns_data );
        if ( $ret_create_sns['code'] != 0 ) {
          throw new \Exception( '创建第三方用户失败' );
        }
        
        $user_data = $MerUserSns->get_by_um_uid( $meta_data['um_uid'], $meta_data['platform'], TRUE );
      }
      
      $MerUser->commit();
      $user_data = $this->_after_api_user_login( $user_data, $meta_data );
      
      //如果有,取用户信息,返回登录成功
      return ajax_arr( '登录成功', 0, $user_data );
      
    } catch ( \Exception $e ) {
      //如果失败 回滚数据,提示错误
      $MerUser->rollback();
      
      return ajax_arr( $e->getMessage(), 500 );
    }
  }
  
  //sns 用户注册
  function _register_by_sns( $meta_data ) {
    //取reg_device
    $reg_device = isset( $this->os_device[ $meta_data['client_os'] ] ) ?
      $this->os_device[ $meta_data['client_os'] ] : 'unknown';
    
    $user_data = [
      'phone'      => "",
      'username'   => "um_{$meta_data['um_uid']}",
      'nickname'   => $meta_data['username'],
      'icon'       => $meta_data['icon'],
      'password'   => str2pwd( rand_string( 16 ) ),
      'reg_from'   => $meta_data['platform'],
      'reg_device' => $reg_device,
      'reg_ip'     => get_client_ip( 0, TRUE )
    ];
    
    $MerUser = db( 'MerUser' );
    
    return $MerUser->add( $user_data );
  }
  */
  
}