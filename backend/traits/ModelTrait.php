<?php
namespace backend\traits;

trait ModelTrait {

    public function M($model, $id='') {
        $nsModel = 'backend\models\\'.ucfirst($model);
        if ($id) {
            $M = $nsModel::find()->asArray()->indexBy('id')->all();
            if (isset($M[$id])) return $M[$id];
            return array_shift($M);
        } else {
            $M = $nsModel::find()->asArray()->indexBy('id')->all();
        }
        return $M;
    }

    /**
     * unique 约束下的 save
     *
     * 若 $data 提供的条件已有持久化数据，则返回数据
     * 否则保存并返回新建的持久化数据（包括 id 号）
     */
    public function checkAndSave($model, $data) {
        $nsModel = 'backend\models\\'.ucfirst($model);
        $M = $nsModel::findOne($data);
        if ($M) return $M->attributes;
        return $this->save($model, $data);
    }

    public function saveData($model, $data) {
        return $this->save($model, $data, true, false);
    }

    /**
     * sub admin cannot save/update
     */
    public function tryDenySubAdmin($model)
    {
        if (stristr('log', $model) !== false) return true;
        if (isset($_SESSION['usertype']) && $this->s('usertype') == 'admin' && $this->s('group_name') && !$this->s('group_root')) {
            return $this->NG($this->t('user', "No such right!"));
        }
    }

    // try to save model
    public function save($model, $data=[], $validate=true, $useInput=true) {
        $this->tryDenySubAdmin($model);
        $iData = [];
        if ($useInput) {
            $iData = array_merge($_POST, $_GET);
        }
        $nsModel = 'backend\models\\'.ucfirst($model);
        $model = new $nsModel;
        $pk = '';
        if (isset($iData['id'])) {
            $pk = $iData['id'];
        }
        isset($data['id']) && $pk = $data['id'];
        if ($pk && $pk >  0) $model = $nsModel::findOne($pk);
        $attrs = array_merge($data, $iData);
        $model->attributes = $attrs;
        try {
            $data = array_merge($iData, $data);
            $model->load($data);
            $model->save($validate);
            $result = $model->attributes;
        } catch (yii\db\CDbException $e) {
            $this->NG($e->getMessage());
        }
        $errors = $model->getFirstErrors();
        if ($errors) {
            $this->NG('出错：'.array_values($errors)[0]);    
        }
        return $result;
    }

    public function update($model, $where, $data=['status'=>0]) {
        $nsModel = 'backend\models\\'.ucfirst($model);
        $no = $nsModel::updateAll($data, $where);
        if ($no) {
            $data = array_merge($where, $data);
            $log = new \backend\services\LogService();
            $log->logUp($model, $data, [], false); 
            return true;
        }
        return false;
    }

    /**
     * 查询相应数据表中的所有符合条件的数据
     */
    public function getAll($model, $where='', $indexBy='id', $asArray=true) {
        $nsModel = 'backend\models\\'.ucfirst($model);
        if ($asArray) {
            $ret = $nsModel::find()->where($where)->indexBy($indexBy)->asArray()->all();
        } else {
            $ret = $nsModel::findAll($where)->indexBy($indexBy);
        }
        return $ret;
    }

    /**
     * 抽取指定字段集合
     */
    public function getField($model, array $fields, $where='', $isAll=true, $indexBy='id', $asArray=true) 
    {
        $all = $this->getAll($model, $where, $indexBy, $asArray);
        if (!$isAll) {
            $all = [$all];
        }

        $filterDatas = [];
        $fields = array_flip($fields);
        foreach ($all as $data) {
            $filterDatas[] = array_intersect_key($data, $fields);
        } 
        if (!$isAll) {
            $filterDatas = array_shift($filterDatas);
        }
        return $filterDatas;
    }

    public function one($model, $where=[], $indexBy='guid') {
        $all = $this->getAll($model, $where);
        if ($all) return array_pop($all);
        return [];
    }

    /**
     * 按唯一键（组合unique字段）保存或更新记录
     */
    public function saveOn($model, $where, $data) {
        $nsModel = 'backend\models\\'.ucfirst($model);
        $exist = $this->getAll($model, $where);

        if (!empty($exist)) {
            foreach ($exist as $key => $value) {
                $updateModel = $nsModel::findone($value['id']);
                $attributeNames = array_keys($data);
                if (in_array('modified_at', array_keys($updateModel->attributes))) {
                    $attributeNames[] = 'modified_at';
                }
                $updateModel->attributes = $data;
                $updateModel->update(true, $attributeNames);
            }
            return $this->one($model, $where);
        } else {
            $model = new $nsModel;
            $model->attributes = $data;
            $result = $model->save(true);
            if (!$result) {
                return $model->getFirstErrors();
            } else {
                $result = $model->attributes;
            }
            return $result;
        }
    }

    public function query($sql, $matrix=[], $row='all') {
        $db = \Yii::$app->db;
        if ($this->S('ssp_distribution')) {
            $db = $this->S('ssp_distribution').'Db';
            $db = \Yii::$app->$db;
            $prefix = $this->S('ssp_distribution').'_';
            $sql = str_replace('vam_', $prefix, $sql);
        }
        $cmd = $db->createCommand($sql);
        if ($row == 'all') {
            $datas = $cmd->queryAll();
        } else if ($row == 'one') {
            $datas = $cmd->queryOne();
        }
        if (!empty($matrix)) {
            $datas = $this->fieldsMap($matrix, $datas);
        }
        return $datas;
    }

    public function executeSql($sql) {
        $db = \Yii::$app->db;
        $cmd = $db->createCommand($sql);
        return $cmd->execute();
    }

    public function queryOne($sql) {
        return $this->query($sql, [], 'one');
    }

    public function queryAll($sql, $matrix=[]) {
        return $this->query($sql, $matrix, 'all');
    }

    public function saveAll($model, array $datas, $useInput=true) {
        $result = [];
        foreach ($datas as $data) {
            $result[] = $this->save($model, $data, true, $useInput);
        }
        return $result;
    }

    public function saveDataAll($model, $datas) {
        return $this->saveAll($model, $datas, false);
    }

    public function deleteAll($model, $where) {
        $nsModel = 'backend\models\\'.ucfirst($model);
        return $nsModel::deleteAll($where);
    }

    public function getSingleColumn($model, $where, array $columns) {
        $datas = $this->getColumn($model, $where, $columns);
        return array_shift($datas);
    }

    public function getColumn($model, $where, array $columns) {
        $nsModel = 'backend\models\\'.ucfirst($model);
        $datas = $nsModel::find()->where($where)->select($columns)->asArray()->all(); 
        if (count($columns) == 1) {
            $result = [];
            $field = array_pop($columns);
            foreach ($datas as $data) {
                if ($data[$field]) {
                    $result[] = $data[$field];
                }
            }
            return $result;
        }
        return $datas;
    }

    /**
     * update model columns
     */
    public function setField($model, $where, array $columns) {
        $this->tryDenySubAdmin($model);
        $nsModel = 'backend\models\\'.ucfirst($model);
        $dummy = new $nsModel;
        $models = $nsModel::findAll($where);
        if(in_array('modified_at', array_keys($dummy->attributes))) {
            $columns['modified_at'] = date('Y-m-d H:i:s');
        }
        foreach ($models as $model) {
            $model->updateAttributes($columns);
        }
        return count($models);
    }
}//end
