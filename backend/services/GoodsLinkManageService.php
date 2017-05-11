<?php
namespace backend\services;

use backend\utils\Functions as F;
use backend\services\RedisCacheService;

class GoodsLinkManageService extends BaseService {
    
    public $goodsMatrix = [
        'id'           => 'id',
        'title'        => 'title',
        'type'         => ['type', '$v==1&&$v="single";
                                    $v==2&&$v="banner";
                                      return $v;'],
        'src'          => 'goods_from',
        'img'          => 'img',
        'href'         => 'href',
        'desc'         => 'desc',
    ];

    public $goodsLinkList = [];

    public function init()
    {
        parent::init();
        $this->redis = new RedisCacheService;
    }

    public function addGoodsRedis($data){
        $index = 'goods_link_list_'.$data['id'];
        foreach ($data as $key => $value) {
            $this->redis->hsetData($index, $key, $value);
        }
        $maxIndex = 'goods_link_list_max';
        $this->redis->delData($maxIndex);
        $this->redis->setData($maxIndex,$data['id']);        
    }

    public function editGoodsRedis($data){
        $index = 'goods_link_list_'.$data['id'];
        $this->redis->delData($index);
        foreach ($data as $key => $value) {
            $this->redis->hsetData($index, $key, $value);
        }
    }

    public function delGoodsRedis($id){
        $index = 'goods_link_list_'.$id;
        $this->redis->delData($index);
    }

    public function getGoodsLinkManageList($page,$num=5,$pageStatus='more',$startIndex='null'){
        if($pageStatus == 'refresh' && $startIndex=='null'){
            $startIndex = ($page-1)*$num;
        }elseif($pageStatus == 'more' && $startIndex=='null'){
            $startIndex = $page*$num+1;
        }
        if(count($this->goodsLinkList)<$num){
            $key = 'goods_link_list_'.$startIndex;
            $res = $this->redis->getHashData($key);
            if(!empty($res)){
                $res = $this->hashToArray($res);
                array_push($this->goodsLinkList,$res);
            }
            if($pageStatus=='refresh'){
                $startIndex--;
                if($startIndex>0) $this->getGoodsLinkManageList($page,$num,$pageStatus,$startIndex);
            }else{
                $startIndex++;
                $maxIndex = $this->redis->getData('goods_link_list_max');
                if($startIndex<=$maxIndex) $this->getGoodsLinkManageList($page,$num,$pageStatus,$startIndex);
            }
        }
        $ret = $this->fieldsMap($this->goodsMatrix, $this->goodsLinkList);
        return $ret;
    }

    public function hashToArray($data){
        $arr = [];
        $count = count($data);
        for ($i=0; $i < $count; $i=$i+2) {
            $arr = array_merge($arr,[$data[$i]=>$data[$i+1]]);
        }
        return $arr;
    }

}//end
