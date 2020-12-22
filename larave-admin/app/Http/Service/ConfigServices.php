<?php

namespace App\Http\Service;


use App\Models\Config;
use App\Models\PatentEntrust;
use Illuminate\Support\Facades\DB;
use mysql_xdevapi\Exception;

class ConfigServices
{

    public $state = ['未启用', '启用'];


    /**
     * 软著模板文件配置
     * @return mixed
     */
    function workTpl_info()
    {
        $model = new Config();
        $array = $model->where('key', 'workTpl')->get();
        $arr = json_decode($array, true);
        $workTpl = json_decode($arr[0]['value'], true);
        return $workTpl;
    }

    /**
     * 商标申请线索配置
     * @return mixed
     */
    function clueApply_info()
    {
        $model = new Config();
        $array = $model->where('key', 'clueApply')->get();
        $arr = json_decode($array, true);
        $brand = json_decode($arr[0]['value'], true);
        return $brand;
    }

    /**
     * 商标分析报告套餐价格配置
     * @return mixed
     */
    function tmReport_info()
    {
        $model = new Config();
        $array = $model->where('key', 'tmReport')->get();
        $arr = json_decode($array, true);
        $report = json_decode($arr[0]['value'], true);
        return $report;
    }

    /**
     * 商标注册费用配置
     * @return mixed
     */
    function tmFee_info()
    {
        $model = new Config();
        $array = $model->where('key', 'tmFee')->get();
        $arr = json_decode($array, true);
        $tmFee = json_decode($arr[0]['value'], true);
        return $tmFee;
    }

    /**
     * 软著模板文件配置
     */
    function workTpl_update($params)
    {
        $workTplConfig = [
            'attorney' => [
                'path' => $params['agencyupload']
            ],
            'certificate' => [
                'path' => $params['rightupload']
            ],
        ];
        $json = json_encode($workTplConfig);
        $model = new Config();
        $state = $model->where('key', 'workTpl')
            ->update(
                ['value' => $json]
            );
        if ($state == 1) {
            return json_suc();
        }
        return json_err();
    }

    /**
     * 商标申请线索配置
     * @param $params
     * @return array
     */
    function clueApply_update($params)
    {


        $config = [
            'days' => (int)$params['days'],  // 有效天数
            'count' => (int)$params['count'], // 单次推送线索数量
            'area' => $params['area'],
            'price' => [
                1 => ['official_fee' => (int)$params['extension_official'], 'ref_fee' => (int)$params['extension_ref'], 'profit' => (int)$params['extension_profit']],
                2 => ['official_fee' => (int)$params['broadsiding_official'], 'ref_fee' => (int)$params['broadsiding_ref'], 'profit' => (int)$params['broadsiding_profit']],
                3 => ['official_fee' => (int)$params['objection_official'], 'ref_fee' => (int)$params['objection_ref'], 'profit' => (int)$params['objection_ref']],
            ],
        ];
        $json = json_encode($config);
        $model = new Config();
        $state = $model->where('key', 'clueApply')
            ->update(
                ['value' => $json]
            );
        if ($state == 1) {
            return json_suc();
        }
        return json_err();

    }

    /**
     * 商标分析报告套餐价格配置
     * @param $params
     * @return array
     */
    function tmReport_update($params)
    {
        $tmReportConfig = [
            1 => [
                'type' => (int)$params['skypetype'],
                'price' => (int)$params['skypeprice'],
                'count' => (int)$params['skypecount'],
                'remark' => '包年'
            ],
            2 => [
                'type' => (int)$params['oncetype'],
                'price' => (int)$params['onceprice'],
                'count' => (int)$params['oncecount'],
                'remark' => '单次'
            ]
        ];
        $json = json_encode($tmReportConfig);
        $model = new Config();
        $state = $model->where('key', 'tmReport')
            ->update(
                ['value' => $json]
            );
        if ($state == 1) {
            return json_suc();
        }
        return json_err();


    }

    /**
     * 商标注册费用配置
     * @param $params
     * @return array
     */
    function tmFee_update($params)
    {

        $tmFeeConfig = [
            'base_official_fee' => (int)$params['base_official_fee'],
            'base_service_fee' => (int)$params['base_service_fee'],
            'more_item_official_fee' => (int)$params['more_item_official_fee'],
            'more_item_service_fee' => (int)$params['more_item_service_fee'],
        ];
        $json = json_encode($tmFeeConfig);
        $model = new Config();
        $state = $model->where('key', 'tmFee')
            ->update(
                ['value' => $json]
            );
        if ($state == 1) {
            return json_suc();
        }
        return json_err();

    }

