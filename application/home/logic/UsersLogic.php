<?php
/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * Author: IT宇宙人
 * Date: 2015-09-09
 */

namespace app\home\logic;
use app\home\model\UserAddress;
use think\Model;
use think\Page;
use think\db;
/**
 * 分类逻辑定义
 * Class CatsLogic
 * @package Home\Logic
 */
class UsersLogic extends Model
{
    /*
     * 登陆
     */
    public function login($username,$password){
    	$result = array();
        if(!$username || !$password)
           $result= array('status'=>0,'msg'=>'请填写账号或密码');
        $user = M('users')->where("mobile",$username)->whereOr('email',$username)->find();
        if(!$user){
           $result = array('status'=>-1,'msg'=>'账号不存在!');
        }elseif(encrypt($password) != $user['password']){
           $result = array('status'=>-2,'msg'=>'密码错误!');
        }elseif($user['is_lock'] == 1){
           $result = array('status'=>-3,'msg'=>'账号异常已被锁定！！！');
        }else{
            //查询用户信息之后, 查询用户的登记昵称
            $levelId = $user['level'];
            $levelName = M("user_level")->where("level_id", $levelId)->getField("level_name");
            $user['level_name'] = $levelName;
          
           $result = array('status'=>1,'msg'=>'登陆成功','result'=>$user);
        }
        return $result;
    }

    /*
     * app端登陆
     */
    public function app_login($username,$password){
       
    	$result = array();
        if(!$username || !$password)
           $result= array('status'=>0,'msg'=>'请填写账号或密码');
        $user = M('users')->where("mobile|email","=",$username)->find();
        if(!$user){
           $result = array('status'=>-1,'msg'=>'账号不存在!');
        }elseif($password != $user['password']){
           $result = array('status'=>-2,'msg'=>'密码错误!');
        }elseif($user['is_lock'] == 1){
           $result = array('status'=>-3,'msg'=>'账号异常已被锁定！！！');
        }else{
            //查询用户信息之后, 查询用户的登记昵称
            $levelId = $user['level'];
            $levelName = M("user_level")->where("level_id", $levelId)->getField("level_name");
            $user['level_name'] = $levelName;            
            $user['token'] = md5(time().mt_rand(1,999999999));
            M('users')->where("user_id", $user['user_id'])->save(array('token'=>$user['token'],'last_login'=>time()));
            $result = array('status'=>1,'msg'=>'登陆成功','result'=>$user);
        }
        return $result;
    }    
    
    
    //绑定账号
    public function oauth_bind($data = array()){
    	$user = session('user');
    	if(empty($user['openid'])){
    		if(M('users')->where(array('openid'=>$data['openid']))->count()>0){
    			return array('status'=>-1,'msg'=>'您的'.$data['oauth'].'账号已经绑定过账号');
    		}else{
    			 M('users')->where(array('user_id'=>$user['user_id']))->save($data);
    			 return array('status'=>1,'msg'=>'绑定成功','result'=>$data);
    		}
    	}else{
    		return array('status'=>-1,'msg'=>'您的账号已绑定过，请不要重复绑定');
    	}
    }
    /*
     * 第三方登录
     */
    public function thirdLogin($data=array()){
        $openid = $data['openid']; //第三方返回唯一标识
        $oauth = $data['oauth']; //来源
        if(!$openid || !$oauth)
            return array('status'=>-1,'msg'=>'参数有误','result'=>'');
        //获取用户信息
        if(isset($data['unionid'])){
        	$map['unionid'] = $data['unionid'];
        	$user = get_user_info($data['unionid'],4,$oauth);
        }else{
        	$user = get_user_info($openid,3,$oauth);
        }  
        if(!$user){
            //账户不存在 注册一个
            $map['password'] = '';
            $map['openid'] = $openid;
            $map['nickname'] = $data['nickname'];
            $map['reg_time'] = time();
            $map['oauth'] = $oauth;
            $map['head_pic'] = $data['head_pic'];
            $map['sex'] = empty($data['sex']) ? 0 : $data['sex'];
            $map['token'] = md5(time().mt_rand(1,99999));
            $map['first_leader'] = cookie('first_leader'); // 推荐人id
            if($_GET['first_leader'])
                $map['first_leader'] = $_GET['first_leader']; // 微信授权登录返回时 get 带着参数的            
            // 如果找到他老爸还要找他爷爷他祖父等
            if($map['first_leader'])
            {
                $first_leader = M('users')->where("user_id", $map['first_leader'])->find();
                $map['second_leader'] = $first_leader['first_leader']; //  第一级推荐人
                $map['third_leader'] = $first_leader['second_leader']; // 第二级推荐人
            }else
			{
				$map['first_leader'] = 0;
			}                                    

            // 成为分销商条件  
            $distribut_condition = tpCache('distribut.condition'); 
            if($distribut_condition == 0)  // 直接成为分销商, 每个人都可以做分销        
                $map['is_distribut']  = 1;
                        
            $row_id = M('users')->insertGetId($map);
			// 会员注册送优惠券
			$coupon = M('coupon')->where("send_end_time > ".time()." and ((createnum - send_num) > 0 or createnum = 0) and type = 2")->select();
			foreach ($coupon as $key => $val)
			{
				// 送券            
				M('coupon_list')->add(array('cid'=>$val['id'],'type'=>$val['type'],'uid'=>$row_id,'send_time'=>time()));
				M('Coupon')->where("id", $val['id'])->setInc('send_num'); // 优惠券领取数量加一
			}
                        $user = M('users')->where("user_id", $row_id)->find();
			
        }else{
            $user['token'] = md5(time().mt_rand(1,999999999));
            M('users')->where("user_id", $user['user_id'])->save(array('token'=>$user['token'],'last_login'=>time()));
        }
        return array('status'=>1,'msg'=>'登陆成功','result'=>$user);
    }

