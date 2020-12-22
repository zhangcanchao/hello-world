<?php
namespace App\Http\Requests;

//use App\Api\Requests\BaseRequest;

class ConfigRequest extends Request
{
    public function rules()
    {
        return [
            'register_tm_search_number' =>  'integer',
            'register_tm_search_day' =>  'integer',
            'register_tm_manage_number' =>  'integer',
            'register_tm_manage_day' =>  'integer',
            'register_tm_clue_number' =>  'integer',
            'register_tm_clue_day' =>  'integer',
            'register_tm_report_number' =>  'integer',
            'register_tm_report_day' =>  'integer',
            'register_patent_manage_number' =>  'integer',
            'register_patent_manage_day' =>  'integer',
            'register_patent_search_number' =>  'integer',
            'register_patent_search_day' =>  'integer',
            'register_patent_analysis_number' =>  'integer',
            'register_patent_analysis_day' =>  'integer',


            'auth_tm_search_number' =>  'integer',
            'auth_tm_search_day' =>  'integer',
            'auth_tm_manage_number' =>  'integer',
            'auth_tm_manage_day' =>  'integer',
            'auth_tm_clue_number' =>  'integer',
            'auth_tm_clue_day' =>  'integer',
            'auth_tm_report_number' =>  'integer',
            'auth_tm_report_day' =>  'integer',
            'auth_patent_manage_number' =>  'integer',
            'auth_patent_manage_day' =>  'integer',
            'auth_patent_search_number' =>  'integer',
            'auth_patent_search_day' =>  'integer',
            'auth_patent_analysis_number' =>  'integer',
            'auth_patent_analysis_day' =>  'integer',

            'recharge_tm_search_number' =>  'integer',
            'recharge_tm_search_day' =>  'integer',
            'recharge_tm_manage_number' =>  'integer',
            'recharge_tm_manage_day' =>  'integer',
            'recharge_tm_clue_number' =>  'integer',
            'recharge_tm_clue_day' =>  'integer',
            'recharge_tm_report_number' =>  'integer',
            'recharge_tm_report_day' =>  'integer',
            'recharge_patent_manage_number' =>  'integer',
            'recharge_patent_manage_day' =>  'integer',
            'recharge_patent_search_number' =>  'integer',
            'recharge_patent_search_day' =>  'integer',
            'recharge_patent_analysis_number' =>  'integer',
            'recharge_patent_analysis_day' =>  'integer',


        ];
    }

    public function messages()
    {
        return [
            'register_tm_search_number.integer' => '注册商标检索数量请填整数',
            'register_tm_manage_number.integer' => '注册商标管理数量请填整数',
            'register_tm_clue_number.integer' => '注册拓客数量请填整数',
            'register_tm_report_number.integer' => '注册商标分析报告包年数量请填整数',
            'register_patent_manage_number.integer' => '注册专利管理数量请填整数',
            'register_patent_search_number.integer' => '注册专利搜索数量请填整数',
            'register_patent_analysis_number.integer' => '注册专利去重数量请填整数',
            'register_tm_search_day.integer' => '注册商标检索天数请填整数',
            'register_tm_manage_day.integer' => '注册商标管理天数请填整数',
            'register_tm_clue_day.integer' => '注册拓客天数请填整数',
            'register_tm_report_day.integer' => '注册商标分析报告包年天数请填整数',
            'register_patent_manage_day.integer' => '注册专利管理天数请填整数',
            'register_patent_search_day.integer' => '注册专利搜索天数请填整数',
            'register_patent_analysis_day.integer' => '注册专利去重天数请填整数',

            'auth_tm_search_number.integer' => '实名商标检索数量请填整数',
            'auth_tm_manage_number.integer' => '实名商标管理数量请填整数',
            'auth_tm_clue_number.integer' => '实名拓客数量请填整数',
            'auth_tm_report_number.integer' => '实名商标分析报告包年数量请填整数',
            'auth_patent_manage_number.integer' => '实名专利管理数量请填整数',
            'auth_patent_search_number.integer' => '实名专利搜索数量请填整数',
            'auth_patent_analysis_number.integer' => '实名专利去重数量请填整数',
            'auth_tm_search_day.integer' => '实名商标检索天数请填整数',
            'auth_tm_manage_day.integer' => '实名商标管理天数请填整数',
            'auth_tm_clue_day.integer' => '实名拓客天数请填整数',
            'auth_tm_report_day.integer' => '实名商标分析报告包年天数请填整数',
            'auth_patent_manage_day.integer' => '实名专利管理天数请填整数',
            'auth_patent_search_day.integer' => '实名专利搜索天数请填整数',
            'auth_patent_analysis_day.integer' => '实名专利去重天数请填整数',


            'recharge_tm_search_number.integer' => '充值商标检索数量请填整数',
            'recharge_tm_manage_number.integer' => '充值商标管理数量请填整数',
            'recharge_tm_clue_number.integer' => '充值拓客数量请填整数',
            'recharge_tm_report_number.integer' => '充值商标分析报告包年数量请填整数',
            'recharge_patent_manage_number.integer' => '充值专利管理数量请填整数',
            'recharge_patent_search_number.integer' => '充值专利搜索数量请填整数',
            'recharge_patent_analysis_number.integer' => '充值专利去重数量请填整数',
            'recharge_tm_search_day.integer' => '充值商标检索天数请填整数',
            'recharge_tm_manage_day.integer' => '充值商标管理天数请填整数',
            'recharge_tm_clue_day.integer' => '充值拓客天数请填整数',
            'recharge_tm_report_day.integer' => '充值商标分析报告包年天数请填整数',
            'recharge_patent_manage_day.integer' => '充值专利管理天数请填整数',
            'recharge_patent_search_day.integer' => '充值专利搜索天数请填整数',
            'recharge_patent_analysis_day.integer' => '充值专利去重天数请填整数',
        ];
    }





}