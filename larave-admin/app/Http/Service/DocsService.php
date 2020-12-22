<?php


namespace App\Http\Service;


use App\Models\Doc;

class DocsService
{
    public $docs_type = ['', '使用入门', '商标文档', '专利文档', '版权文档'];


    /**
     * 列表返回
     * @return mixed
     */
    public function show()
    {
        $info = Doc::get()->toArray();

        foreach ($info as $key => $value) {
            foreach ($this->docs_type as $item => $v) {
                if ($item == $value['type']) {

                    $info[$key]['type'] = $v;
                }
            }
        }
        return $info;
    }

    public function add_save($params)
    {
        $model = new Doc();
        $model->name = $params['name'];
        $model->type = $params['type'];
        $model->doc_path = $params['docs_path'];
        $state = $model->save();
        if ($state){
            return json_suc();
        }
        return json_err();

    }

    /**
     * 获取编辑页面的数据
     * @param $id
     * @return mixed
     */
    public function edit_info($id)
    {
        $data = Doc::where('id', $id)->get()->toArray();
        return $data;
    }

    /**
     * 编辑更新保存
     * @param $params
     * @return array
     */
    public function edit_save($params)
    {
        $model = new Doc();
        $state = $model->where('id', $params['id'])
            ->update([
                'name' => $params['name'],
                'type' => $params['type'],
                'sort' => $params['sort'],
                'doc_path' => $params['docs_path']
            ]);
        if ($state == 1) {
            return json_suc();
        }
        return json_err();

    }


    public function docs_del($id)
    {
        $state=Doc::find($id)->delete();//软删除;
        if ($state){
            return json_suc();
        }
        return json_err();

    }


}