    /**
     * 注册
     * @param $username  邮箱或手机
     * @param $password  密码
     * @param $password2 确认密码
     * @return array
     */
    public function reg($username,$password,$password2,$recommend_mobile='',$nickname){
    	$is_validated = 0 ;
        if(check_email($username)){
            $is_validated = 1;
            $map['email_validated'] = 1;
            $map['nickname'] = $map['email'] = $username; //邮箱注册
        }

        if(check_mobile($username)){
            $is_validated = 1;
            $map['mobile_validated'] = 1;
            $map['nickname'] = $map['mobile'] = $username; //手机注册
        }
        if (empty($recommend_mobile)) {
           return array('status'=>-1,'msg'=>'请填写正确的推荐人手机号');
        }
        if (!check_mobile($recommend_mobile)) {
            return array('status'=>-1,'msg'=>'请填写正确的推荐人手机号');
        }
        $recommend_user = M('users')->where(array('mobile'=>$recommend_mobile))->find();
        if (!$recommend_user) {
            return array('status'=>-1,'msg'=>'未找到该推荐人');
        }        
        if($is_validated != 1)
            return array('status'=>-1,'msg'=>'请用手机号或邮箱注册');

        if(!$username || !$password)
            return array('status'=>-1,'msg'=>'请输入用户名或密码');

        //验证两次密码是否匹配
        if($password2 != $password)
            return array('status'=>-1,'msg'=>'两次输入密码不一致');
        //验证是否存在用户名
        if(get_user_info($username,1)||get_user_info($username,2))
            return array('status'=>-1,'msg'=>'账号已存在');

        $map['password'] = encrypt($password);
        $map['reg_time'] = time();
        $map['first_leader'] = cookie('first_leader'); // 推荐人id
        $map['user_recommend_id'] = $recommend_user['user_id']; //推荐人id
        $map['user_recommend_mobile'] = $recommend_user['mobile']; //推荐人id
        $map['nickname'] = $nickname;
        // 如果找到他老爸还要找他爷爷他祖父等
        if($map['first_leader'])
        {
            $first_leader = M('users')->where("user_id", $map['first_leader'])->find();
            $map['second_leader'] = $first_leader['first_leader'];
            $map['third_leader'] = $first_leader['second_leader'];
        }else
		{
			$map['first_leader'] = 0;
		}              
        
        // 成为分销商条件  
        $distribut_condition = tpCache('distribut.condition'); 
        if($distribut_condition == 0)  // 直接成为分销商, 每个人都可以做分销        
            $map['is_distribut']  = 1;        
        
        $map['token'] = md5(time().mt_rand(1,99999));
       // dump($map);die;
        $user_id = M('users')->insertGetId($map);
        if($user_id === false)
            return array('status'=>-1,'msg'=>'注册失败');      
        $pay_points = tpCache('basic.reg_integral'); // 会员注册赠送积分
        if($pay_points > 0)
            accountLog($user_id, 0,$pay_points, '会员注册赠送积分'); // 记录日志流水
        // 会员注册送优惠券
        $coupon = M('coupon')->where("send_end_time > ".time()." and ((createnum - send_num) > 0 or createnum = 0) and type = 2")->select();
        if(!empty($coupon)){
        	foreach ($coupon as $key => $val)
        	{
        		M('coupon_list')->add(array('cid'=>$val['id'],'type'=>$val['type'],'uid'=>$user_id,'send_time'=>time()));
        		M('Coupon')->where("id", $val['id'])->setInc('send_num'); // 优惠券领取数量加一
        	}
        }
        $user = M('users')->where("user_id", $user_id)->find();
        return array('status'=>1,'msg'=>'注册成功','result'=>$user);
    }

