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
class UserController extends BaseController
{
    public $enableCsrfValidation = false;

    public function init(){
        parent::init();
    }

    public function actionLogin(){
        $list = [
              'token' => $this->token,
        ];
        $this->jsonReturn($list);
    }
}
