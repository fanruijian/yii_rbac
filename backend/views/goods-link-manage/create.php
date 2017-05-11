<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\GoodsLinkManage */

$this->title = 'Create Goods Link Manage';
$this->params['breadcrumbs'][] = ['label' => 'Goods Link Manages', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="goods-link-manage-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