     /*
      * 获取当前登录用户信息
      */
     public function get_info($user_id){
         if(!$user_id > 0)
             return array('status'=>-1,'msg'=>'缺少参数','result'=>'');
        $user_info = M('users')->where("user_id", $user_id)->find();
        if(!$user_info)
            return false;
         
         $user_info['coupon_count'] = M('coupon_list')->where(['uid'=>$user_id,'use_time'=>0])->count(); //获取优惠券列表
         $user_info['collect_count'] = M('goods_collect')->where(array('user_id'=>$user_id))->count(); //获取收藏数量         
                                    
         $user_info['waitPay']     = M('order')->where("user_id = :user_id ".C('WAITPAY'))->bind(['user_id'=>$user_id])->count(); //待付款数量
         $user_info['waitSend']    = M('order')->where("user_id = :user_id ".C('WAITSEND'))->bind(['user_id'=>$user_id])->count(); //待发货数量
         $user_info['waitReceive'] = M('order')->where("user_id = :user_id ".C('WAITRECEIVE'))->bind(['user_id'=>$user_id])->count(); //待收货数量
         $user_info['order_count'] = $user_info['waitPay'] + $user_info['waitSend'] + $user_info['waitReceive'];
         return array('status'=>1,'msg'=>'获取成功','result'=>$user_info);
     }
     
    /*
     * 获取最近一笔订单
     */
    public function get_last_order($user_id){
        $last_order = M('order')->where("user_id", $user_id)->order('order_id DESC')->find();
        return $last_order;
    }


    /*
     * 获取订单商品
     */
    public function get_order_goods($order_id){
        $sql = "SELECT og.* FROM __PREFIX__order_goods og LEFT JOIN __PREFIX__goods g ON g.goods_id = og.goods_id WHERE order_id = :order_id";
        $bind['order_id'] = $order_id;
        $goods_list = DB::query($sql,$bind);

        $return['status'] = 1;
        $return['msg'] = '';
        $return['result'] = $goods_list;
        return $return;
    }

    /*
     * 获取账户资金记录
     */
    public function get_account_log($user_id,$type=0){
        //查询条件
//        $type = I('get.type',0);
        if($type == 1){
            //收入
            $where = 'user_money > 0 OR pay_points > 0 AND user_id=:user_id';
        }
        if($type == 2){
            //支出
            $where = 'user_money < 0 OR pay_points < 0 AND user_id=:user_id';
        }
        $count = M('account_log')->where($where ? $where : 'user_id = :user_id')->bind(['user_id'=>$user_id])->count();
        $Page = new Page($count,16);
        $logs = M('account_log')->where($where ? $where : 'user_id = :user_id')->bind(['user_id'=>$user_id])->order('change_time desc')->limit($Page->firstRow.','.$Page->listRows)->select();

        $return['status'] = 1;
        $return['msg'] = '';
        $return['result'] = $logs;
        $return['show'] = $Page->show();

        return $return;
    }
    /*
     * 获取优惠券
     */
    public function get_coupon($user_id,$type =0 ){
        
        //查询条件
        //    $type = I('get.type',0);           
     

        $where = ' AND l.order_id = 0 AND c.use_end_time > '.time(); // 未使用
        if($type == 1){
            //已使用
            $where = ' AND l.order_id > 0 AND l.use_time > 0 ';
        }
        if($type == 2){
            //已过期
            $where = ' AND '.time().' > c.use_end_time ';
        }        
        //获取数量
        $sql = "SELECT count(l.id) as total_num FROM __PREFIX__coupon_list".
            " l LEFT JOIN __PREFIX__coupon".
            " c ON l.cid =  c.id WHERE l.uid = {$user_id} {$where}";
        $count = DB::query($sql);
        $count = $count[0]['total_num'];

        $Page = new Page($count,10);

        $sql = "SELECT l.*,c.name,c.money,c.use_end_time,c.condition FROM __PREFIX__coupon_list".
            " l LEFT JOIN __PREFIX__coupon".
            " c ON l.cid =  c.id WHERE l.uid = {$user_id} {$where}  ORDER BY l.send_time DESC,l.use_time LIMIT {$Page->firstRow},{$Page->listRows}";

        $logs = Db::query($sql);

        $return['status'] = 1;
        $return['msg'] = '获取成功';
        $return['result'] = $logs;
        $return['show'] = $Page->show();
        return $return;
    }

