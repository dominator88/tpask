<?php namespace apps\common\service;

/**
 * SysMail Service
 *
 * @author  Zix
 * @version 2.0 2016-10-10
 */

use PHPMailer;

class SysMailService extends BaseService {
  
  //引入 GridTable trait
  use \apps\common\traits\service\GridTable;
  
  private $timeCap = 600;
  
  public $type = [
    'captcha' => '验证码' ,
  ];
  
  
  public $type2mail = [
    'captcha' => 'member' ,
  ];
  
  //状态
  public $status = [
    0 => '未发送' ,
    1 => '已发送' ,
  ];
  
  public $error = '';
  
  //类实例
  private static $instance;
  
  //生成类单例
  public static function instance() {
    if ( self::$instance == NULL ) {
      self::$instance        = new SysMailService();
      self::$instance->model = db( 'SysMail' );
    }
    
    return self::$instance;
  }
  
  public function setError( $error ) {
    $this->error = $error;
  }
  
  public function getError() {
    return $this->error;
  }
  
  //取默认值
  function getDefaultRow() {
    return [
      'id'         => '' ,
      'type'       => 'captcha' ,
      'name'       => '' ,
      'address'    => '' ,
      'subject'    => '' ,
      'content'    => '' ,
      'captcha'    => '' ,
      'status'     => '0' ,
      'created_at' => date( 'Y-m-d H:i:s' ) ,
      'sent_at'    => '' ,
    ];
  }
  
  /**
   * 根据条件查询
   *
   * @param $param
   *
   * @return array|number
   */
  public function getByCond( $param ) {
    $default = [
      'field'    => [] ,
      'keyword'  => '' ,
      'status'   => '' ,
      'page'     => 1 ,
      'pageSize' => 10 ,
      'sort'     => 'id' ,
      'order'    => 'DESC' ,
      'count'    => FALSE ,
      'getAll'   => FALSE
    ];
    
    $param = extend( $default , $param );
    
    if ( ! empty( $param['keyword'] ) ) {
      $this->model->where( 'name' , 'like' , "%{$param['keyword']}%" );
    }
    
    if ( $param['status'] !== '' ) {
      $this->model->where( 'status' , $param['status'] );
    }
    
    if ( $param['count'] ) {
      return $this->model->count();
    }
    
    $this->model->field( $param['field'] );
    
    if ( ! $param['getAll'] ) {
      $this->model->limit( ( $param['page'] - 1 ) * $param['pageSize'] , $param['pageSize'] );
    }
    
    $order[] = "{$param['sort']} {$param['order']}";
    $this->model->order( $order );
    
    $data = $this->model->select();
    
    //echo $this->model->getLastSql();
    
    return $data ? $data : [];
  }
  
  public function sendById( $id ) {
    $data = $this->getById( $id );
    
    if ( ! $data || empty( $data ) ) {
      return ajax_arr( '邮件未找到' , 0 );
    }
    
    switch ( $data['type'] ) {
      case 'captcha' :
        return $this->sendCaptcha( '' , $data );
      case 'notification':
      case 'ad':
        return ajax_arr( '暂未开通' , 500 );
      default:
        return ajax_arr( '位置类型' , 500 );
    }
  }
  
  /**
   * 创建新邮件对象
   *
   * @param string $type
   *
   * @return PHPMailer
   */
  private function makeMail( $type = 'captcha' ) {
    $mail = new PHPMailer;
    
    //$mail->SMTPDebug = 3;                               // Enable verbose debug output
    $from         = $this->type2mail[ $type ];
    $mailConfig   = config( 'custom.mail' );
    $memberConfig = $mailConfig[ $from ];
    
    $mail->isSMTP();  // Set mailer to use SMTP
    $mail->Host       = $mailConfig['smtp'];  // Specify main and backup SMTP servers
    $mail->SMTPAuth   = TRUE;                 // Enable SMTP authentication
    $mail->Username   = $memberConfig['username'];  // SMTP username
    $mail->Password   = $memberConfig['password'];  // SMTP password
    $mail->SMTPSecure = 'tls';               // Enable TLS encryption, `ssl` also accepted
    $mail->Port       = $mailConfig['port']; // TCP port to connect to
    $mail->CharSet    = 'utf-8';
    $mail->setFrom( $memberConfig['username'] , $memberConfig['desc'] );
    $mail->isHTML( TRUE );  // Set email format to HTML
    
    return $mail;
  }
  
