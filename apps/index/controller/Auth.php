<?php
namespace apps\index\controller;

use apps\common\controller\IndexBase;
use apps\common\service\AuthService;
use apps\common\service\SysMailService;

class Auth extends IndexBase {
  
  private $coolDownTimeSessionName = 'smart2_send_gap';
  private $coolDownTimeGap         = 60;
  
  public function __construct() {
    parent::__construct();
    
    $this->_initClassName( __CLASS__ );
  }
  
  /**
   * 用户登录
   *
   * @return \think\response\View
   */
  public function SignIn() {
    $method   = strtolower( request()->method() );
    $redirect = input( 'get.redirect' , urlencode( base_uri() ) );
    
    switch ( $method ) {
      case 'get' :
        //显示登录页面
        $this->_init( '用户登录' );
        
        $this->_addParam( 'uri' , [
          'doSignIn' => 'auth/signin?redirect=' . $redirect
        ] );
        
        return $this->_displayWithLayout( 'signin' , 'public/auth_layout' );
      case 'post':
        $userInfo = input( 'post.userInfo' , '' );
        $pwd      = input( 'post.pwd' , '' );
        $remember = input( 'post.remember' , '' );
        
        $Auth = AuthService::instance();
        $ret  = $Auth->webSignInByInfo( $userInfo , $pwd , $remember , config( 'sessionName' ) );
        
        $ret['data']['redirect'] = $redirect;
        
        return json( $ret );
      default :
        return json( ajax_arr( '未知请求方式' , 500 ) );
    }
  }
  
  /**
   * 用户注册
   *
   * @return string
   */
  public function SignUp() {
    
    $method = strtolower( request()->method() );
//    $redirect = input( 'get.redirect' , urlencode( base_uri() ) );
    
    switch ( $method ) {
      case 'get' :
        //显示登录页面
        $this->_init( '用户注册' );
        
        $this->_addParam( 'uri' , [
          'doSignUp'    => 'auth/signup' ,
          'sendCaptcha' => 'auth/sendcaptcha' ,
        ] );
        
        $this->_addParam( [
          'coolDownTime' => cookie( $this->coolDownTimeSessionName ) ,
          'coolDownGap'  => $this->coolDownTimeGap
        ] );
        
        $this->_addJsLib( 'node_modules/moment/min/moment.min.js' );
        
        return $this->_displayWithLayout( 'signup' , 'public/auth_layout' );
      case 'post':
        $email   = input( 'post.email' , '' );
        $pwd     = input( 'post.pwd' , '' );
        $captcha = input( 'post.captcha' , '' );
        
        $Auth = AuthService::instance();
        $ret  = $Auth->webSignUpByMail( $email , $pwd , $captcha , config( 'sessionName' ) );
        
        return json( $ret );
      default :
        return json( ajax_arr( '未知请求方式' , 500 ) );
    }
    
  }
  
  /**
   * 发送验证码
   *
   * @return \think\response\Json
   */
  public function sendCaptcha() {
    $email   = input( 'post.email' );
    $SysMail = SysMailService::instance();
    
    $result = $SysMail->sendCaptcha( $email );
    if ( $result['code'] == 0 ) {
      cookie( $this->coolDownTimeSessionName , time() , 60 );
    }
    
    return json( $result );
  }
  
  /**
   * 用户退出
   */
  public function SignOut() {
    $Auth = AuthService::instance();
    $Auth->signOut( config( 'sessionName' ) );
  }
}
