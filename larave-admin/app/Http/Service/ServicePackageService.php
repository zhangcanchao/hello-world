<?php

namespace App\Http\Service;

use App\Api\Service\GoodsService;
use App\Http\Controllers\PatentController;
use App\Models\ServiceGood;
use App\Models\ServicePackage;
use Illuminate\Support\Facades\DB;
use Exception;

class ServicePackageService
{
    //服务项类型,1商标检索,2商标管理,3拓客,4商标分析报告包年,5专利管理,6专利搜索,7专利查重,8混合套餐
    //服务项类型,1商标检索,2商标管理,3拓客,4商标分析报告包年,5专利管理
    public $type = ['', '商标检索', '商标管理', '拓客', '商标分析报告包年', '专利管理', '专利搜索', '专利查重', '混合套餐'];
    public $states = ['已下架', '正常'];

    public function __construct()
    {

    }

    /**
     * 获取增值服务列表数据
     * @return array
     */
    public function showInfo()
    {
        $result = ServicePackage::all()->toArray();
        foreach ($result as $k => $v) {
            //商品类型
            foreach ($this->type as $key => $value) {
                if ($v['service_type'] == $key) {
                    $result[$k]['service_type'] = $value;
                }
            }
            //商品状态
            foreach ($this->states as $key => $value) {
                if ($v['state'] == $key) {
                    $result[$k]['state'] = $value;
                }
            }
            if ($v['count'] == -1) {
                $result[$k]['count'] = '不限数量';
            }
        }
        return $result;
    }

    /**
     * 获取增值服务编辑需要的数据
     * @param $id
     * @return array
     */
    public function editInfo($id)
    {
        //关联
        $result = ServicePackage::with('goods')->where('id', $id)->get();
        return $result;
    }

    /**
     * 增值套餐提交保存
     * @param $params
     * @return array
     */
    public function editSave($params)
    {
        //        判断数量是不是 不限数量
        if ($params['number_type'] == -1) {
            $params['count'] = -1;
        }
        $state = ServicePackage::where('id', $params['package_id'])
            ->update([
                'name' => $params['package_name'],
                'state' => $params['package_state'],
                'service_type' => $params['package_type'],
                'cover' => $params['cover_path'],
                'single_pay_fee' => (float)$params['single_pay_fee'],
                'count' => $params['count'],
                'end_at' => $params['end_at']
            ]);
        if ($state == 1) {
            return json_suc();
        }
        return json_err();

    }



    //--------------------------------

    /**
     * 获取套餐关联下的商品  展示
     * @param $package_id
     * @return mixed
     */
    public function goodsInfo($package_id)
    {
        $result = ServiceGood::where('package_id', $package_id)->get()->toArray();
        foreach ($result as $k => $v) {
            //商品类型
            foreach ($this->type as $key => $value) {
                if ($v['service_type'] == $key) {
                    $result[$k]['service_type'] = $value;
                }
            }
        }
        return $result;
    }

    /**
     * 商品编辑信息获取
     * @param $goods_id
     * @return mixed
     */
    public function goodsEdit($goods_id)
    {
        $result = ServiceGood::find($goods_id);
        return $result;
    }

    /**
     * 套餐商品信息修改，以及套餐价格改变
     * @param $params
     * @return array
     */
    public function goodsEditSave($params)
    {
        DB::beginTransaction();
        try {
            ServiceGood::where('package_id', $params['package_id'])
                ->where('id', $params['id'])
                ->update(
                    [
                        'name' => $params['name'],
                        'num' => $params['num'],
                        'days' => $params['days'],
                        'price' => $params['price'],
                        'service_type' => $params['service_type']
                    ]
                );

//            更新套餐总价格
            $this->update_price($params['package_id']);
            DB::commit();
            return json_suc();
        } catch (Exception $e) {
            DB::rollBack();
            return json_err();
        }


    }

    /**
     * 删除商品  以及更新套餐价格
     * @param $id  商品id
     * @param $package_id 套餐id
     * @return array
     */
    public function goodsDel($id, $package_id)
    {

        DB::beginTransaction();
        try {
            ServiceGood::find($id)->delete();//软删除
            $this->update_price($package_id);
            DB::commit();
            return json_suc();
        } catch (Exception $e) {
            DB::rollBack();
            return json_err();
        }


    }

    /**
     * 套餐 添加商品
     * @param $params
     * @return array
     */
    public function goodsAdd($params)
    {
        DB::beginTransaction();
        try {
            $data = [
                'name' => $params['name'],
                'service_type' => $params['service_type'],
                'num' => $params['num'],
                'days' => $params['days'],
                'price' => (float)$params['price'],
                'package_id' => $params['package_id']
            ];
            $rs = ServiceGood::create($data);
            if (!$rs) throw new Exception('添加商品失败！');
            $this->update_price($params['package_id']);
            DB::commit();
            return json_suc();
        } catch (Exception $e) {
            DB::rollBack();
            return json_err($e->getMessage());
        }
    }

    /**
     * 更新套餐总价
     * @param $package_id
     */
    function update_price($package_id)
    {
        $goods_arr = ServiceGood::where('package_id', $package_id)->get()->toArray();
        $goods_sum = 0;//统计商品总价
        foreach ($goods_arr as $k => $value) {
            (float)$goods_sum += $value['price'];
        }
        ServicePackage::where('id', $package_id)
            ->update(
                [
                    'price' => $goods_sum
                ]
            );
    }

    /**
     * 添加套餐
     * @param $params
     * @return array
     */
    public function packageSave($params)
    {
        //判断数量是不是 不限数量
        if ($params['number_type'] == -1) {
            $params['count'] = -1;
        }
        $data = [
            'name' => $params['package_name'],
            'service_type' => $params['package_type'],
            'count' => $params['count'],
            'cover'=>$params['cover_path'],
            'single_pay_fee' => (float)$params['single_pay_fee'],
            'state' => $params['package_state'],
            'end_at' => $params['end_at']
        ];
        $rs = ServicePackage::create($data);
        if ($rs) {
            return json_suc('添加套餐成功！');
        }
        return json_err('添加套餐失败！');


    }

    /**
     * 删除套餐以及套餐下的商品
     * @param $id
     * @return array
     */
    public function packageDel($id)
    {
        DB::beginTransaction();
        try {
            ServicePackage::destroy($id);
            ServiceGood::where('package_id', $id)->delete();
            DB::commit();
            return json_suc();
        } catch (Exception $e) {
            DB::rollBack();
            return json_err();
        }
    }

}