    /**
     * 获取商品收藏列表
     * @param $user_id  用户id
     */
    public function get_goods_collect($user_id){
        $count = M('goods_collect')->where(array('user_id'=>$user_id))->count();
        $page = new Page($count,10);
        $show = $page->show();
        //获取我的收藏列表
        $sql = "SELECT c.collect_id,c.add_time,g.goods_id,g.goods_name,g.shop_price,g.original_img,g.is_on_sale FROM __PREFIX__goods_collect c ".
            "inner JOIN __PREFIX__goods g ON g.goods_id = c.goods_id ".
            "WHERE c.user_id = ".$user_id.
            " ORDER BY c.add_time DESC LIMIT {$page->firstRow},{$page->listRows}";
        $result = Db::query($sql);
        $return['status'] = 3;
        $return['msg'] = '获取成功';
        $return['result'] = $result;
        $return['show'] = $show;        
        return $return;
    }

    /**
     * 获取评论列表
     * @param $user_id 用户id
     * @param $status  状态 0 未评论 1 已评论
     * @return mixed
     */
    public function get_comment($user_id,$status=2){
        if($status == 1){
            //已评论
            $count2 =DB::query("select count(*) as count from `__PREFIX__comment`  as c inner join __PREFIX__order_goods as g on c.goods_id = g.goods_id and c.order_id = g.order_id where c.user_id = $user_id");
            $count2 = $count2[0]['count'];
            
            $page = new Page($count2,10);
            $sql = "select c.*,g.*,(select order_sn from  __PREFIX__order where order_id = c.order_id ) as order_sn  from  __PREFIX__comment as c inner join __PREFIX__order_goods as g on c.goods_id = g.goods_id and c.order_id = g.order_id where c.user_id = $user_id order by c.add_time desc LIMIT {$page->firstRow},{$page->listRows}";
        }else{        	
        	$countsql = " select count(1) as comment_count from __PREFIX__order_goods as og
        	left join __PREFIX__order as o on o.order_id = og.order_id where o.user_id = $user_id  and og.is_send = 1 ";
        	$where = '';
        	if($status == 0){
        		$countsql .= $where = " and og.is_comment = 0 ";
        	}
        	$comment = DB::query($countsql);
        	$count1 = $comment[0][comment_count]; // 待评价
            $page = new Page($count1,3);
            $sql =" select *  from __PREFIX__order_goods as og 
            left join __PREFIX__order as o on o.order_id = og.order_id  where o.user_id = $user_id and og.is_send = 1  
            $where order by o.order_id desc  LIMIT {$page->firstRow},{$page->listRows}";            
        }
        
        $show = $page->show();
        $comment_list = DB::query($sql);
        if($comment_list){
        	$return['result'] = $comment_list;
        	$return['show'] = $show; //分页
        	return $return;
        }else{
        	return array();
        }
    }

    /**
     * 添加评论
     * @param $add
     * @return array
     */
    public function add_comment($add){
        if(!$add['order_id'] || !$add['goods_id'])
            return array('status'=>-1,'msg'=>'非法操作','result'=>'');
        
        //检查订单是否已完成
        $order = M('order')->where("order_id", $add['order_id'])->where('user_id', $add['user_id'])->find();
        if($order['order_status'] != 2)
            return array('status'=>-1,'msg'=>'该笔订单还未确认收货','result'=>'');

        //检查是否已评论过
        $goods = M('comment')->where("order_id", $add['order_id'])->where('goods_id', $add['goods_id'])->find();
        if($goods)            
            return array('status'=>-1,'msg'=>'您已经评论过该商品','result'=>'');        
        
        $row = M('comment')->add($add);
        if($row)
        {
            //更新订单商品表状态
            M('order_goods')->where(array('goods_id'=>$add['goods_id'],'order_id'=>$add['order_id']))->save(array('is_comment'=>1));
            M('goods')->where(array('goods_id'=>$add['goods_id']))->setInc('comment_count',1); // 评论数加一
            // 查看这个订单是否全部已经评论,如果全部评论了 修改整个订单评论状态            
            $comment_count   = M('order_goods')->where("order_id", $add['order_id'])->where('is_comment', 0)->count();
            if($comment_count == 0) // 如果所有的商品都已经评价了 订单状态改成已评价
            {
                M('order')->where("order_id",$add['order_id'])->save(array('order_status'=>4));
            }
            return array('status'=>1,'msg'=>'评论成功','result'=>'');
        }
        return array('status'=>-1,'msg'=>'评论失败','result'=>'');
    }

