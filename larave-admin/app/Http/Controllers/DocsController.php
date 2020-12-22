<?php
/**
 * 后台文档中心
 * @author     chaoquan
 * @Time: 2019/12/14
 * @version     2.0 版本号
 */
namespace App\Http\Controllers;

use App\Http\Service\DocsService;
use Illuminate\Http\Request;

class DocsController extends Controller
{
    private $docsService;

    public function __construct(Request $request,DocsService $docsService)
    {
        parent::__construct($request);
        $this->docsService=$docsService;
    }

    /**
     * 视图信息返回 展示
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(){
        return view('officialWebsite.docs.list');
    }

    /**
     * 显示文档表格
     * @return false|string
     */
    public function show(){
        $date=$this->docsService->show();

        $json = json_encode(array(
            'code' => 0,
            'msg' => '',
            'count' => count($date),
            'data' => $date
        ));
        return $json;
    }

    /**
     * 添加文档视图返回
     */
    public function add_view(){
        return view('officialWebsite.docs.add',[
            'type'=>$this->docsService->docs_type
        ]);
    }

    /**
     * 文件上传
     * @return array
     */
    public function upload(){
//        同名称的文件会覆盖之前的文件
        $file = $this->request->file('file');
        $name = $file->getClientOriginalName();
        $path = $file->storeAs('doc', $name, 'public');
        return json_suc(['file_path' => $path, 'file_url' => get_file_url($path)]);
    }

    /**
     * 添加文档
     * @return array
     */
    public function add_save(){
        return $this->docsService->add_save($this->params);
    }

    /**
     * 返回编辑视图和编辑的信息
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit_view(){
        $id=$_GET['id'];
        return view('officialWebsite.docs.edit',[
            'type'=>$this->docsService->docs_type,
            'info'=>$this->docsService->edit_info($id),
        ]);
    }

    /**
     * 编辑更新保存
     * @return array
     */
    public function edit_save(){
       return $this->docsService->edit_save($this->params);
    }

    public function docs_del(){
        $id=$_GET['id'];
        return $this->docsService->docs_del($id);
    }




}