<?php
namespace backend\utils;
use Yii;

class Functions
{
    static function C($param) {
        $params = Yii::$app->params;
        if (isset($params[$param]))
        return $params[$param];
        return '';
    }

	static function nonce($len=40) {
        $seeds = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'; //len = 61
        //$seeds .= '$![]{}|~*-()'; //len += 12;
        $seeds .= time();
        $nonce = '';
        $seedsLen = strlen($seeds);
        for ($i=0; $i < $len; $i++) {
            $rand = rand()%$seedsLen;
            $nonce .= substr($seeds, $rand, 1);
        }
        return str_shuffle(substr($nonce.sha1($nonce), 0, $len));
	}

    //still get chance to enconter cllision 
    static function guid($len=16) {
        $random = substr(number_format(time() * rand(),0,'',''),0,10);
        $seed = 1;
        list($msec, $sec) = explode(' ', microtime());
        $seed .= ltrim($msec, '0.');
        $seed .= $sec;
        $seed .= mt_rand(100000, 999999);
        $seed .= mt_rand(100000, 999999);
        $seed = '1'.str_shuffle($seed);
        return substr($seed, 0, $len);
    }

	// 二维数组转化
    static function fieldsMap(array $data, array $map) {
        $results = [];
        foreach ($data as $sigle) {
            $mix = [];
            foreach ($map as $k => $v) {
                //无应用规则
                $value = '';
                if (!is_array($v)) {
                    if (isset($sigle[$v])) {
                        $value = $sigle[$v];
                    }
                    $mix[$k] = $value;
                } else {
                    //若附带函数代码
                    list($v, $trans) = $v;
                    $func = create_function('$v', $trans);
                    $mix[$k] = $func($sigle[$v]); 
                }
            }
            $results[] = $mix;
        }
        return $results;
    }

	/**
	 * 获取客户端IP地址
	 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
	 * @param boolean $adv 是否进行高级模式获取（有可能被伪装） 
	 * @return mixed
	 */
	static function get_client_ip($type = 0,$adv=true) {
		$type       =  $type ? 1 : 0;
		static $ip  =   NULL;
		if ($ip !== NULL) return $ip[$type];
		if($adv){
		    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		        $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
		        $pos    =   array_search('unknown',$arr);
		        if(false !== $pos) unset($arr[$pos]);
		        $ip     =   trim($arr[0]);
		    }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
		        $ip     =   $_SERVER['HTTP_CLIENT_IP'];
		    }elseif (isset($_SERVER['REMOTE_ADDR'])) {
		        $ip     =   $_SERVER['REMOTE_ADDR'];
		    }
		}else if (isset($_SERVER['REMOTE_ADDR'])) {
		    $ip     =   $_SERVER['REMOTE_ADDR'];
		}
		// IP地址合法验证
		$long = sprintf("%u",ip2long($ip));
		$ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
		return $ip[$type];
	}
    
    /**
     * 计算两个日期之间相差天数
     */
    static function dateDiff($from, $to) {
        $d1 = strtotime($to);
        $d2 = strtotime($from);
        $days = round(($d1-$d2)/3600/24) + 1;
        return $days;
    }

    static function sortAndGlue($array, $glue=',', $sort=true) {
        $values = $array;
        sort($values);
        $str = implode($glue, $values);
        return $str;
    }

    static function array2str($array, $glue=',', $sort=true) {
        if (!is_array($array)) return '';
        $values = $array;
        sort($values);
        $str = implode($glue, $values);
        return $str;
    }

    static function str2array($str, $glue=',') {
        $array = explode($glud, $str);
        return $array;
    }
}//end
