<?php
namespace backend\services;
use Yii;
class RedisCacheService extends BaseService {
    private $redis = null;
    
    public function init() {
        $this->redis = \Yii::$app->redis;
        if (!$this->redis->ping()) {
            return [];
        }
    }

    public function setData($key, $val)
    {
        return $this->redis->set($key, $val);
    }

    public function hsetData($key, $dim, $val)
    {
        return $this->redis->hset($key, $dim, $val);
    }

     public function hgetData($key, $dim)
    {
        return $this->redis->hget($key, $dim);
    }

    public function delData($key)
    {
        return $this->redis->del($key);
    }

    public function getData($key){
        return $this->redis->get($key);
    }

    public function getHashData($key){
        return $this->redis->hgetall($key);
    }

    public function hmsetData($key,$val){
        return $this->redis->hmset($key,$val);
    }

}//end
