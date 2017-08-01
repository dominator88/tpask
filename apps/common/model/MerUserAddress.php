<?php
namespace apps\common\model;

use think\Model;

class MerUserAddress extends Model {
	
	public function merUser() {
		return $this->belongsTo( 'MerUser' );
	}
	
}