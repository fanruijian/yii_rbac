<?php

namespace backend\controllers;

use Yii;
use backend\models\GoodsLinkManage;
use backend\models\GoodsLinkManageSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * GoodsLinkManageController implements the CRUD actions for GoodsLinkManage model.
 */
class GoodsLinkManageController extends BaseController
{

    /**
     * Lists all GoodsLinkManage models.
     * @return mixed
     */
    public function actionIndex()
    {
        
        $this->setForward();
        $searchModel = new GoodsLinkManageSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single GoodsLinkManage model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new GoodsLinkManage model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionAdd()
    {
        $model = new GoodsLinkManage();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $saveData = $this->one('GoodsLinkManage',['id' => $model->id]);
            $this->service -> addGoodsRedis($saveData);
            return $this->redirect(['index', 'id' => $model->id]);
        } else {
            $model->loadDefaultValues();
            return $this->render('edit', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing GoodsLinkManage model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionEdit($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $saveData = $this->one('GoodsLinkManage',['id' => $model->id]);
            $this->service -> editGoodsRedis($saveData);
            return $this->redirect(['index', 'id' => $model->id]);
        } else {
            return $this->render('edit', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing GoodsLinkManage model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        $this->service -> delGoodsRedis($id);
        return $this->redirect(['index']);
    }

    /**
     * Finds the GoodsLinkManage model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return GoodsLinkManage the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = GoodsLinkManage::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionAaa(){
        $this->service->getAaa();
    }
}
