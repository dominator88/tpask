<?php
return [
  'projectName' => '拿货宝' ,
  
  'copyright' => '2016 - 2017 &copy; Power by DMG Smart Project.' ,
  'icp'       => '鄂ICP备XXXXXX号' ,
  
  'areaCachePrefix' => 'area_' ,
  
  //upload type
  'uploadType'      => 'ftp' , //ftp or local
  
  //upload ftp 配置
  'imgUri'          => 'http://smart2.local.com/' ,
  
  //上传FTP设置
  'ftp'             => [
    'host'     => 'localhost' , //服务器
    'port'     => 21 , //端口
    'timeout'  => 30 , //超时时间
    'username' => 'sl' , //用户名
    'password' => 'yangtaosir' , //密码
    'pasv'     => TRUE ,
    'root_dir' => '/Applications/Mamp/htdocs/img/public/' ,
  ] ,
  
  'mail' => [
    'smtp'   => 'smtp.mxhichina.com' ,
    'port'   => 25 ,
    'member' => [
      'desc'     => '会员' ,
      'username' => 'xxx@example.com' ,
      'password' => 'password' ,
    ] ,
  ] ,
  
  'payChannel' => [
    'alipay' => '支付宝' ,
    'wx'     => '微信支付' ,
    'points' => '积分兑换' ,
  ] ,
  
  //api相关
  'Api'        => [
    'secret'  => 'nahuo_api_secret' ,
    'timeGap' => '300' ,
  ] ,
  
  //激光
  'JPush'      => [
    'appKey' => 'xxx' ,
    'secret' => 'xxx' ,
  ] ,
  
  //容联云
  'sms'        => [
    'serverIP'      => 'app.cloopen.com' ,
    'serverPort'    => '8883' ,
    'softVersion'   => '2013-12-26' ,
    'accountSid'    => 'xxx' ,
    'accountToken'  => 'xxx' ,
    'appId'         => 'xxx' ,
    'captchaTempId' => 'xxx' ,
  ] ,
  
  'PingPP' => [
    'appId'  => '' ,
    'secret' => '' ,
  ]

];