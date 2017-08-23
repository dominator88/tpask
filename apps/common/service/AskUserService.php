<?php namespace apps\common\service;

/**
 * AskUser Service
 *
 * @author  Zix
 * @version 2.0 2016-09-13
 */
use apps\ask\controller\Ask;
use think\Db;

class AskUserService extends BaseService {

    //引入 GridTable trait
    use \apps\common\traits\service\GridTable;

    const INSERT_CHECK_NICKNAME = TRUE; //注册是否检查昵称唯一
    const INSERT_CHECK_ASKCHANT = TRUE;  //注册是否检查机构唯一
    const INSERT_CHECK_EMAIL    = TRUE; //注册检查邮箱唯一

    public $sex = [
        0 => '未知' ,
        1 => '男' ,
        2 => '女'
    ];

    public $error = '';

    //'phone','qq','wx','wb','unknown'
    public $regFrom = [
        'phone'   => '手机号' ,
        'qq'      => 'QQ登录' ,
        'wx'      => '微信登录' ,
        'wb'      => '微博登录' ,
        'email'   => '邮箱' ,
        'unknown' => '未知'
    ];

    //状态
    public $status = [
        0 => '禁用' ,
        1 => '启用' ,
    ];

    //类实例
    private static $instance;

    //生成类单例
    public static function instance() {
        if ( self::$instance == NULL ) {
            self::$instance        = new AskUserService();
            self::$instance->model = db( 'AskUser' );
        }

        return self::$instance;
    }

    //取默认值
    function getDefaultRow() {
        return [
            'id'         => '' ,
            'mer_id'     => '3' ,
            'referee_id' => '0' ,
            'sex'        => '0' ,
            'username'   => '' ,
            'nickname'   => '' ,
            'password'   => '' ,
            'icon'       => '' ,
            'truename'   => '' ,
            'phone'      => '' ,
            'bucks'      => '0.00' ,
            'points'     => '0' ,
            'status'     => '1' ,
            'reg_from'   => 'unknown' ,
            'reg_device' => 'unknown' ,
            'reg_ip'     => '' ,
            'reg_at'     => date( 'Y-m-d H:i:s' ) ,
            'login_ip'   => '' ,
            'login_at'   => '' ,
            'for_test'   => '0' ,
        ];
    }