    /**
     * 邮箱或手机绑定
     * @param $email_mobile  邮箱或者手机
     * @param int $type  1 为更新邮箱模式  2 手机
     * @param int $user_id  用户id
     * @return bool
     */
    public function update_email_mobile($email_mobile,$user_id,$type=1){
        //检查是否存在邮件
        if($type == 1)
            $field = 'email';
        if($type == 2)
            $field = 'mobile';
        $condition['user_id'] = array('neq',$user_id);
        $condition[$field] = $email_mobile;

        $is_exist = M('users')->where($condition)->find();
        if($is_exist)
            return false;
        unset($condition[$field]);
        $condition['user_id'] = $user_id;
        $validate = $field.'_validated';
        M('users')->where($condition)->save(array($field=>$email_mobile,$validate=>1));
        return true;
    }

    /**
     * 更新用户信息
     * @param $user_id
     * @param $post  要更新的信息
     * @return bool
     */
    public function update_info($user_id,$post=array()){
        $model = M('users')->where("user_id", $user_id);
        $row = $model->setField($post);
        if($row === false)
           return false;
        return true;
    }

    /**
     * 地址添加/编辑
     * @param $user_id 用户id
     * @param $user_id 地址id(编辑时需传入)
     * @return array
     */
    public function add_address($user_id,$address_id=0,$data){
        $post = $data;
        if($address_id == 0)
        {
            $c = M('UserAddress')->where("user_id", $user_id)->count();
            if($c >= 20)
                return array('status'=>-1,'msg'=>'最多只能添加20个收货地址','result'=>'');
        }        
        
        //检查手机格式
        if($post['consignee'] == '')
            return array('status'=>-1,'msg'=>'收货人不能为空','result'=>'');
        if(!$post['province'] || !$post['city'] || !$post['district'])
            return array('status'=>-1,'msg'=>'所在地区不能为空','result'=>'');
        if(!$post['address'])
            return array('status'=>-1,'msg'=>'地址不能为空','result'=>'');
        if(!check_mobile($post['mobile']))
            return array('status'=>-1,'msg'=>'手机号码格式有误','result'=>'');

        //编辑模式
        if($address_id > 0){

            $address = M('user_address')->where(array('address_id'=>$address_id,'user_id'=> $user_id))->find();
            if($post['is_default'] == 1 && $address['is_default'] != 1)
                M('user_address')->where(array('user_id'=>$user_id))->save(array('is_default'=>0));
            $row = M('user_address')->where(array('address_id'=>$address_id,'user_id'=> $user_id))->save($post);
            if(!$row)
                return array('status'=>-1,'msg'=>'操作完成','result'=>'');
            return array('status'=>1,'msg'=>'编辑成功','result'=>'');
        }
        //添加模式
        $post['user_id'] = $user_id;
        
        // 如果目前只有一个收货地址则改为默认收货地址
        $c = M('user_address')->where("user_id", $post['user_id'])->count();
        if($c == 0)  $post['is_default'] = 1;
        
        $address_id = M('user_address')->add($post);
        //如果设为默认地址
        $insert_id = DB::name('user_address')->getLastInsID();
        $map['user_id'] = $user_id;
        $map['address_id'] = array('neq',$insert_id);
               
        if($post['is_default'] == 1)
            M('user_address')->where($map)->save(array('is_default'=>0));
        if(!$address_id)
            return array('status'=>-1,'msg'=>'添加失败','result'=>'');
        
        
        return array('status'=>1,'msg'=>'添加成功','result'=>$address_id);
    }

