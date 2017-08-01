<?php
namespace apps\common\model;

use think\Model;

class MerUser extends  Model {

	public function address() {
		return $this->hasMany('MerUserAddress', 'user_id');
	}
}