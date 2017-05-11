<?php
namespace backend\utils;

class HttpUtil {
    /**
     * post data to url
     *
     * @param string $url
     * @param array $data key-value map array, key will be the post variable
     * name
     * @param boolean $rnt weather return the url page content
     * @return mixed boolean if $rnt is false, the content of request url if $rnt is true.
     */
    public static function curl($url, $data=[], $rnt=false, $username, $password)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if(is_array($data) && sizeof($data) != 0)
            curl_setopt($ch, CURLOPT_POST, 1);

        //TODO need to get user identity string
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $username.':'.$password);
		//set default request execute time to be 20 seconds
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $issent = curl_exec($ch);
       

        if(curl_errno($ch)) {
            $msg = 'Curl error: '.curl_error($ch);     
            Yii::log($msg, 'error', 'curl');
        }

        curl_close($ch);
        return $issent;
    }
    
    /**
     * Get方式请求Url数据，返回字符串
     * @param $url 请求Url
     * @return content 网页内容
     * @author weiping_lei
     */
    public static function getUrlContent($url, $username='', $password=''){
    	//初始化Curl
    	$ch = curl_init($url);
    	//获取数据返回
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if ($username && $password) {
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
       		curl_setopt($ch, CURLOPT_USERPWD, $username.':'.$password);
		}

    	//在启用 CURLOPT_RETURNTRANSFER 时候将获取数据返回
    	curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
    	$fileContent = curl_exec($ch);
    	//关闭Curl对象
    	curl_close($ch);
    	return $fileContent;
    }

}//end
