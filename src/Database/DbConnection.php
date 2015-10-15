<?php
namespace CST\Yii\Illuminate\Database;

use CDbConnection;
use CDbException;

class DbConnection extends CDbConnection
{
    /**
     * @var string
     */
    public $readConnectionString;

    /**
     * @var string
     */
    public $writeConnectionString;

    public function init()
    {
        if (!$this->connectionString && $this->readConnectionString) {
            $this->connectionString = $this->readConnectionString;
        }

        parent::init();
    }

    public function isReadWrite()
    {
        return $this->writeConnectionString;
    }

    public function setWriteMode()
    {
        if (!$this->writeConnectionString) {
            throw new CDbException(sprintf('%s.writeConnectionString cannot be empty.', self::class));
        }

        $this->connectionString = $this->writeConnectionString;
        $this->close();
    }
}