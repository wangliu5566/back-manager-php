<?php
namespace app\admin\controller;
use think\Controller;
use think\request;
use think\Db;

use think\Validate;

class Product extends Controller
{
    private $proName = [
        'proName'=>'chsAlphaNum'
    ];
    private $error = array(
                            0 => '请求方式错误！',
                            1 => '产品名称不能为空！',
                            2 => '服务名不能为空！',
                            3 => '分类ID不能为空！',
                            4 => '客户名不能为空！',
                            5 => '日期不能为空！',
                            6 => '产品名称不能含有非法字符！',
                            7 => '分类ID不正确，没有这个分类！',
                            8 => '新增成功！',
                            9 => '新增失败！',
                            10 => '修改成功！',
                            11 => '修改失败！',
                            12 => '删除失败！',
                            13 => '删除成功！',
                            14 => '产品ID不正确，没有这个产品！',
                          );
    //获取分类列表
    public function prod_list()
    {
        $keywords = request()->get('keywords') ? request()->get('keywords') : '';
        $cp = request()->get('cp') ? request()->get('cp') : 1;
        $ps = request()->get('ps') ? request()->get('ps') : 20;

        $arr = array();

        if(request()->get('cateId')) {
           $arr = Db::name('product')
               ->where('cate_id',request()->get('cateId'))
               ->where('project_name','like','%'.$keywords.'%')
               ->order('id')
               ->select();

           //统计数
           $count = Db::name('product')
               ->where('cate_id',request()->get('cateId'))
               ->where('project_name','like','%'.$keywords.'%')
               ->count();      
        }else {
           $arr = Db::name('product')
               ->where('project_name','like','%'.$keywords.'%')
               ->order('id','desc')
               ->page($cp,$ps)
               ->select();

           //统计数
           $count = Db::name('product')
               ->where('project_name','like','%'.$keywords.'%')
               ->count();    
        }

        $this->setResponse(200,'ok',$arr,$count);
    }

    //新增或修改分类
    public function createOrUpdate()
    {
        //参数 proId proName cateId  server client date isPublic
        //必须post访问
        if(request()->isGet()) {
          $this->setResponse(21,$this->error[0]);
          return;
        }

        //先迅速验证必填字段
        if(!request()->post('proName')) {
          $this->setResponse(21,$this->error[1]);
          return;
        }else if(!request()->post('cateId')) {
          $this->setResponse(21,$this->error[3]);
          return;
        }else if(!request()->post('server')) {
          $this->setResponse(21,$this->error[2]);
          return;
        }else if(!request()->post('client')) {
          $this->setResponse(21,$this->error[4]);
          return;
        }else if(!request()->post('date')) {
          $this->setResponse(21,$this->error[5]);
          return;
        }

        $va = new Validate($this->proName);

        //验证产品名
        if(!$va->check(request()->post('proName'))) {
          $this->setResponse(21,$this->error[6]);
          return;
        }

        //验证所属分类是否存在
        $cate = Db::name('category')->where('id',request()->post('cateId'))->select();

        if(!$cate) {
          $this->setResponse(21,$this->error[7]);
          return;
        }

        $arr = array(
          'project_name'=>request()->post('proName'),
          'cate_id'=>request()->post('cateId'),
          'server'=>request()->post('server'),
          'client'=>request()->post('client'),
          'date'=>request()->post('date'),
          'isPublic'=>request()->post('isPublic'),
          'date'=>request()->post('date'),
        );

        //判断有没有传入产品ID，如果有则修改，没有则新增
        if(!request()->post('proId')) {
          //新增操作
             $res = Db::name('product')->insert($arr);

             if($res == 1) {
                $proId = Db::name('product')->getLastInsID();
                $this->setResponse(200,$this->error[8],$proId);
             }else {
                $this->setResponse(21,$this->error[9]);
             }
        }else {
          //修改操作
             $res = Db::name('product')->where('id',request()->post('proId'))->update($arr);

             if($res !== 0) {
                $this->setResponse(200,$this->error[10]);
             }else {
                $this->setResponse(21,$this->error[11]);
             }
        }
    }

    //删除一个分类
    public function delete() 
    {
        //参数 proId
        //必须post访问
        if(request()->isGet()) {
          $this->setResponse(21,$this->error[0]);
          return;
        }

        //根据proId删除7
        if(!request()->post('proId')) {
           $this->setResponse(21,'产品ID不能为空！');
           return;
        }

        $res = Db::name('product')->where('id',request()->post('proId'))->delete();

        if($res == 0 ) {
           $this->setResponse(21,$this->error[12]);
        }else {
           $this->setResponse(200,$this->error[13]);
        }
    }


    //获取产品详情
    public function detail() {
        //根据proId查询
        if(!request()->get('proId') && !request()->post('proId')) {
           $this->setResponse(21,'产品ID不能为空！');
           return;
        }

        $proId = request()->get('proId') ? request()->get('proId') : request()->post('proId');

        $res = Db::name('product')->where('id',$proId)->select();

        if(count($res) == 0) {
           $this->setResponse(21,$this->error[14]);
        }else {
           $this->setResponse(200,'ok',$res);
        }
    }


    //获取首页产品列表及图片
    public function getIndexProductList() {
        //必须get访问
        if(request()->isPost()) {
          $this->setResponse(21,$this->error[0]);
          return;
        }


        $res = Db::name('product')->where('isPublic',1)->select();

        if(count($res) !== 0) {
          //依次找出对应的小图
          foreach ($res as $k => $v) {
             $res[$k]['coverImg'] = Db::name('images')
                                 ->where('pid',$v['id'])
                                 ->where('type',3)
                                 ->select();
          }
        }

        $this->setResponse(200,'ok',$res);
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
