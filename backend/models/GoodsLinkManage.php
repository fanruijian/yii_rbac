<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "yii2_goods_link_manage".
 *
 * @property int $id
 * @property string $title 标题
 * @property int $type 类型 1 single
 * @property string $goods_from 商品来源
 * @property string $img 商品图片
 * @property string $href 商品链接地址
 * @property string $desc 描述
 * @property int $status 商品状态， 1 正常， 2 删除
 * @property string $created_at
 * @property string $modified_at
 */
class GoodsLinkManage extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'yii2_goods_link_manage';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title', 'goods_from', 'img', 'href'], 'required'],
            [['type', 'status'], 'integer'],
            [['desc'], 'string'],
            [['created_at', 'modified_at'], 'safe'],
            [['title'], 'string', 'max' => 100],
            [['goods_from', 'img', 'href'], 'string', 'max' => 200],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'type' => 'Type',
            'goods_from' => 'Goods From',
            'img' => 'Img',
            'href' => 'Href',
            'desc' => 'Desc',
            'status' => 'Status',
            'created_at' => 'Created At',
            'modified_at' => 'Modified At',
        ];
    }
}
