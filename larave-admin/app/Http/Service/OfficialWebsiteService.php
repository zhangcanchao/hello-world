<?php


namespace App\Http\Service;


use App\Models\Information;

class OfficialWebsiteService
{
    public $news_state = ['未发布', '已发布'];
    public $news_type = ['新闻资讯'];

    /**
     * 页面数据
     * @return mixed
     */
    public function show(){
        $info=Information::orderBy('sort', 'desc')
            ->orderBy('created_at','desc')
            ->get()
            ->toArray();

        $state=$this->news_state;
        $type=$this->news_type;
        foreach ($info as $key=>$value){
            foreach ($state as $k=>$v){
                if ($k==$value['status']){
                    $info[$key]['status'] = $v;
                }
            }
        }
        foreach ($info as $key=>$value){
            foreach ($type as $k=>$v){
                if ($k==$value['type']){
                    $info[$key]['type'] = $v;
                }
            }
        }

        foreach ($info as $k=>$v){
            if(!empty($v['publish_time'])){
                $info[$k]['publish_time']=date("Y-m-d H:i",$v['publish_time']);
            }
        }
        return $info;
    }


    /**
     * 添加信息
     * @param $params
     * @return array
     */
    public function add($params){

        $model=new Information();

        $model->title = $params['title'];
        $model->url   = $params['img'];
        $model->count = $params['count'];
        $model->status= $params['issue'];
        $model->sort  =0;

        if($params['issue']==1){
            $model->publish_time = strtotime($params['time']);
        }

        if(!empty($params['id'])){
            $model->exists = true;
            $model->id = $params['id'];
        }

        if($model->save()){
            return ['status'=>1,'msg'=>'操作成功'];
        }
        return ['status'=>0,'msg'=>'操作失败'];

    }

    /**
     * 获取编辑信息
     * @param $id
     * @return mixed
     */
    public function geteditInfo($id){
        $info=Information::find($id)->toArray();
        return $info;
    }

    /**
     * 软删除
     * @param $id
     * @return array
     */
    public function del($id){
        $model = Information::find($id);
        if ($model->delete()) {
            return ['status'=>1,'msg'=>'操作成功'];
        } else {
            return ['status'=>0,'msg'=>'操作失败'];
        }
    }




}