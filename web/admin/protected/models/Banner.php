<?php

/**
 * This is the model class for table "{{banner}}".
 *
 * The followings are the available columns in table '{{banner}}':
 * @property integer $id
 * @property string $title
 * @property string $link
 * @property string $code
 * @property integer $type
 * @property integer $sys_type
 * @property string $image_url
 * @property string $add_time
 */
class Banner extends CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{banner}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('code, type', 'required'),
			array('type, sys_type', 'numerical', 'integerOnly'=>true),
			array('title', 'length', 'max'=>100),
			array('link, image_url', 'length', 'max'=>255),
			array('code', 'length', 'max'=>20),
			array('add_time', 'safe'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, title, link, code, type, sys_type, image_url, add_time', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => '主键自增id',
			'title' => '标题',
			'link' => '链接地址',
			'code' => '用于app跳转不同页面的id',
			'type' => '类型 1-基金 2-组合 3-文章',
			'sys_type' => '系统类型 1-app 2-pc 3-app & pc',
			'image_url' => '图片地址',
			'add_time' => '添加时间',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * Typical usecase:
	 * - Initialize the model fields with values from filter form.
	 * - Execute this method to get CActiveDataProvider instance which will filter
	 * models according to data in model fields.
	 * - Pass data provider to CGridView, CListView or any similar widget.
	 *
	 * @return CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function search()
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('title',$this->title,true);
		$criteria->compare('link',$this->link,true);
		$criteria->compare('code',$this->code,true);
		$criteria->compare('type',$this->type);
		$criteria->compare('sys_type',$this->sys_type);
		$criteria->compare('image_url',$this->image_url,true);
		$criteria->compare('add_time',$this->add_time,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Banner the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
