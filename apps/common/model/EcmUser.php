<?php
namespace apps\common\model;


use think\Model;

class EcmUser extends Model
{
    public function profile()
    {
        return $this->hasOne('Profile','id','user_id')->field('id,truename,email');
    }
}