<?php
namespace CST\Yii\Illuminate\Queue;

use CST\Yii\Illuminate\Database\BaseModel;

class FailedJob extends BaseModel
{
    private static $tableName = 'failed_jobs';
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
     * @param string $tableName
     */
    public static function setTableName($tableName)
    {
        self::$tableName = $tableName;
    }

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return self::$tableName;
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