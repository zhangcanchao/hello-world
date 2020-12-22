<?php

namespace App\Http\Service;

use App\Models\Goods;
use App\Models\ServeType;
use App\Api\Service\GoodsService;
use Illuminate\Support\Facades\DB;

class GoodsServices
{


    private $typeList;

    public function __construct()
    {
        //通过反射机制获取 私有属性
        $class = new \ReflectionClass(GoodsService::class);
        $property = $class->getProperty("typeList");//设置访问的属性
        $property->setAccessible(true);//允许属性可访问
        $goods = new GoodsService();
        $this->typeList = $property->getValue($goods);
    }

    /**
     * 商品服务信息表格
     * @param $params
     * @return mixed
     */
    function show($params)
    {
        $pageSize = $params['limit'];

        //顺序排序
        $goods = Goods::orderBy('id', 'desc');

        //条件参数判断 isset检测变量是否设置，并且不是 NULL。  判断是否存在
        if (isset($params['goodsname']) && $params['goodsname']) {
            $goods->where('name', 'LIKE', '%' . trim($params['goodsname']) . '%');
        }
        if (isset($params['type']) && $params['type']) {
            $goods->where('type', trim($params['type']));
        }
        if (isset($params['desc']) && $params['desc']) {
            $goods->where('desc', 'LIKE', '%' . trim($params['desc']) . '%');
        }
        //执行sql语句
        $pageResult = $goods->paginate($pageSize)->appends($params)->toArray();

        //获取值服务类型分类数组
        $arr = json_suc($this->typeList);
        for ($s = 1; $s <= sizeof($arr['data']); $s++) {
            for ($i = 0; $i < sizeof($arr['data'][$s]); $i++) {
                for ($j = 0; $j < sizeof($arr['data'][$s][$i]['items']); $j++) {
                    $type[] = $arr['data'][$s][$i]['items'][$j];
                }
            }
        }
        //$key是数组下标   $item 是对应的下标值
        foreach ($pageResult['data'] as $key => $item) {//数据条数 10  20
            foreach ($type as $k => $v) {
                if ($v['goods_type'] == $item['type']) {
                    $pageResult['data'][$key]['type'] = $v['title'];
                }
            }
        }
        return $pageResult;
    }

    /**
     * 软删除
     * @param $id
     * @return int  0
     */
    function del($id)
    {
        $goods = Goods::find($id);
        if ($goods->delete()) {
            return 1;
        } else {
            return 0;
        }
    }


    /**
     * 商品二维数组
     * @return array
     */
    public function getGoodsarray()
    {
        $arr = json_suc($this->typeList);//专利数组
        $type[] = ["goods_type" => 1, "title" => "专利"];
        for ($s = 0; $s < sizeof($arr['data'][1]); $s++) {
            for ($i = 0; $i < sizeof($arr['data'][1][$s]['items']); $i++) {
                $type[] = $arr['data'][1][$s]['items'][$i];
            }

        }
        $type[] = ["goods_type" => 2, "title" => "商标"];
        for ($s = 0; $s < sizeof($arr['data'][2]); $s++) {
            for ($i = 0; $i < sizeof($arr['data'][2][$s]['items']); $i++) {
                $type[] = $arr['data'][2][$s]['items'][$i];
            }

        }
        $type[] = ["goods_type" => 3, "title" => "版权"];
        for ($s = 0; $s < sizeof($arr['data'][3]); $s++) {
            for ($i = 0; $i < sizeof($arr['data'][3][$s]['items']); $i++) {
                $type[] = $arr['data'][3][$s]['items'][$i];
            }

        }
        return json_suc($type);
    }


    public function getGoodsarray2()
    {
        $goodslist = $this->typeList;
        $type = ['专利', '商标', '版权'];

        foreach ($goodslist as $k => $goodstype) {

            $goodsarr[] = ["goods_type" => $k, "title" => $type[$k - 1]];
            foreach ($goodstype as $group) {

                foreach ($group['items'] as $goods) {
                    $goodsarr[] = $goods;
                }
            }
        }
        return $goodsarr;

    }


    /**
     * 商品服务编辑信息返回
     * @param $type goods_type 商品类型
     * @param $name 商品名称
     * @return array
     */
    public function serveEdit($type, $name)
    {
        $data = Goods::get()
            ->where('type', $type)
            ->toArray();
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $result[] = $value;
            }
            return $result;
        }
    }

    /**
     * 商品服务上传图片保存 (更新)
     * @param $date
     * @return array
     */
    public function serve_imgSave($date)
    {
        //判断是手机类型还是PC类型
        if ($date['forType'] == 'mobile') {
            $state=Goods::where('type', $date['type'])
                ->update(
                    [
                        'cover' => $date['cover_path'],
                        'info_img' => $date['info_img_path']
                    ]
                );
        } elseif ($date['forType'] == 'pc') {
            $state=Goods::where('type', $date['type'])
                ->update(
                    [
                        'pc_cover' => $date['cover_path'],
                        'pc_info_img' => $date['info_img_path']
                    ]
                );
        }
        if ($state){
            return json_suc();
        }
        return json_err();


    }

}