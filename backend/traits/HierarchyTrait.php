<?php
namespace backend\traits;

use Pdp\PublicSuffixListManager;
use Pdp\Parser;

trait HierarchyTrait {
    public function C($config='') {
        $params = \Yii::$app->params;
        if (!$config) {
            return $params;
        }
        if (in_array($config, $params)) {
            return $params[$config];
        }
        return '';
    }

    public function t($category, $message, $params = [], $language = null ) {
       return \Yii::t($category, $message, $params, $language); 
    }

    /**
    * 提取controller 和 action的名字作为 category
    */
    public function makePath($classPath, $actionPath, & $category) {
        if ($subPath = strrchr($classPath, '\\')) {
            $category = substr($subPath, 1)."_{$actionPath}";
        } else {
            $category = $classPath."_{$actionPath}";
        }
    }

    /*
    * 写入日志文件
    */
    public function logFile($logmessage, $category='', $level = \yii\log\Logger::LEVEL_INFO) {
        if (empty($category)) {
            $path = debug_backtrace();
            $this->makePath($path[1]['class'], $path[1]['function'], $category);
        }

        $service = new backend\services\LogService($this);
        $service->setFileLog($logmessage, $category, $level);
    }

    public function getSiteBaseUrl() {
        $HOST = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'];
        $URI = $_SERVER['REQUEST_URI'];
        $URI = strstr($URI, '?', true);
        $parts = explode('/', $URI);
        array_pop($parts);
        array_pop($parts);
        $URI = implode('/', $parts);
        $ASK = $HOST.$URI;
        //$PIC = '/creative/get-material-picture?name='.$name;
        $PIC = '';

        return $ASK.$PIC;
    }

	/**
	 * 获取客户端IP地址
	 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
	 * @param boolean $adv 是否进行高级模式获取（有可能被伪装） 
	 * @return mixed
	 */
	public function get_client_ip($type = 0,$adv=false) {
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

    public function setJsDir() {
        return true;
        \Yii::$app->language = $this->s('language');
        $env = strstr(\Yii::$app->params['envDir'],"/",true); 
        if(!$env) {
            \Yii::$app->params['envDir'] = \Yii::$app->params['envDir'].'/js/'.strtolower(\Yii::$app->language);
        } else {
            \Yii::$app->params['envDir'] = $env.'/js/'.strtolower(\Yii::$app->language);
        }
    }

    public function setLangConfig($lang) {
        $this->S('language',$lang);
        $this->Cookie('language', $lang);
        \Yii::$app->language = $lang;
    }
    
    /**
     * Returns message file path for the specified language and category.
     *
     * @param string $category the message category
     * @param string $language the target language
     * @return string path to message file
     */
    public function getMessageFilePath($category, $language)
    {
        $basePath = '@app/messages';
        $messageFile = \Yii::getAlias($basePath) . "/$language/";
        //if (isset($this->fileMap[$category])) {
            //$messageFile .= $this->fileMap[$category];
        //} else {
            $messageFile .= str_replace('\\', '/', $category) . '.php';
        //}

        return $messageFile;
    }

    /**
     * fronend js i18n files
     */
    public function getFrontendMessageFilePath($category, $language)
    {
        $basePath = '@app/messages';
        $messageFile = \Yii::getAlias($basePath) . "/$language/frontend/";
        $messageFile .= str_replace('\\', '/', $category) . '.php';
        return $messageFile;
    }

    /**
     * Loads the message translation for the specified language and category or returns null if file doesn't exist.
     *
     * @param $messageFile string path to message file
     * @return array|null array of messages or null if file not found
     */
    public function loadMessagesFromFile($messageFile)
    {
        if (is_file($messageFile)) {
            $messages = include($messageFile);
            if (!is_array($messages)) {
                $messages = [];
            }

            return $messages;
        } else {
            return [];
        }
    }

    public  function getUrlDomain($url)
    {
        $url = str_ireplace('{!vam_click_url}', '', $url);
        $url = str_ireplace('{!dsp_click_url}', '', $url);
        $manager = new PublicSuffixListManager();
        $parser = new Parser($manager->getList());
        $uri = $parser->parseUrl($url);
        $host = $uri->toArray();
        return $host['registrableDomain'];
    }
}//end
