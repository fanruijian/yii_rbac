<?php

use yii\helpers\Html;
use common\core\ActiveForm;
use common\helpers\ArrayHelper;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model backend\models\Ad */
/* @var $form common\core\ActiveForm */
?>

<?php $form = ActiveForm::begin([
    'options'=>[
        'class'=>"form-aaa ",
        'enctype'=>"multipart/form-data",
    ]
]); ?>

<?= $form->field($model, 'title')->textInput(['maxlength' => true])->label('商品名称'); ?>

    <?= $form->field($model, 'type')->selectList(
    ['1'=>'sigle','2'=>'banner'],
    ['class'=>'form-control c-md-2'])->label('类型') ?>

    <?= $form->field($model, 'goods_from')->textInput(['maxlength' => true])->label('商品来源') ?>

    <?= $form->field($model, 'img')->textInput(['maxlength' => true])->label('商品图片'); ?>

    <?= $form->field($model, 'href')->textInput(['maxlength' => true])->label('链接地址'); ?>

    <?=$form->field($model, 'desc')->textarea(['class'=>'form-control c-md-4', 'rows'=>3])->label('商品描述')->hint('商品描述') ?>

<div class="form-actions">
    <?= Html::submitButton('<i class="icon-ok"></i> 确定', ['class' => 'btn blue ajax-post','target-form'=>'form-aaa']) ?>
    <?= Html::button('取消', ['class' => 'btn']) ?>
</div>

<?php ActiveForm::end(); ?>
