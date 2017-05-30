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
    	$this->make_cors();
        if (Yii::$app->request->isPost) {
            $content = file_get_contents("php://input");
            $this->jsonObj = json_decode($content, TRUE);
        }
        $controller = $this->id;
        $action = explode('/',$controller);
        if(count($action) == 2){
            $action = $action[1];
        }
        $header = getallheaders();
        if(in_array($action,\Yii::$app->params['api_list'])){
            if(isset($header['Authorization'])){
                $token = $header['Authorization'];
                $tArr = explode(' ', $token);
                $token = $tArr[1];
            }
            if(empty($token)){
                $jwt = $this->getJWT();
                if($jwt){
                    $this->token = $jwt;
                }else{
                    header('HTTP/1.1 401 Unauthorized'); 
                }
            }else{
                $checkToken = $this->findIdentityByAccessToken($token);
                $msg = (String) $checkToken;
                if($checkToken!='true'){
                    $this->jsonReturn(['status'=>0,'msg'=>$checkToken]);
                }
            }
            
        }
    }

    function make_cors($origin = '*') {
 
        $request_method = $_SERVER['REQUEST_METHOD'];
     
        if ($request_method === 'OPTIONS') {
     
            header('Access-Control-Allow-Origin:'.$origin);
            header('Access-Control-Allow-Headers:authorization,content-type');
            header('Access-Control-Allow-Credentials:true');
            header('Access-Control-Allow-Methods:GET,HEAD,PUT,PATCH,POST,DELETE');
     
            header('Access-Control-Max-Age:1728000');
            header('Content-Type:application/json charset=UTF-8');
            header('Content-Length: 0',true);
     
            header('status: 204');
            header('HTTP/1.0 204 No Content');
        }
     
        if ($request_method === 'POST') {
     
            header('Access-Control-Allow-Origin:'.$origin);
            header('Access-Control-Allow-Credentials:true');
            header('Access-Control-Allow-Methods:GET, POST, OPTIONS');
     
        }
     
        if ($request_method === 'GET') {
     
            header('Access-Control-Allow-Origin:'.$origin);
            header('Access-Control-Allow-Credentials:true');
            header('Access-Control-Allow-Methods:GET, POST, OPTIONS');
     
        }
     
    }
}
