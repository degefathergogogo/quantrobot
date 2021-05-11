<?php
namespace app\admin\controller;
use api\wallet\service\Apibase;
use api\wallet\service\ColumnName;
use cmf\controller\AdminBaseController;
use think\Db;
use think\Validate;


class PushController extends AdminBaseController
{
    public function index(){
        $data=  Db::name('PushLog')->where(['status'=>['gt',0]])->order('id desc')->paginate(15);
        // 获取分页显示
        $page = $data->render();
        $this->assign('type', ColumnName::$db_push_log['type'] );
        $this->assign('status', ColumnName::$db_push_log['status'] );
        $this->assign('datas', $data->toArray()['data'] );
        $this->assign("page", $page);
        return $this->fetch();
    }
    public function del(){
        $id= $this->request->param('id');
        $is_del=  Db::name('PushLog')->where(['id' => $id])->update(['status'=>0]);
        if( $is_del ){
            $this->success('删除成功！');
        }else{
            $this->error('删除失败，请重试！');
        }
    }

    public function push(){
        $id= $this->request->param('id');
        $data = Db::name('PushLog')->where(['id' => $id])->find();
        if (empty($data)){
            $this->error('未找到推送内容');
        }else {
            if ($data['status'] == 0) {
                $this->error( '不能推送已删除内容');
            }
            if ($data['user_id'] > 0){
                $regs = Db::name("UserDeviceRegistration")->where(['user_id'=>$data['user_id']])->field('user_id,registration_id,os')->find();
                if (empty($regs)){
                    $this->error( '推送对象异常');
                }
                $ret = Apibase::jpush_send_push($data['title'],$data['content'],$data['type'],$data['url'],$regs['registration_id'],$regs['os']);
            }else{
                $ret = Apibase::jpush_send_push($data['title'],$data['content'],$data['type'],$data['url'],0);
            }
        }
        if ($ret['code'] == 1){
            Db::name('PushLog')->where(['id' => $id])->update(['status'=>2,'deal_time'=>time()]);
            $this->success($ret['msg']);
        }else{
            $this->error($ret['msg']);
        }
    }

    public function add(){
        if ($this->request->isPost()) {
            $validate = new Validate([
                'title'     => 'require',
                'content'     => 'require',
            ]);

            $validate->message([
                'title.require'  => '标题不能为空!',
                'content.require'  => '内容不能为空!',
            ]);

            $data = $this->request->param();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            if ($data['type'] == 3){
                $uinfo = Db::name("user")->where(['id'=>$data['user_id'],'user_status'=>1])->find();
                if (empty($uinfo)){
                    $this->error("指定用户不存在");
                }
            }
            $log = Db::name("PushLog")->insertGetId($data);
            if ($log){
                $this->success("提交成功",url('Push/index'));
            }else{
                $this->error("提交失败");
            }
        }
        return $this->fetch();
    }

}