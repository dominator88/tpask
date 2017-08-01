<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\Route;

//接口
Route::rule( 'api/:version/:directory/[:action]' , 'api/index/index' );

//文章列表
Route::rule( 'articles/' , 'index/articles/index' );

//文章评论, 点赞, 收藏
Route::rule( 'article/comments/:id' , 'index/articles/comments' );
Route::rule( 'article/likes/:id' , 'index/articles/likes' );
Route::rule( 'article/favorites/:id' , 'index/articles/favorites' );

//文章详情
Route::rule( 'article/:id' , 'index/articles/detail' );

//商品详情
Route::rule( 'goods_api/:id' , 'index/goods/detail_for_api' );



//用户详情
Route::rule( 'user/:id' , 'index/users/detail' );

//用户验证相关
Route::group( 'auth' , [
  'signin'      => [ 'index/auth/signin' ] ,
  'signup'      => [ 'index/auth/signup' , ] ,
  'signout'     => [ 'index/auth/signout' , ] ,
  'sendcaptcha' => [ 'index/auth/sendCaptcha' ] ,
] );
//Route::rule( 'backend' , 'backend/index/index' );
//Route::miss( 'index/miss' );

return [

];
