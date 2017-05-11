<?php

namespace backend\controllers\api;


use yii\web\Controller;
use Yii;
use Yii\web\Request;
use backend\services\UsersService;
use backend\traits\JwtTrait;
use backend\traits\RequestTrait;
use backend\traits\DataTrait;
use backend\traits\ModelTrait;
use backend\traits\HierarchyTrait;

/**
 * Trait to handle JWT-authorization process. Should be attached to User model.
 * If there are many applications using user model in different ways - best way
 * is to use this trait only in the JWT related part.
 */
class BaseController extends Controller
{
	use RequestTrait, ModelTrait, DataTrait, HierarchyTrait,JwtTrait;
    public $enableCsrfValidation = false;
    public $jsonObj;
    public $token = null;
    public function init(){
    	header('Access-Control-Allow-Origin: *');
        // 如果是get请求直接返回，不进行权限验证
        if (Yii::$app->request->isPost) {
            $content = file_get_contents("php://input");
            $this->jsonObj = json_decode($content, TRUE);
            $controller = $this->id;
            $action = explode('/',$controller);
            if(count($action) == 2){
                $action = $action[1];
            }
            if(in_array($action,\Yii::$app->params['api_list'])){
                $post = $this->jsonObj;
                if(isset($post['token'])) $token = $post['token'];
                if(empty($token)){
                    $jwt = $this->getJWT();
                    if($jwt){
                        $this->token = $jwt;
                    }else{
                        $this->jsonReturn(['status'=>0,'msg'=>'权限验证失败!']);
                    }
                }else{
                    $checkToken = $this->findIdentityByAccessToken($token);
                    if(!$checkToken) $this->jsonReturn(['status'=>0,'msg'=>'权限验证失败!']);
                }
                
            }
        }
    }
}