    /**
     * 添加自提点
     * @author dyr
     * @param $user_id
     * @param $post
     * @return array
     */
    public function add_pick_up($user_id, $post)
    {
        //检查用户是否已经有自提点
        $user_pickup_address_id = M('user_address')->where(['user_id'=>$user_id,'is_pickup'=>1])->getField('address_id');
        $pick_up = M('pick_up')->where(array('pickup_id' => $post['pickup_id']))->find();
        $post['address'] = $pick_up['pickup_address'];
        $post['is_pickup'] = 1;
        $post['user_id'] = $user_id;
        $user_address = new UserAddress();
        if (!empty($user_pickup_address_id)) {
            //更新自提点
            $user_address_save_result = $user_address->allowField(true)->validate(true)->save($post,['address_id'=>$user_pickup_address_id]);
        } else {
            //添加自提点
            $user_address_save_result = $user_address->allowField(true)->validate(true)->save($post);
        }
        if (false === $user_address_save_result) {
            return array('status' => -1, 'msg' => '保存失败', 'result' => $user_address->getError());
        } else {
            return array('status' => 1, 'msg' => '保存成功', 'result' => '');
        }
    }

    /**
     * 设置默认收货地址
     * @param $user_id
     * @param $address_id
     */
    public function set_default($user_id,$address_id){
        M('user_address')->where(array('user_id'=>$user_id))->save(array('is_default'=>0)); //改变以前的默认地址地址状态
        $row = M('user_address')->where(array('user_id'=>$user_id,'address_id'=>$address_id))->save(array('is_default'=>1));
        if(!$row)
            return false;
        return true;
    }

    /**
     * 修改密码
     * @param $user_id  用户id
     * @param $old_password  旧密码
     * @param $new_password  新密码
     * @param $confirm_password 确认新 密码
     */
    public function password($user_id,$old_password,$new_password,$confirm_password,$is_update=true){
        $data = $this->get_info($user_id);
        $user = $data['result'];
        if(strlen($new_password) < 6)
            return array('status'=>-1,'msg'=>'密码不能低于6位字符','result'=>'');
        if($new_password != $confirm_password)
            return array('status'=>-1,'msg'=>'两次密码输入不一致','result'=>'');
        //验证原密码
        if($is_update && ($user['password'] != '' && encrypt($old_password) != $user['password']))
            return array('status'=>-1,'msg'=>'密码验证失败','result'=>'');
        $row = M('users')->where("user_id", $user_id)->save(array('password'=>encrypt($new_password)));
        if(!$row)
            return array('status'=>-1,'msg'=>'修改失败','result'=>'');
        return array('status'=>1,'msg'=>'修改成功','result'=>'');
    }

    /**
     * 取消订单
     */
    public function cancel_order($user_id,$order_id){
        $order = M('order')->where(array('order_id'=>$order_id,'user_id'=>$user_id))->find();
        //检查是否未支付订单 已支付联系客服处理退款
        if(empty($order))
            return array('status'=>-1,'msg'=>'订单不存在','result'=>'');
        //检查是否未支付的订单
        if($order['pay_status'] > 0 || $order['order_status'] > 0)
            return array('status'=>-1,'msg'=>'支付状态或订单状态不允许','result'=>'');
        //获取记录表信息
        //$log = M('account_log')->where(array('order_id'=>$order_id))->find();
        //有余额支付的情况
        if($order['user_money'] > 0 || $order['integral'] > 0){
            accountLog($user_id,$order['user_money'],$order['integral'],"订单取消，退回{$order['user_money']}元,{$order['integral']}积分");
        }

        $row = M('order')->where(array('order_id'=>$order_id,'user_id'=>$user_id))->save(array('order_status'=>3));
				
        $data['order_id'] = $order_id;
        $data['action_user'] = $user_id;
        $data['action_note'] = '您取消了订单';
        $data['order_status'] = 3;
        $data['pay_status'] = $order['pay_status'];
        $data['shipping_status'] = $order['shipping_status'];
        $data['log_time'] = time();
        $data['status_desc'] = '用户取消订单';        
        M('order_action')->add($data);//订单操作记录

        if(!$row)
            return array('status'=>-1,'msg'=>'操作失败','result'=>'');
        return array('status'=>1,'msg'=>'操作成功','result'=>'');

    }
  
