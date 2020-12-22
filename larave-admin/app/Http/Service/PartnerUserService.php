<?php
declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: TaoJie
 * Date: 2019/8/13
 * Time: 19:06
 */

namespace App\Http\Service;

use App\Models\PartnerUser;
use Illuminate\Support\Facades\DB;

class PartnerUserService
{
    function show($params)
    {
        $pageSize = isset($params['limit']) ? $params['limit'] : 0;

        $partnerUser = PartnerUser::leftJoin("partners as p", "p.id", "=", "partner_users.partner_id")
            ->orderBy('partner_users.created_at', 'desc');

        if (isset($params['id']) && $params['id']) {
            $partnerUser->where('partner_users.id', intval($params['id']));
        }
        if (isset($params['name']) && $params['name']) {
            $partnerUser->where('partner_users.mobile', 'LIKE', '%' . trim($params['name']) . '%');
        }
        if (isset($params['company_name']) && $params['company_name']) {
            $partnerUser->where('p.company_name', 'LIKE', '%' . trim($params['company_name']) . '%');
        }
        if (isset($params['begin']) && $params['begin']) {
            $partnerUser->where('partner_users.created_at', '>=', trim($params['begin']));
        }
        if (isset($params['end']) && $params['end']) {
            $partnerUser->where('partner_users.created_at', '<=', trim($params['end']));
        }

        $partnerUser->where('partner_users.partner_id', '>', 0);
        $partnerUser->select('partner_users.id', 'p.company_name', 'is_admin', 'name', 'partner_users.email', 'partner_users.mobile', 'role_id', 'partner_users.created_at');

        if ($pageSize) {
            $pageResult = $partnerUser->paginate($pageSize)->appends($params)->toArray();
        } else {
            $pageResult['data'] = $partnerUser->get()->toArray();
            $pageResult['total'] = count($pageResult['data']);
        }

        return $pageResult;
    }

    /**
     * 未充值列表
     * @param $params
     * @return mixed
     */
    function noRechargeShow($params)
    {
        $pageSize = isset($params['limit']) ? $params['limit'] : 0;
        $partnerUser = PartnerUser::leftJoin("partners as p", "p.id", "=", "partner_users.partner_id");

        //有公司名 但未充值，包括：未认证有公司名的一期数据，二期的认证中等状态有公司名的数据
        $auth_where = [
//            ['p.state', '>', 0], 一期数据中有公司名，但是未认证状态
            ['p.type', 1],
            ['p.balance', 0],
            ['p.currency', 0],
            ['partner_users.is_admin', 1]
        ];

        //注册未认证未充值
        $not_auth_where = [
            ['partner_users.partner_id', 0],
            ['partner_users.is_admin', 1]
        ];

        $push_arr = [];
        if (isset($params['id']) && $params['id']) {
            $push_arr[] = ['partner_users.id', intval($params['id'])];
        }
        if (isset($params['mobile']) && $params['mobile']) {
            $push_arr[] = ['partner_users.mobile', trim($params['mobile'])];
        }
        if (isset($params['company_name']) && $params['company_name']) {
            $push_arr[] = ['p.company_name', 'LIKE', '%' . $params['company_name'] . '%'];
        }
        if (isset($params['begin']) && $params['begin']) {
            $push_arr[] = ['partner_users.created_at', '>=', trim($params['begin'])];
        }
        if (isset($params['end']) && $params['end']) {
            $push_arr[] = ['partner_users.created_at', '<=', trim($params['end'])];
        }

        foreach ($push_arr as $push) {
            array_push($auth_where, $push);
            array_push($not_auth_where, $push);
        }

        if (!isset($params['state'])) {
            $partnerUser->where($auth_where)->orWhere($not_auth_where);
        } elseif (isset($params['state']) && $params['state']) {
            $auth_where[] = ['state', '>', 0];
            $partnerUser->where($auth_where);
        } else {
            $auth_where[] = ['state', 0];
            $partnerUser->where($not_auth_where)->orWhere($auth_where);
        }

        $partnerUser->select('partner_users.id as id', 'partner_users.partner_id as partner_id', 'partner_users.mobile as mobile', 'partner_users.created_at', 'partner_users.admin_remark', 'p.company_name');

        $partnerUser->orderBy('created_at', 'desc');

        if ($pageSize) {
            $pageResult = $partnerUser->paginate($pageSize)->appends($params)->toArray();
        } else {
            $pageResult['data'] = $partnerUser->get()->toArray();
            $pageResult['total'] = count($pageResult['data']);
        }

        return $pageResult;
    }

    /**
     * 更新未充值列表备注
     * @param $data
     * @return mixed
     */
    function noRechargeStore($data)
    {
        return PartnerUser::where('id', intval($data['id']))->update(['admin_remark' => $data['admin_remark'] ?: '']);
    }


}
