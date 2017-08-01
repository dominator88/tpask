<?php
namespace apps\common\model;


use think\Model;

class EcmMember extends Model {

    public function profile(){

        return $this->hasOne('EcmMemberToken','user_id','user_id')->field('token');

    }
}
