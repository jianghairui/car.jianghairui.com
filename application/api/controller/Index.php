<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2019/10/20
 * Time: 10:44
 */
namespace app\api\controller;

use think\Controller;
use think\Db;
use think\Debug;

class Index extends Controller {

    public function index() {
        try {
            $where = [
                ['status','=',1],
                ['del','=',0]
            ];
            $list = Db::table('mp_activity')->where($where)->select();
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        $this->assign('list',$list);
        return $this->fetch();
    }

    public function detail() {
        $id = input('param.id',0);
        try {
            $where = [
                ['id','=',$id]
            ];
            $exist = Db::table('mp_activity')->where($where)->find();
            if(!$exist) {
                die('未找到此活动');
            }
            $whereTag = [
                ['del','=',0]
            ];
            $tags = Db::table('mp_activity_tags')->where($whereTag)->select();
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        $this->assign('info',$exist);
        $this->assign('tags',$tags);
        return $this->fetch();
    }

    public function appoint() {
        $val['activity_id'] = input('post.activity_id');
        $val['name'] = input('post.name');
        $val['tel'] = input('post.tel');
        checkPost($val);
        $val['remark'] = input('post.remark');
        $val['create_time'] = time();
        $val['tag_ids'] = input('post.tag_ids',[]);
        try {
            $whereAc = [
                ['id','=',$val['activity_id']]
            ];
            $exist = Db::table('mp_activity')->where($whereAc)->find();
            if(!$exist) {
                return ajax('活动不存在',-1);
            }
            $val['tag_ids'] = implode(',',$val['tag_ids']);
            Db::table('mp_activity_order')->insert($val);
        } catch(\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax();
    }





























}