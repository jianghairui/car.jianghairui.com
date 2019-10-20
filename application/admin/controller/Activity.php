<?php
/**
 * Created by PhpStorm.
 * User: JHR
 * Date: 2019/4/12
 * Time: 9:53
 */
namespace app\admin\controller;

use think\Db;
class Activity extends Base {

    public function activityList() {
        $param['search'] = input('param.search');
        $page['query'] = http_build_query(input('param.'));
        $curr_page = input('param.page',1);
        $perpage = input('param.perpage',10);
        $where = [];
        if($param['search']) {
            $where[] = ['title','like',"%{$param['search']}%"];
        }
        $count = Db::table('mp_activity')->alias('a')->where($where)->count();
        $page['count'] = $count;
        $page['curr'] = $curr_page;
        $page['totalPage'] = ceil($count/$perpage);
        try {
            $list = Db::table('mp_activity')
                ->where($where)
                ->order(['id'=>'DESC'])
                ->limit(($curr_page - 1)*$perpage,$perpage)->select();
        }catch (\Exception $e) {
            die('SQL错误: ' . $e->getMessage());
        }
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->assign('param',$param);
        return $this->fetch();
    }

    public function activityAdd() {
        return $this->fetch();
    }

    public function activityAddPost() {
        $val['title'] = input('post.title');
        $val['desc'] = input('post.desc');
        checkInput($val);
        $val['content'] = input('post.content');
        $val['create_time'] = date('Y-m-d H:i:s');

        if(isset($_FILES['file'])) {
            $info = upload('file');
            if($info['error'] === 0) {
                $val['pic'] = $info['data'];
            }else {
                return ajax($info['msg'],-1);
            }
        }else {
            return ajax('请上传封面图',-1);
        }
        try {
            Db::table('mp_activity')->insert($val);
        }catch (\Exception $e) {
            if(isset($val['pic'])) {
                @unlink($val['pic']);
            }
            return ajax($e->getMessage(),-1);
        }
        return ajax();
    }

    public function activityDetail() {
        $val['id'] = input('param.id',0);
        try {
            $exist = Db::table('mp_activity')->where('id','=',$val['id'])->find();
            if(!$exist) {
                die('非法操作');
            }
        } catch (\Exception $e) {
            die($e->getMessage());
        }
        $this->assign('info',$exist);
        return $this->fetch();
    }

    public function activityMod() {
        $val['title'] = input('post.title');
        $val['desc'] = input('post.desc');
        $val['id'] = input('post.id');
        checkInput($val);
        $val['content'] = input('post.content');
        if(isset($_FILES['file'])) {
            $info = upload('file');
            if($info['error'] === 0) {
                $val['pic'] = $info['data'];
            }else {
                return ajax($info['msg'],-1);
            }
        }
        $where = [
            ['id','=',$val['id']]
        ];
        try {
            $exist = Db::table('mp_activity')->where($where)->find();
            if(!$exist) {
                return ajax('非法参数',-1);
            }
            Db::table('mp_activity')->where($where)->update($val);
        }catch (\Exception $e) {
            if(isset($val['pic'])) {
                @unlink($val['pic']);
            }
            return ajax($e->getMessage(),-1);
        }
        if(isset($val['pic'])) {
            @unlink($exist['pic']);
        }
        return ajax([]);
    }

