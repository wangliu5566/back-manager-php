<?php
namespace app\admin\controller;
use think\Controller;
use think\request;
use think\Db;
use think\Validate;

class User extends Controller
{
    private $error = 21;
    private $success = 200;

	  private $loginRules = [
					    'account'  => 'require',
					    'password'   => 'require',
					];
    private $nickName = [
        'nickName'=>'chsAlphaNum'
    ];
    private $validate = null;

    public function __construct() {
       $this->validate = new Validate($this->loginRules);
    }  
    
    //用户登录
    public function login()
    {
       //验证请求方式和账号密码不能为空，账号只能为手机号
       if(!$this->validateAccount()) return;

       //匹配账号密码
       $arr = Db::name('users')->where('account',request()->post('account'))->find();

       if(!$arr) {
         $this->setResponse(21,'您还未注册！');
       }else if(request()->post('password') !== $arr['password']) {
         $this->setResponse(21,'账号或密码错误！');
       }else {
       	   unset($arr['password']);
       	   $this->setResponse(200,'登录成功',$arr);
       }
      
    }

    //用户注册
    public function reg() 
    {
      //验证请求方式和账号密码不能为空，账号只能为手机号
      if(!$this->validateAccount()) return;

      //验证手机号是否注册
      $arr = Db::name('users')->where('account',request()->post('account'))->find();
      if(count($arr) !== 0) {
         $this->setResponse(21,'该手机号已注册，请直接登录！');
         return;
      }

      $va = new Validate($this->nickName);


      //验证昵称
      if(request()->post('nickName')) {
         if(!$va->check(request()->post('nickName'))) {
            $this->setResponse(21,'昵称不能含有非法字符！');
            return;
         }
      }

      //验证年龄
      if((request()->post('age') && !is_numeric(request()->post('age'))) || request()->post('age') < 0) {
            $this->setResponse(21,'年龄只能为大于0的数字');
            return;
      }

      //验证性别
      if(request()->post('sex')) {
          if(!in_array(request()->post('sex'), array('男','女','保密'))) {
             $this->setResponse(21,'年龄只能为男、女、保密中的一个');
             return;
          }
      }


      //验证通过，存数据库
      $res = Db::name('users')->insert(request()->post());

      if($res == 1) {
         $userId = Db::name('users')->getLastInsID();
         $this->setResponse(200,'注册成功！',$userId);
      }else {
         $this->setResponse(21,'注册失败！');
      }
  
    }

    //获取用户列表
    public function userList()
    {
        $keywords = request()->get('keywords') ? request()->get('keywords') : '';
        $cp = request()->get('cp') ? request()->get('cp') : 1;
        $ps = request()->get('ps') ? request()->get('ps') : 20;

        $arr = Db::name('users')
               ->where('account|nickName','like','%'.$keywords.'%')
               ->order('id','desc')
               ->page($cp,$ps)
               ->select();

        //统计数
         $count = Db::name('users')
             ->where('account|nickName','like','%'.$keywords.'%')
             ->count();        
        $this->setResponse(200,'ok',$arr,$count);
    }

    //验证旧密码
    public function validOldPwd() 
    {
      if(request()->isGet()) {
        $this->setResponse(21,'请求方式错误！');
        return;
      }

      // 验证id
      if(!request()->post('userId')) {
        $this->setResponse(21,'用户编号不能为空！');
        return;
      }

      //验证旧密码是否传入
      if(!request()->post('oldPwd')) {
        $this->setResponse(21,'原始密码不能为空！');
        return;
      }

      $arr = Db::name('users')->where('id',request()->post('userId'))->select();

      //对比数据库
      if(!$arr) {
        $this->setResponse(21,'未获取到用户信息！');
      }else if($arr[0]['password'] !== request()->post('oldPwd')){
        $this->setResponse(21,'原始密码错误！');
      }else {
        $this->setResponse(200,'ok');
      }
    }

    //修改密码
    public function updatePwd()
    {
      if(!request()->isPost()) {
        $this->setResponse(21,'请求方式错误！');
        return;
      }

      //验证id
      if(!request()->post('userId')) {
        $this->setResponse(21,'用户编号不能为空！');
        return;
      }

      //验证新密码是否传入
      if(!request()->post('newPwd')) {
        $this->setResponse(21,'新密码不能为空！');
        return;
      }

      $arr = Db::name('users')->where('id',request()->post('userId'))->select();

      //对比数据库
      if(!$arr) {
        $this->setResponse(21,'未获取到用户信息，修改失败！');
      }else if($arr[0]['password'] == request()->post('newPwd')){
        $this->setResponse(21,'新密码不能与旧密码一致');

      }else if(Db::name('users')->where('id',request()->post('userId'))->update(['password'=>request()->post('newPwd')]) == 0){
        $this->setResponse(21,'修改密码失败！');
      }else {
        $this->setResponse(200,'修改成功！');
      }
    }

    //验证账号和密码是否传入
    private function validateAccount() 
    {
        //必须post访问
        if(request()->isGet()) {
          $this->setResponse(21,'请求方式错误！');
          return false;
        }

        $result = $this->validate->check(request()->post());

        if(!$result) {
           //验证账号密码不能为空
           $this->setResponse(21,'账号或密码不能为空！');
           return false;
        }

        if(!preg_match('/^1[34578]\d{9}$/', request()->post('account'))  ) {
           //验证手机号
           $this->setResponse(21,'请输入正确的手机号');
           return false;
        }

        return true;
    }

     //返回数据
    private function setResponse($code,$description,$data='',$recordCount=0) 
    {
        $res = array();
        switch ($code) {
            case 21:
                $res = array(
                   'code'=>$code,
                   'success'=>false,
                   'description'=>$description
                );
                break;
            case 200:
                $res = array(
                     'code'=>$code,
                     'success'=>true,
                     'description'=>$description,
                     'data'=>$data,
                  );
                if(is_array($data)) {
                  $res['recordCount'] = $recordCount;
                }
                
                break;
            
            default:
                # code...
                break;
        }

        print_r(json_encode($res,JSON_UNESCAPED_UNICODE ));
    }

}