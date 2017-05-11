<?php
namespace backend\traits;

use Yii;
use yii\helpers\Url;

trait RequestTrait {

    public $I = [];
    
    /**
     * 获取输入
     */
    public function I() {
        $no = func_num_args();
        $request = Yii::$app->request;
        $post = $request->post();
        $get = $request->get();
        $params = array_merge($get, $post);
        $this->I = array_merge($params, $this->I);
        switch($no) {
            case 0:
                return $this->I;
                break;
            case 1:
                if (!isset($params[func_get_arg(0)])) 
                    return false;
                return $params[func_get_arg(0)];
                break;
            case 2:
                if (is_null(func_get_arg(1))) {
                    if (isset($params[func_get_arg(0)])) 
                        unset($params[func_get_arg(0)]);
                    if (isset($this->I[func_get_arg(0)])) 
                        unset($this->I[func_get_arg(0)]);
                    if (isset($_POST[func_get_arg(0)]))
                        unset($_POST[func_get_arg(0)]);
                    if (isset($_GET[func_get_arg(0)]))
                        unset($_GET[func_get_arg(0)]);
                    //return $request->setBodyParams([func_get_arg(0) =>null]);
                } else {
                    $_POST[func_get_arg(0)] = func_get_arg(1);
                    $_SET[func_get_arg(0)] = func_get_arg(1);
                    $request->post(func_get_arg(0), func_get_arg(1));
                    $request->get(func_get_arg(0), func_get_arg(1));
                    //$request->setBodyParams([func_get_arg(0) => func_get_arg(1)]);
                    $params[func_get_arg(0)] = func_get_arg(1);
                    $this->I[func_get_arg(0)] = func_get_arg(1);
                    return func_get_arg(1);
                }
                break;
        }
        return $params;
    }

    /**
     * session simple wrapper
     */
    public function S() {
        $no = func_num_args();
        $session = \Yii::$app->session;
        if ($no == 0) {
            $sess = [];
            foreach ($session as $k => $v) {
                $sess[$k] = $v;
            }
            return $sess;
        } else if ($no == 1) {
            if (is_null(func_get_arg(0))) {
                $session->removeAll();
                return $session->destroySession($session->getId());
            }
            return $session->get(func_get_arg(0));
        } else if ($no == 2) {
            if (is_null(func_get_arg(1))) {
                $session->remove(func_get_arg(0));
            }
            $session->set(func_get_arg(0), func_get_arg(1));
            return func_get_arg(1);
        }
    }

    public function isPjax() {
        return array_key_exists('HTTP_X_PJAX', $_SERVER) && $_SERVER['HTTP_X_PJAX'];
    }

    public function OK($msg, $url='', $data=[], $time='') {
        //$msg = ucfirst($msg);
        $info = ['status' => 'success',
                 'message' => $msg,
                 'url' => $url,
                 'data' => $data,
                 'time' => $time ];     
        $this->jsonReturn($info); 
    }

    public function NG($msg, $failTip='', $data=[]) {
        //$msg = ucfirst($msg);
        $info = ['status' => 'failure',
                 'message' => $msg,
                 'url' => '',
                 'data' => $data ];    
        if ($failTip) $info['reason'] = $failTip;
        $this->jsonReturn($info); 
    }

    public function isHeartClicking() {
        if (isset($this->id) && substr($this->id, 0, 3) == 'api') return true;
        if (!isset($this->action) || $this->action->id == 'login') return true;
        //remove this after login enabled
        $lifeSpan = \Yii::$app->params['session']['SESSION_LIFE_SPAN'];
        $lastRequestTime = $this->S('LastRequestTime');
        $intval = time() - $lastRequestTime;
        return $lifeSpan > $intval;
    }

    //session timeout
    public function JAM($url='') {
        !$url && $url = Url::to(['/site/login']);
        $info = ['status' => 'timeout', 'url' => $url];
        if (!$this->isHeartClicking()) {
            $this->jsonReturn($info); 
        }
    }

    public function toJson(array $data) {
        return $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function jsonReturn(array $data) {
        // if (!$this->isHeartClicking()) {
        //     $this->S(null);
        //     $url = Url::to(['/site/login']);
        //     $data = ['status' => 'sessionExpire', 'url' => $url];
        // }
        if (ob_get_level() > 0) ob_clean();
        header('Content-Type:application/json; charset=utf-8');
        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
        exit($jsonData);
    }

    public function touchSession() {
        $this->S('LastRequestTime', time());
    }

     /**
     * cookie simple wrapper
     */
    public function Cookie() {
        $no = func_num_args();
        $cookie = $_COOKIE;
        if ($no == 0) {
            return $cookie;
        } else if ($no == 1) {
            if (is_null(func_get_arg(0))) {
                return null;
            }
            if(isset($cookie[func_get_arg(0)])) {
                return $cookie[func_get_arg(0)];
            }
            return null;
        } else if ($no == 2) {
            if (is_null(func_get_arg(1))) {
                setcookie(func_get_arg(0),"",time()-100);
            }
            setcookie(func_get_arg(0), func_get_arg(1),  time()+60*60*24*30, '/', $this->getAdomain());
            return func_get_arg(1);
        } else if ($no == 3) {
            if (is_null(func_get_arg(1))) {
                setcookie(func_get_arg(0),"",time()-100);
            }
            setcookie(func_get_arg(0), func_get_arg(1),  func_get_arg(2), '/', $_SERVER['HTTP_HOST']);
            return func_get_arg(1);
        }
    }

     public function getAdomain() {
        $url = $_SERVER['HTTP_HOST'];
        if(!preg_match("/[\d]{2,3}\.[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}/", $url)){
            $pattern = "/[\w-]+\.(com|net|org|gov|cc|biz|info|cn)(\.(cn|hk))*/";
            preg_match($pattern, $url, $matches);
            if ($matches) {
                return $matches[0];
            } else {
                return $this->checkAdomainPort($_SERVER['HTTP_HOST']);
            }
        }else{
            return $this->checkAdomainPort($url);
        }
    }

    public function checkAdomainPort($url) {
        $pos = strpos($url, ":");
        if($pos) {
            $url = strstr($url, ":", true);
        }
        return $url;
    }

    public function ajaxSessionExpire(){
        $url = Url::to(['/site/login']);
        $data = ['status' => 'sessionExpire', 'url' => $url];
        header('Content-Type:application/json; charset=utf-8');
        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
        exit($jsonData);
    }

}//end
