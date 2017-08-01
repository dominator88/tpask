<?php namespace apps\ask\controller;



class Setting extends Ask {
    public function __construct() {
        parent::__construct();
        $this->_initClassName( __CLASS__ );
    }


    /**
     * 页面显示接口
     *
     * @return string
     */
    public function index() {
        $this->_init( '设置' );

        $this->_addParam('uri' , [
            'website' => full_uri( 'ask/website/read' ),
            'email' => full_uri( 'ask/email/read' ),
            'register' => full_uri( 'ask/register/read' ),
            'points' => full_uri( 'ask/setting/points' ),
            'seo' => full_uri( 'ask/seo/read' ),
        ]);



        return $this->_displayWithLayout();
    }

    /**
     * 获取图表数据
     *
     * @param $stat
     *
     * @return array
     */
    private function _getCharts( $stat ) {
        $data = db( 'sys_statistics' )->order( 'created_at ASC' )->limit( 29 )->select();

        $period = [];
        $users  = [];
        $api    = [];
        foreach ( $data as $item ) {
            $period[] = substr( $item['created_at'], 5, 5 );
            $users[]  = $item['users_today'];
            $api[]    = $item['api'];
        }

        $period[] = date( 'm-d' );
        $users[]  = db( 'mer_user' )->whereTime( 'reg_at', '>', date( 'Y-m-d' ) )->count();
        $api[]    = $stat['api'];

        return [
            'users' => [
                'period' => $period,
                'data'   => $users,
            ],
            'api'   => [
                'period' => $period,
                'data'   => $api,
            ]
        ];
    }
}