    //停用活动
    public function activityHide()
    {
        $val['id'] = input('post.id');
        checkInput($val);
        try {
            $exist = Db::table('mp_activity')->where('id','=',$val['id'])->find();
            if (!$exist) {
                return ajax('非法操作', -1);
            }
            Db::table('mp_activity')->where('id','=',$val['id'])->update(['status' => 0]);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }
    //启用活动
    public function activityShow() {
        $val['id'] = input('post.id');
        checkInput($val);
        try {
            $exist = Db::table('mp_activity')->where('id','=',$val['id'])->find();
            if(!$exist) {
                return ajax('非法操作',-1);
            }
            Db::table('mp_activity')->where('id','=',$val['id'])->update(['status'=>1]);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }

    //文章排序
    public function sortActivity() {
        $val['id'] = input('post.id');
        $val['sort'] = input('post.sort');
        checkInput($val);
        try {
            Db::table('mp_activity')->update($val);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax($val);
    }

    public function activityDel() {
        $val['id'] = input('post.id');
        checkInput($val);
        try {
            $exist = Db::table('mp_activity')->where('id','=',$val['id'])->find();
            if(!$exist) {
                return ajax('非法操作',-1);
            }
            Db::table('mp_activity')->where('id','=',$val['id'])->delete();
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        @unlink($exist['pic']);
        return ajax([],1);
    }
    


    //预约列表
    public function orderList() {
        $param['contact'] = input('param.contact','');
        $param['datemin'] = input('param.datemin');
        $param['datemax'] = input('param.datemax');
        $param['search'] = input('param.search');

        $page['query'] = http_build_query(input('param.'));

        $curr_page = input('param.page',1);
        $perpage = input('param.perpage',10);

        $where = [];

        if(!is_null($param['contact']) && $param['contact'] !== '') {
            $where[] = ['contact','=',$param['contact']];
        }
        if($param['datemin']) {
            $where[] = ['create_time','>=',strtotime(date('Y-m-d 00:00:00',strtotime($param['datemin'])))];
        }

        if($param['datemax']) {
            $where[] = ['create_time','<=',strtotime(date('Y-m-d 23:59:59',strtotime($param['datemax'])))];
        }

        if($param['search']) {
            $where[] = ['name|tel','like',"%{$param['search']}%"];
        }
        $order = ['id'=>'DESC'];
        try {
            $count = Db::table('mp_activity_order')->where($where)->count();
            $page['count'] = $count;
            $page['curr'] = $curr_page;
            $page['totalPage'] = ceil($count/$perpage);
            $list = Db::table('mp_activity_order')->where($where)
                ->order($order)
                ->limit(($curr_page - 1)*$perpage,$perpage)->select();
            $tag_list = Db::table('mp_activity_tags')->where('del','=',0)->select();
            $tag_arr = [];
            foreach ($tag_list as $v) {
                $tag_arr[$v['id']] = $v['tag_name'];
            }
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        foreach ($list as &$v) {
            $ids = explode(',',$v['tag_ids']);
            $tag_names = [];
            foreach ($ids as $vv) {
                if(isset($tag_arr[$vv])) {
                    $tag_names[] = $tag_arr[$vv];
                }
            }
            $v['tag_names'] = $tag_names;
        }
        $this->assign('list',$list);
        $this->assign('page',$page);
        $this->assign('param',$param);
        return $this->fetch();
    }

    public function signContact() {
        $id = input('post.id');
        try {
            $where = [
                ['id','=',$id]
            ];
            Db::table('mp_activity_order')->where($where)->update(['contact'=>1]);
        } catch (\Exception $e) {
            return ajax($e->getMessage(), -1);
        }
        return ajax();
    }


    //标签列表
    public function tagList() {
        $where = [
            ['del','=',0]
        ];
        try {
            $list = Db::table('mp_activity_tags')->where($where)->select();
        }catch (\Exception $e) {
            die($e->getMessage());
        }
        $this->assign('list',$list);
        return $this->fetch();
    }
//添加标签
    public function tagAdd() {
        return $this->fetch();
    }
//添加标签POST
    public function tagAddPost() {
        $val['tag_name'] = input('post.tag_name');
        checkPost($val);
        try {
            $count = Db::table('mp_activity_tags')->where('del','=',0)->count();
            if($count >= 10) {
                return ajax('最多添加10个',-1);
            }
            Db::table('mp_activity_tags')->insert($val);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax([]);
    }
//标签详情
    public function tagDetail() {
        $id = input('param.id');
        try {
            $info = Db::table('mp_activity_tags')->where('id',$id)->find();
        }catch (\Exception $e) {
            die($e->getMessage());
        }
        $this->assign('info',$info);
        return $this->fetch();
    }
//修改标签POST
    public function tagModPost() {
        $val['tag_name'] = input('post.tag_name');
        $val['id'] = input('post.id');
        checkPost($val);
        try {
            $exist = Db::table('mp_activity_tags')->where('id',$val['id'])->find();
            if(!$exist) {
                return ajax('非法参数',-1);
            }
            Db::table('mp_activity_tags')->where('id',$val['id'])->update($val);
        }catch (\Exception $e) {

            return ajax($e->getMessage(),-1);
        }
        return ajax([]);
    }
//删除标签
    public function tagDel() {
        $id = input('post.id');
        try {
            $exist = Db::table('mp_activity_tags')->where('id',$id)->find();
            if(!$exist) {
                return ajax('非法参数',-1);
            }
            Db::table('mp_activity_tags')->where('id','=',$id)->update(['del'=>1]);
        }catch (\Exception $e) {
            return ajax($e->getMessage(),-1);
        }
        return ajax();
    }


}