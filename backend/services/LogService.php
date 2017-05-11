<?php
namespace backend\services;

use backend\models\Log;
use backend\utils\Functions as F;
use yii\log\FileTarget;

class LogService extends BaseService {
    public $log = null;
    public $fileLog = null;
    public $logFlag = null;

    public function __construct() {
        parent::__construct();
        $this->logFlag = \yii::$app->params['filelog'];
    }

    /**
     * 记录操作 log 
     *
     */
    public function log($model, $attributes, $oldAttributes=[], $action) { 
        $this->log = new Log;
        $this->setUserInfo();
        $this->setModelTag($model);
        $this->setProperties($attributes, $oldAttributes);
        $this->setActionName($action);
        $this->log->save();
    }

    /**
     * log change 
     */
    public function logUp($model, $attributes, $changedAttributes, $action) {
        $oldAttributes = [];
        $this->log = new Log;
        $this->setUserInfo();
        $this->setModelTag($model);
        $this->setProperties($attributes, $oldAttributes, $changedAttributes);
        $this->setActionName($action); 
        $this->log->save();
    }

    /**
     * 设置 log action name
     */
    public function setActionName($action) {
        if ($action)
            $this->log->action = 'insert';
        else 
            $this->log->action = 'update';
    }

    /**
     * 设置模型的属性值
     */
    public function setProperties($attributes, $oldAttributes, $updateAttributes=[]) {
        $this->log->properties = $this->toJson($attributes);
        $this->log->old_properties = $this->toJson($oldAttributes);
        if (empty($updateAttributes)) {
            $updateAttributes = array_diff($attributes, $oldAttributes);
        }
        $this->log->updated_fields = $this->toJson($updateAttributes);
    }

    /**
     * 获取操作所属的 tag
     */
    public function setModelTag($model) {
        $this->log->model = $model;
        return $this->log->tag = $model;
    }

    /**
     * 获取操作者的基本信息
     */
    public function setUserInfo() {
        // if from command, no $_SESSION defined yet
        if (isset($_SESSION)) {
            $userType = $_SESSION['user_type'];
            $userId = $_SESSION['user_id'];
            $userName = $_SESSION['user_name'];
        } else {
            $userType = '1';
            $userId = '1';
            $userName = 'command';
        }
        $this->log->user_type = $userType;
        $this->log->user_id = $userId;
        $this->log->user_name = $userName;
        $this->log->request_ip = F::get_client_ip();
    }

    /**
     * 记录文件log
     */
    public function setFileLog($logmessage, $category, $level) {
        if ($this->logFlag) {
             $fileName = date('Ymd');
             $path = \Yii::$app->getRuntimePath().DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR.$fileName;
             if (!is_dir($path))
                 mkdir($path, 0777); 
             $this->fileLog = new FileTarget();
             $this->fileLog->logFile = $path.DIRECTORY_SEPARATOR."{$fileName}.log";
             $this->fileLog->maxLogFiles = 5;
             $this->fileLog->messages[] = ["{$logmessage}\r\n",$level ,$category, time()];
             $this->fileLog->export();
         }
    }
}//end
