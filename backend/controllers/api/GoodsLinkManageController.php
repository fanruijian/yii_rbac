<?php

namespace backend\controllers\api;


use yii\web\Controller;
use Yii;
use yii\web\Request as WebRequest;
use backend\services\GoodsLinkManageService;

/**
 * Trait to handle JWT-authorization process. Should be attached to User model.
 * If there are many applications using user model in different ways - best way
 * is to use this trait only in the JWT related part.
 */
class GoodsLinkManageController extends BaseController
{
    public $enableCsrfValidation = false;

    public function init(){
        parent::init();
    }

    public function actionList(){
        $pageSize = \Yii::$app->params['goods_link_page']['page_size'];
        $has_more = true;
        $req = $this->I();
        if (Yii::$app->request->isPost){
            $req = $this->jsonObj;
        }
        if(isset($req['page'])){
            $page = $req['page'];
        }else{
            $page = 0;
        }
        $ac = 'more';
    	$glms = new GoodsLinkManageService();
        $res = $glms -> getGoodsLinkManageList($page,$pageSize,$ac);
        $count = count($res);
        if($count!=$pageSize) $has_more=false;
        if($ac=='more'){
            $page++;
        }else{
            $page--;
        }
        $list = [
              'return_count' => $count,
              'has_more' => $has_more,
              'page_id' => 'goods_link_list',
              'page' => $page,
              'data' => $res,
        ];
        if($this->token) $list['token'] = $this->token;
        $this->jsonReturn($list);
    }
}
