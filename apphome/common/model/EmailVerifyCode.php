<?php
namespace app\common\model;
use app\common\service\Smsbao;
use app\common\lib\Helper;
use app\common\lib\Smtp;
use app\common\lib\ReturnData;
use app\common\lib\Sms;
use think\Db;

class EmailVerifyCode extends Base
{
    protected $pk = 'id';
    
    public function getDb()
    {
        return db('email_verify_code');
    }
    
    const STATUS_UNUSE = 0;
    const STATUS_USE = 1;                                                       //验证码已被使用
    
    const TYPE_GENERAL = 0;                                                     //通用
    const TYPE_REGISTER = 1;                                                    //用户注册业务验证码
    const TYPE_CHANGE_PASSWORD = 2;                                             //密码修改业务验证码
    const TYPE_MOBILEE_BIND = 3;                                                //手机绑定业务验证码
	const TYPE_VERIFYCODE_LOGIN = 4;                                            //验证码登录
	const TYPE_CHANGE_MOBILE = 5;                                               //修改手机号码
	
    //验证码校验
    public function isVerify($mobile, $code, $type)
    {
        return $this->getOne(array('code'=>$code,'email'=>$mobile,'type'=>$type,'status'=>self::STATUS_UNUSE,'expired_at'=>array('>',date('Y-m-d H:i:s'))));
    }
    
    //验证码设置为已使用
    public function setVerifyCodeUse($mobile, $code, $type)
    {
        return $this->edit(array('status'=>self::STATUS_USE), array('code'=>$code,'email'=>$mobile,'type'=>$type));
    }
    
    //生成验证码
    public function getVerifyCodeBySmtp($email,$type,$text='')
    {
        //验证手机号
        if (!Helper::isValidEmail($email))
        {
            return ReturnData::create(ReturnData::MOBILE_FORMAT_FAIL);
        }
        
        switch ($type)
        {
            case self::TYPE_GENERAL;//通用
                break;
            case self::TYPE_REGISTER: //用户注册业务验证码
                break;
            case self::TYPE_CHANGE_PASSWORD: //密码修改业务验证码
                break;
            case self::TYPE_MOBILEE_BIND: //手机绑定业务验证码
                break;
            case self::TYPE_VERIFYCODE_LOGIN: //验证码登录
                break;
            case self::TYPE_CHANGE_MOBILE: //修改手机号码
                break;
            default:
                return ReturnData::create(ReturnData::INVALID_VERIFYCODE);
        }
        
        $data['type'] = $type;
        $data['email'] = $email;
        $data['code'] = rand(1000, 9999);
        $data['status'] = self::STATUS_UNUSE;
        //30分钟有效
        $time = time();
        $data['expired_at'] = date('Y-m-d H:i:s',($time+60*30));
        $data['created_at'] = date('Y-m-d H:i:s',$time);
        
        //短信发送验证码
        $text = '【'.sysconfig('CMS_WEBNAME').'】您的驗證碼是'.$data['code'].'，有效期30分鐘。';
        
        $smtpserver = 'smtp.sina.com';//SMTP服务器
        $smtpserverport = 25;//SMTP服务器端口
        $smtpusermail = '1feng2010@sina.com';//SMTP服务器的用户邮箱
        $smtpemailto = $email;//发送给谁
        $smtpuser = "1feng2010@sina.com";//SMTP服务器的用户帐号
        $smtppass = "seo123456";//SMTP服务器的用户密码
        $mailtitle = '【'.sysconfig('CMS_WEBNAME').'】驗證碼';//邮件主题
        $mailcontent = $text;//邮件内容
        $mailtype = 'HTML';//邮件格式(HTML/TXT),TXT为文本邮件
        $smtp = new Smtp($smtpserver,$smtpserverport,true,$smtpuser,$smtppass);//这里面的一个true是表示使用身份验证,否则不使用身份验证.
        $smtp->debug = false;//是否显示发送的调试信息
        $state = $smtp->sendmail($smtpemailto, $smtpusermail, $mailtitle, $mailcontent, $mailtype);
        if($state==""){return ReturnData::create(ReturnData::PARAMS_ERROR, null, '對不起，郵件發送失敗！請檢查郵箱填寫是否有誤。');}
        
		$this->add($data);
		
        return ReturnData::create(ReturnData::SUCCESS, array('code' => $data['code']));
    }
    
    /**
     * 列表
     * @param array $where 查询条件
     * @param string $order 排序
     * @param string $field 字段
     * @param int $offset 偏移量
     * @param int $limit 取多少条
     * @return array
     */
    public function getList($where = array(), $order = '', $field = '*', $offset = 0, $limit = 15)
    {
        $res['count'] = $this->getDb()->where($where)->count();
        $res['list'] = array();
        
        if($res['count'] > 0)
        {
            $res['list'] = $this->getDb()->where($where);
            
            if(is_array($field))
            {
                $res['list'] = $res['list']->field($field[0],true);
            }
            else
            {
                $res['list'] = $res['list']->field($field);
            }
            
            $res['list'] = $res['list']->order($order)->limit($offset.','.$limit)->select();
        }
        
        return $res;
    }
    
