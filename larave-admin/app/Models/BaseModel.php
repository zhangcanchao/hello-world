<?php
namespace App\models;

use Validator;

/**
 * Model基类
 * @author Robin
 *
 */
abstract class BaseModel
{
    /**
     * 验证类
     * @var Validator
     */
    private $_validator;
    
    /**
     * 错误验证信息
     * @var Array
     */
    protected $messages = [
        "integer" => "应为整型值",
        "required" => "必填字段",
        "confirmed" => "密码两次输入不一致",
        "email" => "邮件地址格式不正确",
        "date" => "日期格式不正确",
        "between" => "值区间为:min 到 :max",
        "min" => "最小值为:min",
        "max" => "最大值为:max",
        "in" => "值应为:values",
        "size" => "大小为:size",
    ];
 
    /**
     * 加载函数
     * @param Input $input
     * @param Rule $rule
     */
    protected function init($input, $rule = array())
    {
        $this->_validator = Validator::make($input, $rule, $this->messages);
 
        $formKey = array_keys(get_class_vars(get_class($this)));
        // 遍历表单键值 并赋予类成员
        foreach ($formKey as $value)
        {
            if(isset($input[$value]))
            {
                $this->$value = $input[$value];
            }
        }
    }
 
    /**
     * 取得验证器
     */
    public function validator()
    {
        return $$this->_validator;
    }
 
    /**
     * 判断是否验证成功
     * @return boolean
     */
    public function isValid()
    {
        return !$this->_validator->fails();
    }
    
    /**
     * 取得验证错误信息
     */
    public function messages() {
        return $this->_validator->messages();
    }
 
}

?>