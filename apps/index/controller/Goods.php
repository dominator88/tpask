<?php
namespace apps\index\controller;

use apps\common\controller\IndexBase;
use apps\common\model\MerGoods;
use apps\common\service\MerArticlesService;
use apps\common\service\MerGoodsProfileService;
use apps\common\service\MerGoodsService;
use apps\common\service\MerUserCommentsService;
use apps\common\service\MerUserFavoritesService;
use apps\common\service\MerUserLikesService;
use cebe\markdown\Markdown;

class Goods extends IndexBase {
  
  public function __construct() {
    parent::__construct();
    
    $this->_initClassName( __CLASS__ );
  }
  
  
  public function detail_for_api( $id ) {
    
    $this->_init( '商品详情 for api' );
    $MerGoods        = MerGoodsService::instance();
    $MerGoodsProfile = MerGoodsProfileService::instance();
    
    $MerGoods->incPv( $id );
    $goodsData = $MerGoods->getById( $id );
    if ( $goodsData['pid'] == 0 ) {
      $this->data['content'] = $MerGoodsProfile->getByGoodsId( $id )['content'];
    } else {
      $this->data['content'] = $MerGoodsProfile->getByGoodsId( $goodsData['pid'] )['content'];
    }
    
    
    return view( 'api' , $this->data );
    
  }
  
  /**
   * 商品详情
   *
   * @param $id
   *
   * @return \think\response\View
   */
  public function detail( $id ) {
    
    
  }
  
  
}