    /**
     * 专利模板信息
     * @param $params
     * @return mixed
     */
    function entrustshow($params)
    {
        $pageSize = $params['limit'];
        //顺序排序
        $goods = PatentEntrust::orderBy('id', 'desc');

        //执行sql语句
        $pageResult = $goods->paginate($pageSize)->appends($params)->toArray();

        foreach ($pageResult['data'] as $key => $value) {
            $pageResult['data'][$key]['state'] = $this->state[$value['state']];
        }

        return $pageResult;
    }

    function entrust_edit($id)
    {
        $date = PatentEntrust::where('id', $id)->get()->toArray();
        return $date;
    }

    /**
     * 用户操作赠送相对应的商标查询
     */
    public function identity_info()
    {
        $model = new Config();
        $array = $model->where('key', 'giveServiceItemByRole')->get();
        $identityConfig = json_decode($array[0]['value'], true);
        return $identityConfig;
    }

    /**
     * 用户操作赠送相对应的商标查询
     * @param $params
     * @return array
     */
    public function identity_update($params)
    {
        $serviceItemConfig = [
            //注册用户
            '1' => [
                //商标检索
                '1' => [
                    'number' => (int)$params['register_tm_search_number'],
                    'day' => (int)$params['register_tm_search_day'],
                ],
                //商标管理
                '2' => [
                    'number' => (int)$params['register_tm_manage_number'],
                    'day' => (int)$params['register_tm_manage_day'],
                ],
                //拓客
                '3' => [
                    'number' => (int)$params['register_tm_clue_number'],
                    'day' => (int)$params['register_tm_clue_day'],
                ],
                //商标分析报告包年
                '4' => [
                    'number' => (int)$params['register_tm_report_number'],
                    'day' => (int)$params['register_tm_report_day'],
                ],
                //专利管理
                '5' => [
                    'number' => (int)$params['register_patent_manage_number'],
                    'day' => (int)$params['register_patent_manage_day'],
                ],
                //专利搜索
                '6' => [
                    'number' => (int)$params['register_patent_search_number'],
                    'day' => (int)$params['register_patent_search_day'],
                ],
                //专利去重
                '7' => [
                    'number' => (int)$params['register_patent_analysis_number'],
                    'day' => (int)$params['register_patent_analysis_day'],
                ]
            ],
            //实名用户
            '2' => [
                //商标检索
                '1' => [
                    'number' => (int)$params['auth_tm_search_number'],
                    'day' => (int)$params['auth_tm_search_day'],
                ],
                //商标管理
                '2' => [
                    'number' => (int)$params['auth_tm_manage_number'],
                    'day' => (int)$params['auth_tm_manage_day'],
                ],
                //拓客
                '3' => [
                    'number' => (int)$params['auth_tm_clue_number'],
                    'day' => (int)$params['auth_tm_clue_day'],
                ],
                //商标分析报告包年
                '4' => [
                    'number' => (int)$params['auth_tm_report_number'],
                    'day' => (int)$params['auth_tm_report_day'],
                ],
                //专利管理
                '5' => [
                    'number' => (int)$params['auth_patent_manage_number'],
                    'day' => (int)$params['auth_patent_manage_day'],
                ],
                //专利搜索
                '6' => [
                    'number' => (int)$params['auth_patent_search_number'],
                    'day' => (int)$params['auth_patent_search_day'],
                ],
                //专利去重
                '7' => [
                    'number' => (int)$params['auth_patent_analysis_number'],
                    'day' => (int)$params['auth_patent_analysis_day'],
                ]
            ],
            //充值用户
            '3' => [
                //商标检索
                '1' => [
                    'number' => (int)$params['recharge_tm_search_number'],
                    'day' => (int)$params['recharge_tm_search_day'],
                ],
                //商标管理
                '2' => [
                    'number' => (int)$params['recharge_tm_manage_number'],
                    'day' => (int)$params['recharge_tm_manage_day'],
                ],
                //拓客
                '3' => [
                    'number' => (int)$params['recharge_tm_clue_number'],
                    'day' => (int)$params['recharge_tm_clue_day'],
                ],
                //商标分析报告包年
                '4' => [
                    'number' => (int)$params['recharge_tm_report_number'],
                    'day' => (int)$params['recharge_tm_report_day'],
                ],
                //专利管理
                '5' => [
                    'number' => (int)$params['recharge_patent_manage_number'],
                    'day' => (int)$params['recharge_patent_manage_day'],
                ],
                //专利搜索
                '6' => [
                    'number' => (int)$params['recharge_patent_search_number'],
                    'day' => (int)$params['recharge_patent_search_day'],
                ],
                //专利去重
                '7' => [
                    'number' => (int)$params['recharge_patent_analysis_number'],
                    'day' => (int)$params['recharge_patent_analysis_day'],
                ]
            ]
        ];
        $json = json_encode($serviceItemConfig);
        $model = new Config();
        $state = $model->where('key', 'giveServiceItemByRole')
            ->update(
                ['value' => $json]
            );
        if ($state == 1) {
            return json_suc();
        }
        return json_err();
    }

