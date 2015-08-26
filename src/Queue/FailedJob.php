<?php
namespace CST\Yii\Illuminate\Queue;

use CST\Yii\Illuminate\Database\BaseModel;

class FailedJob extends BaseModel
{
    public $timestamps = false;

    protected $dates = ['failed_at'];

    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return static the static model class
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'failed_jobs';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return [
            ['id', 'numerical', 'integerOnly' => true],
            ['connection, queue, payload, failed_at', 'safe'],
        ];
    }
}