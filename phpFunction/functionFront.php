<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

/**
 * 前台公共库文件
 * 主要定义前台公共函数库
 */

/**
 * 检测验证码
 * @param  integer $id 验证码ID
 * @return boolean     检测结果
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function check_verify($code, $id = 1){
    $verify = new \Think\Verify(array('reset'=>false));
	return $verify->check($code, $id);
}

/**
 * 获取列表总行数
 * @param  string  $category 分类ID
 * @param  integer $status   数据状态
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function get_list_count($category, $status = 1){
    static $count;
    if(!isset($count[$category])){
        $count[$category] = D('Document')->listCount($category, $status);
    }
    return $count[$category];
}

/**
 * 获取段落总数
 * @param  string $id 文档ID
 * @return integer    段落总数
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function get_part_count($id){
    static $count;
    if(!isset($count[$id])){
        $count[$id] = D('Document')->partCount($id);
    }
    return $count[$id];
}

/**
 * 获取导航URL
 * @param  string $url 导航URL
 * @return string      解析或的url
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function get_nav_url($url){
    switch ($url) {
        case 'http://' === substr($url, 0, 7):
        case '#' === substr($url, 0, 1):
            break;        
        default:
            $url = U($url);
            break;
    }
    return $url;
}

/*********************************************************************
 * 函数名称:encrypt
 * 函数作用:加密解密字符串
 * 使用方法:
 * 加密     :encrypt('str','E','nowamagic');
 * 解密     :encrypt('被加密过的字符串','D','nowamagic');
 * 参数说明:
 *      $string   :需要加密解密的字符串
 *      $operation:判断是加密还是解密:E:加密   D:解密
 *      $key      :加密的钥匙(密匙);
*********************************************************************/
function encrypt($string,$operation,$key='swarm2015')
{
    $key=md5($key);
    $key_length=strlen($key);
    $string=$operation=='D'?base64_decode($string):substr(md5($string.$key),0,8).$string;
    $string_length=strlen($string);
    $rndkey=$box=array();
    $result='';
    for($i=0;$i<=255;$i++)
    {
        $rndkey[$i]=ord($key[$i%$key_length]);
        $box[$i]=$i;
    }
    for($j=$i=0;$i<256;$i++)
    {
        $j=($j+$box[$i]+$rndkey[$i])%256;
        $tmp=$box[$i];
        $box[$i]=$box[$j];
        $box[$j]=$tmp;
    }
    for($a=$j=$i=0;$i<$string_length;$i++)
    {
        $a=($a+1)%256;
        $j=($j+$box[$a])%256;
        $tmp=$box[$a];
        $box[$a]=$box[$j];
        $box[$j]=$tmp;
        $result.=chr(ord($string[$i])^($box[($box[$a]+$box[$j])%256]));
    }
    if($operation=='D')
    {
        if(substr($result,0,8)==substr(md5(substr($result,8).$key),0,8))
        {
            return substr($result,8);
        }
        else
        {
            return'';
        }
    }
    else
    {
        return str_replace('=','',base64_encode($result));
    }
}

/**
 * select返回的数组进行整数映射转换
 *
 * @param array $map  映射关系二维数组  array(
 *                                          '字段名1'=>array(映射关系数组),
 *                                          '字段名2'=>array(映射关系数组),
 *                                           ......
 *                                       )
 * @author 朱亚杰 <zhuyajie@topthink.net>
 * @return array
 *
 *  array(
 *      array('id'=>1,'title'=>'标题','status'=>'1','status_text'=>'正常')
 *      ....
 *  )
 *
 */
function int_to_string(&$data,$map=array('status'=>array(1=>'正常',-1=>'删除',0=>'禁用',2=>'未审核',3=>'草稿'))) {
	if($data === false || $data === null ){
		return $data;
	}
	$data = (array)$data;
	foreach ($data as $key => $row){
		foreach ($map as $col=>$pair){
			if(isset($row[$col]) && isset($pair[$row[$col]])){
				$data[$key][$col.'_text'] = $pair[$row[$col]];
			}
		}
	}
	return $data;
}

// 身份证合法性验证 starting
function validation_filter_id_card($id_card) { 
    $tmpStr = substr($id_card,0, 5);
    if (!is_numeric($tmpStr)) {
        return false;
    }
    if(strlen($id_card) == 18) { 
    return idcard_checksum18($id_card); 
    } elseif((strlen($id_card) == 15)) { 
        $id_card = idcard_15to18($id_card); 
        return idcard_checksum18($id_card); 
    } else { 
        return false; 
    }
} 
// 计算身份证校验码，根据国家标准GB 11643-1999 
function idcard_verify_number($idcard_base) { 
    if(strlen($idcard_base) != 17) { 
        return false; 
    } 
    //加权因子 
    $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2); 
    //校验码对应值 
    $verify_number_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'); 
    $checksum = 0; 
    for ($i = 0; $i < strlen($idcard_base); $i++) { 
        $checksum += substr($idcard_base, $i, 1) * $factor[$i]; 
    } 
    $mod = $checksum % 11; 
    $verify_number = $verify_number_list[$mod]; 
    return $verify_number; 
}

// 将15位身份证升级到18位 
function idcard_15to18($idcard){ 
    if (strlen($idcard) != 15){ 
        return false; 
    }else{ 
        // 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码 
        if (array_search(substr($idcard, 12, 3), array('996', '997', '998', '999')) !== false){ 
            $idcard = substr($idcard, 0, 6) . '18'. substr($idcard, 6, 9); 
        }else{ 
            $idcard = substr($idcard, 0, 6) . '19'. substr($idcard, 6, 9); 
        } 
    } 
    $idcard = $idcard . idcard_verify_number($idcard); 
    return $idcard; 
} 
// 18位身份证校验码有效性检查 
function idcard_checksum18($idcard){ 
    if (strlen($idcard) != 18){ return false; } 
    $idcard_base = substr($idcard, 0, 17); 
    if (idcard_verify_number($idcard_base) != strtoupper(substr($idcard, 17, 1))){ 
        return false; 
    }else{ 
        return true; 
    }
}
// 身份证合法性验证 ending

//订单生成
 function buildMerorderid($id){
	if (empty($id)) {
		return false;
	}

	// $newProductID = sprintf("%05d",$id);
	$time = date('YmdHis',time());
	$randomNum = rand(1,9);
	$merorderid = 'SW'.$id.$time.$randomNum;

	return $merorderid;
}

//remove html tag
function removeHtmlTag($fullDetail){
    return mb_ereg_replace('(([ \r\n\t])*|(　)*)*', '', strip_tags($fullDetail));
}