    /**
     * 商标/专利查询次数配置
     * @return array
     */
    public function searchCount_info()
    {
        $model = new Config();
        $query = $model->where('key', 'searchCount')->get();
        $query_data = json_decode($query[0]['value'], true);
        return $query_data;
    }

    /**
     * 商标/专利查询次数配置
     * @return array
     */
    public function searchCount_update($params)
    {
        $searchCountConfig = [
            'register' => [
                'tm' => (int)$params['register_tm_query'],
                'patent' => (int)$params['register_patent_query']
            ],
            'auth' => [
                'tm' => (int)$params['auth_tm_query'],
                'patent' => (int)$params['auth_patent_query']
            ],
            'recharge' => [
                'tm' => (int)$params['recharge_tm_query'],
                'patent' => (int)$params['recharge_patent_query']
            ]
        ];
        $json = json_encode($searchCountConfig);
        $model = new Config();
        $state = $model->where('key', 'searchCount')
            ->update(
                ['value' => $json]
            );
        if ($state == 1) {
            return json_suc();
        }
        return json_err();
    }

    /**
     * 专利缴年费时的手续费
     */
    public function patent_fee_info()
    {
        $model = new Config();
        $patent_fee = $model->where('key', 'patentPayFee')->get();
        $patent_data = json_decode($patent_fee[0]['value'], true);
        return $patent_data;
    }


    /**
     * 专利缴年费时的手续费
     * @param $params
     * @return array
     */
    public function patent_fee_update($params)
    {

        unset($params['_token']);
//        dd($params);
        foreach ($params as $k => $v) {
            if ($v['min_fee'] < $v['max_fee']) {
                $patent_pay_fee[] = ['min_fee' => (int)$v['min_fee'], 'max_fee' => (int)$v['max_fee'], 'service_fee' => (int)$v['service_fee']];
            }else if ($v['max_fee']==0){
                $patent_pay_fee[] = ['min_fee' => (int)$v['min_fee'], 'max_fee' => (int)$v['max_fee'], 'service_fee' => (int)$v['service_fee']];
            }else{
                return json_err('填写最小值大于最大值,请检查,并且修改！');
            }
        }
        $json = json_encode($patent_pay_fee);
        $model = new Config();
        $state = $model->where('key', 'patentPayFee')
            ->update(
                ['value' => $json]
            );
        if ($state == 1) {
            return json_suc();
        }
        return json_err();

    }

    /**
     * 商标担保注册价格配置
     * @return mixed
     */
    function tmGuaranteeFee_info()
    {
        $model = new Config();
        $array = $model->where('key', 'tmGuaranteeFee')->get();
        $arr = json_decode($array, true);
        $tmFee = json_decode($arr[0]['value'], true);
        return $tmFee;
    }


    /**
     * 商标担保注册价格配置
     * @param $params
     * @return array
     */
    public function tmGuaranteeFee_update($params)
    {

        $tmGuaranteeFee = [
            'base_official_fee' => (int)$params['base_official_fee'],
            'base_service_fee' => (int)$params['base_service_fee'],
            'more_item_official_fee' => (int)$params['more_item_official_fee'],
            'more_item_service_fee' => (int)$params['more_item_service_fee'],
        ];
        $json = json_encode($tmGuaranteeFee);
        $model = new Config();
        $state = $model->where('key', 'tmGuaranteeFee')
            ->update(
                ['value' => $json]
            );
        if ($state == 1) {
            return json_suc();
        }
        return json_err();
    }

}