    /**
     * 发送验证码: 该方法只用来发送邮件验证码, 短信验证码不再走该方法
     * @param $sender 接收人
     * @param $type 发送类型
     * @return json
     */
    public function send_validate_code($sender,$type){
    	$sms_time_out = tpCache('sms.sms_time_out');
    	$sms_time_out = $sms_time_out ? $sms_time_out : 180;
    	//获取上一次的发送时间
    	$send = session('validate_code');
    	if(!empty($send) && $send['time'] > time() && $send['sender'] == $sender){
    		//在有效期范围内 相同号码不再发送
    		$res = array('status'=>-1,'msg'=>'规定时间内,不要重复发送验证码');
            return $res;
    	}
    	$code =  mt_rand(1000,9999);

		//检查是否邮箱格式
		if(!check_email($sender)){
			$res = array('status'=>-1,'msg'=>'邮箱码格式有误');
            return $res;
		}
		$send = send_email($sender,'验证码','您好，你的验证码是：'.$code);
    	
    	if($send){
    		$info['code'] = $code;
    		$info['sender'] = $sender;
    		$info['is_check'] = 0;
    		$info['time'] = time() + $sms_time_out; //有效验证时间
    		session('validate_code',$info);
    		$res = array('status'=>1,'msg'=>'验证码已发送，请注意查收');
    	}else{
    		$res = array('status'=>-1,'msg'=>'验证码发送失败,请联系管理员');
    	}
    	return $res;
    }
    
    /**
     * 检查短信/邮件验证码验证码
     * @param unknown $code
     * @param unknown $sender
     * @param unknown $session_id
     * @return multitype:number string
     */
    public function check_validate_code($code, $sender, $type ='email', $session_id=0 ,$scene = -1){
    	
        $timeOut = time();
        $inValid = true;  //验证码失效

        //短信发送否开启
        //-1:用户没有发送短信
        //空:发送验证码关闭
        $sms_status = checkEnableSendSms($scene);

        //邮件证码是否开启
        $reg_smtp_enable = tpCache('smtp.regis_smtp_enable');
        
        if($type == 'email'){            
            if(!$reg_smtp_enable){//发生邮件功能关闭
                $validate_code = session('validate_code');
                $validate_code['sender'] = $sender;
                $validate_code['is_check'] = 1;//标示验证通过
                session('validate_code',$validate_code);
                return array('status'=>1,'msg'=>'邮件验证码功能关闭, 无需校验验证码');
            }            
            if(!$code)return array('status'=>-1,'msg'=>'请输入邮件验证码');                
            //邮件
            $data = session('validate_code');
            $timeOut = $data['time'];
            if($data['code'] != $code || $data['sender']!=$sender){
            	$inValid = false;
            }  
        }else{
            if($scene == -1){
                return array('status'=>-1,'msg'=>'参数错误, 请传递合理的scene参数');
            }elseif($sms_status['status'] == 0){
                $data['sender'] = $sender;
                $data['is_check'] = 1; //标示验证通过
                session('validate_code',$data);
                return array('status'=>1,'msg'=>'短信验证码功能关闭, 无需校验验证码');
            } 
            
            if(!$code)return array('status'=>-1,'msg'=>'请输入短信验证码');
            //短信
            $sms_time_out = tpCache('sms.sms_time_out');
            $sms_time_out = $sms_time_out ? $sms_time_out : 180;
            $data = M('sms_log')->where(array('mobile'=>$sender,'session_id'=>$session_id , 'status'=>1))->order('id DESC')->find();
            if(is_array($data) && $data['code'] == $code){
            	$data['sender'] = $sender;
            	$timeOut = $data['add_time']+ $sms_time_out;
            }else{
            	$inValid = false;
            }           
        }
        
       if(empty($data)){
           $res = array('status'=>-1,'msg'=>'请先获取验证码');
       }elseif($timeOut < time()){
           $res = array('status'=>-1,'msg'=>'验证码已超时失效');
       }elseif(!$inValid)
       {
           $res = array('status'=>-1,'msg'=>'验证失败,验证码有误');
       }else{
            $data['is_check'] = 1; //标示验证通过
            session('validate_code',$data);
            $res = array('status'=>1,'msg'=>'验证成功');
        }
        return $res;
    }
     
    
    /**
     * @time 2016/09/01
     * @author dyr
     * 设置用户系统消息已读
     */
    public function setSysMessageForRead()
    {
        $user_info = session('user');
        if (!empty($user_info['user_id'])) {
            $data['status'] = 1;
            M('user_message')->where(array('user_id' => $user_info['user_id'], 'category' => 0))->save($data);
        }
    }
}