    /**
     * 根据条件查询
     *
     * @param $param
     *
     * @return array|number
     */
    function getByCond( $param ) {
        $default = [
            'field'      => [] ,
            'merId'      => '' ,
            'keyword'    => '' ,
            'phone'      => '' ,
            'username'   => '' ,
            'nickname'   => '' ,
            'email'      => '' ,
            'status'     => '' ,
            'page'       => 1 ,
            'pageSize'   => 10 ,
            'sort'       => 'id' ,
            'order'      => 'DESC' ,
            'excludeId'  => '' , //排除的ID
            'count'      => FALSE ,
            'getAll'     => FALSE ,
            'withoutPwd' => TRUE ,
        ];

        $param = extend( $default , $param );

        if ( ! empty( $param['keyword'] ) ) {
            $this->model->where( 'username|nickname|phone' , 'like' , "%{$param['keyword']}%" );
        }

        if ( $param['merId'] !== '' ) {
            $this->model->where( 'mer_id' , $param['merId'] );
        }

        if ( $param['phone'] !== '' ) {
            $this->model->where( 'phone' , $param['phone'] );
        }

        if ( $param['username'] !== '' ) {
            $this->model->where( 'username' , $param['username'] );
        }

        if ( $param['nickname'] !== '' ) {
            $this->model->where( 'nickname' , $param['nickname'] );
        }

        if ( $param['email'] !== '' ) {
            $this->model->where( 'email' , $param['email'] );
        }

        if ( $param['status'] !== '' ) {
            $this->model->where( 'status' , $param['status'] );
        }

        if ( $param['excludeId'] !== '' ) {
            $this->model->where( 'id' , 'neq' , $param['excludeId'] );
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
        if ( $param['withoutPwd'] ) {
            $data = $this->withoutPwd( $data );
        }

        return $data ? $data : [];
    }

    /**
     * 不包含密码
     *
     * @param $data
     *
     * @return mixed
     */
    private function withoutPwd( $data ) {
        foreach ( $data as &$item ) {
            unset( $item['password'] );
        }

        return $data;
    }

    /**
     * 查询测试用户
     *
     * @return mixed
     */
    public function getForTest() {
        return $this->model
            ->alias( 'u' )
            ->where( 'u.for_test' , 1 )
            ->join( 'ask_user_device ud' , 'ud.user_id = u.id' , 'left' )
            ->select();
    }

    /**
     * 检查手机号唯一
     *
     * @param $phone
     * @param string $merId
     * @param string $excludeId
     *
     * @return bool
     */
    public function checkPhoneUnique( $phone , $merId = '' , $excludeId = '' ) {
        if ( empty( trim( $phone ) ) ) {
            $this->error = '请填写手机号';

            return FALSE;
        }
        $param = [
            'excludeId' => $excludeId ,
            'phone'     => $phone ,
            'count'     => TRUE
        ];

        if ( self::INSERT_CHECK_ASKCHANT ) {
            $param['merId'] = $merId;
        }

        if ( $this->getByCond( $param ) > 0 ) {
            $this->error = '手机号已经被注册了';

            return FALSE;
        }

        return TRUE;
    }

    /**
     * 检查昵称唯一
     *
     * @param $nickname
     * @param string $merId
     * @param string $excludeId
     *
     * @return bool
     */
    public function checkNicknameUnique( $nickname , $merId = '' , $excludeId = '' ) {
        if ( ! self::INSERT_CHECK_NICKNAME ) {
            return TRUE;
        }

        $param = [
            'excludeId' => $excludeId ,
            'nickname'  => $nickname ,
            'count'     => TRUE
        ];

        if ( self::INSERT_CHECK_ASKCHANT ) {
            $param['merId'] = $merId;
        }

        if ( $this->getByCond( $param ) > 0 ) {
            $this->error = '昵称已经被注册了';

            return FALSE;
        }

        return TRUE;
    }

    /**
     * 检查昵称唯一
     *
     * @param $username
     * @param string $merId
     * @param string $excludeId
     *
     * @return bool
     */
    public function checkUsernameUnique( $username , $merId = '' , $excludeId = '' ) {

        $param = [
            'excludeId' => $excludeId ,
            'username'  => $username ,
            'count'     => TRUE
        ];

        if ( self::INSERT_CHECK_ASKCHANT ) {
            $param['merId'] = $merId;
        }

        if ( $this->getByCond( $param ) > 0 ) {
            $this->error = '用户名已经被注册了';

            return FALSE;
        }

        return TRUE;
    }

    public function checkEmailUnique( $email , $merId = '' , $excludeId = '' ) {
        if ( ! self::INSERT_CHECK_EMAIL ) {
            return TRUE;
        }

        $param = [
            'excludeId' => $excludeId ,
            'email'     => $email ,
            'count'     => TRUE
        ];

        if ( self::INSERT_CHECK_ASKCHANT ) {
            $param['merId'] = $merId;
        }

        $count = $this->getByCond( $param );
        if ( $count > 0 ) {
            $this->error = 'Email已经被占用了';

            return FALSE;
        }

        return TRUE;
    }

    private function makeUsernameByPhone( $phone ) {
        $phoneMark = substr_replace( $phone , '****' , 3 , 4 );

        return 'user-' . $phoneMark . '-' . rand_string();
    }

    private function makeUsernameByEmail( $email ) {

    }

    /**
     * 后台添加用户 根据Phone
     *
     * @param $data
     *
     * @return array
     */
    public function insert( $data ) {
        //检查phone
        if ( isset( $data['phone'] ) && ! $this->checkPhoneUnique( $data['phone'] , $data['mer_id'] ) ) {
            return ajax_arr( $this->error , 500 );
        }

        //如果用户名不存在 就创建一个用户名
        if ( ! isset( $data['username'] ) || empty( $data['username'] ) ) {
            $data['username'] = $this->makeUsernameByPhone( $data['phone'] );
        }

        //检查邮箱唯一
        if ( ! $this->checkEmailUnique( $data['username'] , $data['mer_id'] ) ) {
            return ajax_arr( $this->error , 500 );
        }

        //检查用户名唯一性
        if ( ! $this->checkUsernameUnique( $data['username'] , $data['mer_id'] ) ) {
            return ajax_arr( '用户名已经被占用了' , 500 );
        }

        //如果不存在密码 就用默认密码
        if ( ! isset( $data['password'] ) || empty( $data['password'] ) ) {
            $data['password'] = str2pwd( config( 'defaultPwd' ) );
        }

        //如果昵称不存在 就等于 username
        if ( ! isset( $data['nickname'] ) || empty( $data['nickname'] ) ) {
            $data['nickname'] = $data['username'];
        }

        //检查nickname 唯一
        if ( ! $this->checkNicknameUnique( $data['nickname'] , $data['mer_id'] ) ) {
            return ajax_arr( $this->error , 500 );
        }

        //地址
        if ( ! isset( $data['reg_ip'] ) || empty( $data['reg_ip'] ) ) {
            $data['reg_ip'] = request()->ip( 0 , TRUE );
        }

        try {
            $id = $this->model->insertGetId( $data );

            return ajax_arr( '添加成功' , 0 , [ 'id' => $id ] );
        } catch ( \Exception $e ) {
            return ajax_arr( $e->getMessage() , 500 );
        }
    }

    /**
     * 根据邮箱创建用户
     *
     * @param $data
     *
     * @return array
     */
    public function insertByEmail( $data ) {
        $oldData = $this->getByCond( [
            'email' => $data['email']
        ] );

        if ( ! empty( $oldData ) ) {
            return ajax_arr( '邮件已经被注册了' , 500 );
        }

        //如果不存在密码 就用默认密码
        if ( ! isset( $data['password'] ) || empty( $data['password'] ) ) {
            $data['password'] = str2pwd( config( 'defaultPwd' ) );
        }


        //地址
        if ( ! isset( $data['reg_ip'] ) || empty( $data['reg_ip'] ) ) {
            $data['reg_ip'] = request()->ip( 0 , TRUE );
        }

        $data['username'] = $data['email'];
        $data['nickname'] = $data['email'];

        try {
            $id = $this->model->insertGetId( $data );

            return ajax_arr( '添加成功' , 0 , [ 'id' => $id ] );
        } catch ( \Exception $e ) {
            return ajax_arr( $e->getMessage() , 500 );
        }
    }

    /**
     * 第三方用户登录
     *
     * @param $metaData
     *
     * @return array
     */
    public function insertBySns( $metaData ) {
        $username = $metaData['platform'] . '_' . $metaData['snsUid'];

        $data = [
            'mer_id'   => $metaData['merId'] ,
            'username' => $username ,
            'nickname' => $metaData['username'] ,
            'password' => str2pwd( rand_string( 8 ) ) ,
            'reg_ip'   => request()->ip( 0 , TRUE ) ,
            'reg_from' => $metaData['platform'] ,
        ];
        if ( $metaData['gender'] !== '' ) {
            $data['sex'] = $metaData['gender'];
        }

        if ( ! empty( $metaData['icon'] ) ) {
            $data['icon'] = $metaData['icon'];
        }

        Db::startTrans();
        try {
            $id = $this->model->insertGetId( $data );
            if ( $id <= 0 ) {
                throw new \Exception( '创建用户失败' );
            }
            $snsData = [
                'platform' => $metaData['platform'] ,
                'user_id'  => $id ,
                'sns_uid'  => $metaData['snsUid'] ,
                'username' => $metaData['username'] ,
                'icon'     => $metaData['icon'] ,
                'gender'   => $metaData['gender'] ,
                'province' => $metaData['province'] ,
                'city'     => $metaData['city'] ,
            ];

            db( 'ask_user_sns' )->insert( $snsData );

            $data['id']    = $id;
            $data['phone'] = '';

            Db::commit();

            return ajax_arr( '添加成功' , 0 , $data );
        } catch ( \Exception $e ) {
            Db::rollback();

            return ajax_arr( $e->getMessage() , 500 );
        }
    }

    /**
     * 修改用户密码
     *
     * @param $id
     * @param $oldPwd
     * @param $pwd
     *
     * @return array
     */
    public function updatePwd( $id , $oldPwd , $pwd ) {
        try {
            $userData = $this->getById( $id );
            if ( empty( $userData ) ) {
                return ajax_arr( '用户未找到' , 504 );
            }

            if ( ! password_verify( $oldPwd , $userData['password'] ) ) {
                return ajax_arr( '原密码不正确' , 400 );
            }

            $rows = $this->model->where( 'id' , $id )->update( [
                'password' => str2pwd( $pwd )
            ] );
            if ( $rows == 0 ) {
                return ajax_arr( '未修改任何数据' , 0 );
            }

            return ajax_arr( '修改成功' , 0 );
        } catch ( \Exception $e ) {
            return ajax_arr( $e->getMessage() , 500 );
        }
    }

    public function getByPhone( $merId , $phone ) {
        $data = $this->model
            ->where( 'mer_id' , $merId )
            ->where( 'phone' , $phone )
            ->find();

        return $data ? $data : [];
    }

    public function getByEmail( $merId , $email ) {
        $data = $this->model
            ->where( 'mer_id' , $merId )
            ->where( 'email' , $email )
            ->find();

        return $data ? $data : [];
    }

    /**
     *
     *
     * @param $merId
     * @param $phone
     * @param $pwd
     *
     * @return array
     */
    public function resetPwdByPhone( $merId , $phone , $pwd ) {
        try {
            $this->model
                ->where( 'mer_id' , $merId )
                ->where( 'phone' , $phone )
                ->update( [
                    'password' => str2pwd( $pwd )
                ] );

            return ajax_arr( '重置密码成功' , 0 );
        } catch ( \Exception $e ) {
            return ajax_arr( $e->getMessage() , 500 );
        }
    }

    public function resetPwdByEmail( $merId , $email , $pwd ) {
        try {
            $this->model
                ->where( 'mer_id' , $merId )
                ->where( 'email' , $email )
                ->update( [
                    'password' => str2pwd( $pwd )
                ] );

            return ajax_arr( '重置密码成功' , 0 );
        } catch ( \Exception $e ) {
            return ajax_arr( $e->getMessage() , 500 );
        }
    }

    /**
     * 后台更新用户信息
     *
     * @param $id
     * @param $data
     *
     * @return array
     */
    public function update( $id , $data ) {
        //检查phone
        if ( isset( $data['phone'] ) &&
            ! $this->checkPhoneUnique( $data['phone'] , $data['mer_id'] , $id )
        ) {
            return ajax_arr( $this->error , 500 );
        }

        if ( isset( $data['nickname'] ) && empty( $data['nickname'] ) ) {
            $data['nickname'] = $data['username'];
        }

        //检查nickname
        if ( isset( $data['nickname'] ) &&
            ! $this->checkNicknameUnique( $data['nickname'] , $data['mer_id'] , $id )
        ) {
            return ajax_arr( $this->error , 500 );
        }

        try {
            $rows = $this->model->where( 'id' , $id )->update( $data );
            if ( $rows == 0 ) {
                return ajax_arr( '未修改任何数据' , 0 );
            }

            return ajax_arr( '修改成功' , 0 );
        } catch ( \Exception $e ) {
            return ajax_arr( $e->getMessage() . $this->model->getLastSql() , 500 );
        }
    }

    /**
     * 后台重置密码
     *
     * @param $id
     * @param $pwd
     *
     * @return array
     */
    public function resetPwd( $id , $pwd ) {
        try {
            $this->model->where( 'id' , $id )->update( [
                'password' => str2pwd( $pwd )
            ] );

            return ajax_arr( '重置密码成功' , 0 );
        } catch ( \Exception $e ) {
            return ajax_arr( $e->getMessage() , 500 );
        }
    }

    /**
     * 获取用户零钱
     *
     * @param $id
     *
     * @return mixed
     */
    public function getBucksByUser( $id ) {
        return $this->model
            ->where( 'id' , $id )
            ->value( 'bucks' );
    }

    /**
     * 根据订单支付零钱
     *
     * @param $userId
     * @param $payAmount
     * @param string $orderNo
     *
     * @return array
     */
    public function payByOrder( $userId , $payAmount , $orderNo = '' ) {
        $bucks = $this->getBucksByUser( $userId );

        if ( $bucks < $payAmount ) {
            return ajax_arr( '零钱余额不足' , 500 );
        }

        try {
            $this->model->where( 'id' , $userId )->update( [
                'bucks' => [ 'exp' , "bucks - $payAmount" ]
            ] );

            //写用户流水
            $AskUserFlow = AskUserFlowService::instance();
            $resultFlow  = $AskUserFlow->payBucksByOrder( $userId , $bucks , $payAmount , $orderNo );

            if ( $resultFlow['code'] != 0 ) {
                return $resultFlow;
            }

            return ajax_arr( '扣零钱成功' , 0 );
        } catch ( \Exception $e ) {
            return ajax_arr( '扣零钱失败' , 500 );
        }
    }

    /**
     * 根据订单退款
     *
     * @param $userId
     * @param $amount
     * @param $orderNo
     *
     * @return array
     */
    public function refundByOrder( $userId , $amount , $orderNo ) {
        $bucks = $this->getBucksByUser( $userId );

        try {
            $ret = $this->model->where( 'id' , $userId )->update( [
                'bucks' => [ 'exp' , "bucks + $amount" ]
            ] );

            if ( $ret > 0 ) {
                //写用户流水
                $AskUserFlow = AskUserFlowService::instance();
                $AskUserFlow->refundBucksByOrder( $userId , $bucks , $amount , $orderNo );
            }

            return ajax_arr( '退款成功' , 0 );
        } catch ( \Exception $e ) {
            return ajax_arr( '退款失败1' , 500 );
        }

    }

    /**
     * 用户绑定邮箱
     *
     * @param $merId
     * @param $userId
     * @param $email
     *
     * @return array
     */
    public function bindEmail( $merId , $userId , $email ) {
        $oldData = $this->model
            ->where( 'mer_id' , $merId )
            ->where( 'id' , '<>' , $userId )
            ->where( 'email' , $email )
            ->find();

        if ( $oldData ) {
            return ajax_arr( '邮箱已经被占用了' , 500 );
        }
        try {
            $this->model
                ->where( 'id' , $userId )
                ->update( [
                    'email' => $email
                ] );

            return ajax_arr( '绑定邮箱成功' , 0 );
        } catch ( \Exception $e ) {
            return ajax_arr( $e->getMessage() , 500 );
        }
    }

    /**
     * 用户绑定手机
     *
     * @param $merId
     * @param $userId
     * @param $phone
     *
     * @return array
     */
    public function bindPhone( $merId , $userId , $phone ) {
        $oldData = $this->model
            ->where( 'mer_id' , $merId )
            ->where( 'id' , '<>' , $userId )
            ->where( 'phone' , $phone )
            ->find();

        if ( $oldData ) {
            return ajax_arr( '手机号已经被占用了' , 500 );
        }
        try {
            $this->model
                ->where( 'id' , $userId )
                ->update( [
                    'phone' => $phone
                ] );

            return ajax_arr( '绑定手机号成功' , 0 );
        } catch ( \Exception $e ) {
            return ajax_arr( $e->getMessage() , 500 );
        }
    }

    /**
     * 用户悬赏金币验证
     * @param $userId
     * @param $price
     * @return bool
     */
    public function checkPrice($userId , $price){
        $userData = $this->getById($userId);
        return $userData['price'] >= $price;
    }

    /**
     * 减少用户金币
     * @param $userId
     * @param $price
     * @return bool
     */
    public function decPrice($userId , $price){
        $flag = $this->checkPrice($userId , $price);

        if( $flag ){
            $flag_price = $this->model->where('id'  , $userId)->setDec('price' , $price);
            if(!$flag_price){
                exception('操作金币失败' , 500);
            }
          return true;
        }else{
            exception('余额不足' ,500);
        }

    }


    /**
     * 增加用户金币
     * @param $userId
     * @param $price
     * @return bool
     */
    public function incPrice($userId , $price){
        $flag_price = $this->model->where('id'  , $userId)->setDec('price' , $price);
        if(!$flag_price){
           exception( '操作金币失败' , 500);
        }
        return true;
    }



}