    /**
     * 分页，用于前端html输出
     * @param array $where 查询条件
     * @param string $order 排序
     * @param string $field 字段
     * @param int $limit 每页几条
     * @param int|bool $simple 是否简洁模式或者总记录数
     * @param int $page 当前第几页
     * @return array
     */
    public function getPaginate($where = array(), $order = '', $field = '*', $limit = 15, $simple = false)
    {
        $res = $this->getDb()->where($where);
        
        if(is_array($field))
        {
            $res = $res->field($field[0],true);
        }
        else
        {
            $res = $res->field($field);
        }
        
        return $res->order($order)->paginate($limit, $simple, array('query' => request()->param()));
    }
    
    /**
     * 查询全部
     * @param array $where 查询条件
     * @param string $order 排序
     * @param string $field 字段
     * @param int $limit 取多少条
     * @return array
     */
    public function getAll($where = array(), $order = '', $field = '*', $limit = '')
    {
        $res = $this->getDb()->where($where);
            
        if(is_array($field))
        {
            $res = $res->field($field[0],true);
        }
        else
        {
            $res = $res->field($field);
        }
        
        $res = $res->order($order)->limit($limit)->select();
        
        return $res;
    }
    
    /**
     * 获取一条
     * @param array $where 条件
     * @param string $field 字段
     * @return array
     */
    public function getOne($where, $field = '*')
    {
        $res = $this->getDb()->where($where);
        
        if(is_array($field))
        {
            $res = $res->field($field[0],true);
        }
        else
        {
            $res = $res->field($field);
        }
        
        $res = $res->find();
        
        return $res;
    }
    
    /**
     * 添加
     * @param array $data 数据
     * @return int
     */
    public function add($data,$type=0)
    {
        // 过滤数组中的非数据表字段数据
        // return $this->allowField(true)->isUpdate(false)->save($data);
        
        if($type==0)
        {
            // 新增单条数据并返回主键值
            return $this->getDb()->strict(false)->insertGetId($data);
        }
        elseif($type==1)
        {
            // 添加单条数据
            return $this->getDb()->strict(false)->insert($data);
        }
        elseif($type==2)
        {
            /**
             * 添加多条数据
             * $data = [
             *     ['foo' => 'bar', 'bar' => 'foo'],
             *     ['foo' => 'bar1', 'bar' => 'foo1'],
             *     ['foo' => 'bar2', 'bar' => 'foo2']
             * ];
             */
            
            return $this->getDb()->strict(false)->insertAll($data);
        }
    }
    
    /**
     * 修改
     * @param array $data 数据
     * @param array $where 条件
     * @return bool
     */
    public function edit($data, $where = array())
    {
        return $this->getDb()->strict(false)->where($where)->update($data);
    }
    
    /**
     * 删除
     * @param array $where 条件
     * @return bool
     */
    public function del($where)
    {
        return $this->getDb()->where($where)->delete();
    }
    
    /**
     * 统计数量
     * @param array $where 条件
     * @param string $field 字段
     * @return int
     */
    public function getCount($where, $field = '*')
    {
        return $this->getDb()->where($where)->count($field);
    }
    
    /**
     * 获取最大值
     * @param array $where 条件
     * @param string $field 要统计的字段名（必须）
     * @return null
     */
    public function getMax($where, $field)
    {
        return $this->getDb()->where($where)->max($field);
    }
    
    /**
     * 获取最小值
     * @param array $where 条件
     * @param string $field 要统计的字段名（必须）
     * @return null
     */
    public function getMin($where, $field)
    {
        return $this->getDb()->where($where)->min($field);
    }
    
    /**
     * 获取平均值
     * @param array $where 条件
     * @param string $field 要统计的字段名（必须）
     * @return null
     */
    public function getAvg($where, $field)
    {
        return $this->getDb()->where($where)->avg($field);
    }
    
    /**
     * 统计总和
     * @param array $where 条件
     * @param string $field 要统计的字段名（必须）
     * @return null
     */
    public function getSum($where, $field)
    {
        return $this->getDb()->where($where)->sum($field);
    }
    
    /**
     * 查询某一字段的值
     * @param array $where 条件
     * @param string $field 字段
     * @return null
     */
    public function getValue($where, $field)
    {
        return $this->getDb()->where($where)->value($field);
    }
    
    /**
     * 查询某一列的值
     * @param array $where 条件
     * @param string $field 字段
     * @return array
     */
    public function getColumn($where, $field)
    {
        return $this->getDb()->where($where)->column($field);
    }
    
    //类型，0通用，注册，1:手机绑定业务验证码，2:密码修改业务验证码
    public function getTypeAttr($data)
    {
        $arr = array(0 => '通用', 1 => '手机绑定业务验证码', 2 => '密码修改业务验证码');
        return $arr[$data['type']];
    }
    
    //状态
    public function getStatusAttr($data)
    {
        $arr = array(0 => '未使用', 1 => '已使用');
        return $arr[$data['status']];
    }
}