  /**
   * 发送验证码
   *
   * @param $address
   * @param $data
   *
   * @return array
   */
  public function sendCaptcha( $address = '' , $data = [] ) {
    if ( empty( $data ) ) {
      if ( ! filter_var( $address , FILTER_VALIDATE_EMAIL ) ) {
        return ajax_arr( '请填写正确的email' . $address , 500 );
      }
      
      $data = [
        'address' => $address ,
        'subject' => '来自' . config( 'custom.projectName' ) . '的验证码' ,
        'captcha' => $this->getCaptcha() ,
      ];
      
      //保存邮件到数据库
      $result = $this->insert( $data );
      if ( $result['code'] != 0 ) {
        return $result;
      }
      $id = $result['data']['id'];
    } else {
      $address = $data['address'];
      $id      = $data['id'];
    }
    
    $mail = $this->makeMail( 'captcha' );
    $mail->addAddress( $address );     // Add a recipient
    $mail->Subject = $data['subject'];
    $mail->Body    = "验证码为 <b>" . $data['captcha'] . "</b> 请于10分钟内验证";
    
    //保存验证码到 数据库
    if ( ! $mail->send() ) {
      $this->setError( $mail->ErrorInfo );
      
      return ajax_arr( '发送失败'.$data['captcha'] , 500 );
    }
    //修改状态
    $this->setMailSent( $id );
    
    return ajax_arr( '发送成功' , 0 );
  }
  
  /**
   * 设置邮件已发送
   *
   * @param $id
   *
   * @return array
   */
  private function setMailSent( $id ) {
    return $this->update( $id , [
      'status'  => 1 ,
      'sent_at' => date( 'Y-m-d H:i:s' )
    ] );
  }
  
  /**
   * 生成验证码
   *
   * @param int $len
   *
   * @return int
   */
  private function getCaptcha( $len = 4 ) {
    if ( $len == 6 ) {
      return mt_rand( 100000 , 999999 );
    }
    
    return mt_rand( 1000 , 9999 );
  }
  
  public function validCaptcha( $address , $captcha ) {
    $this->error = '';
    
    $data = $this->model
      ->where( 'address' , $address )
      ->where( 'captcha' , $captcha )
      ->order( 'id DESC' )
      ->limit( 1 )
      ->find();

//    echo $this->model->getLastSql();
    //验证数据是否找到
    if ( ! $data ) {
      $this->setError( '验证码未找到' );

      return FALSE;
    }
    
    //验证是否超时
    /*if ( time() - strtotime( $data['sent_at'] ) > $this->timeCap ) {
      $this->setError( '验证码超时' );

      return FALSE;
    }
    */
    return TRUE;
  }

//  public function sendResetPwd( $address ) {
//    $data = [
//      'address' => $address ,
//      'subject' => '重置 ' . config( 'custom.projectName' ) . '的用户密码' ,
//      'content' => $this->getCaptcha() ,
//    ];
//
//    //保存邮件到数据库
//    $result = $this->insert( $data );
//    if ( $result['code'] != 0 ) {
//      return $result;
//    }
//
//    $mail = $this->newMail( 'captcha' );
//    $mail->addAddress( $address );     // Add a recipient
//    $mail->Subject = $data['subject'];
//    $mail->Body = '<p>您请访问</p>';
//    $mail->Body    = "验证码为 <b>" . $data['captcha'] . "</b> 请于10分钟内验证";
//
//    //保存验证码到 数据库
//    if ( ! $mail->send() ) {
//      $this->setError( $mail->ErrorInfo );
//
//      return ajax_arr( '发送失败' , 500 );
//    }
//    //修改状态
//    $this->setMailSent( $result['data']['id'] );
//
//    return ajax_arr( '发送成功' , 500 );
//  }
  
}