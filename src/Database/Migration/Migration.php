<?php
namespace CST\Yii\Illuminate\Database\Migration;

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Class Migration
 */
abstract class Migration extends \CDbMigration
{
    const DSN_REGEX = '/^(?<driver>\w+):host=(?<host>[.\w|-]+)(:(?<port>\d+))?\;dbname=(?<database>[.\w]+)/im';

    public function __construct()
    {
        $connInfo = $this->parseDsn($this->getDbConnection()->connectionString);
        $capsule = new Capsule;
        $capsule->addConnection([
            'driver'    => $connInfo['driver'],
            'host'      => $connInfo['host'],
            'database'  => $connInfo['database'],
            'username'  => $this->getDbConnection()->username,
            'password'  => $this->getDbConnection()->password,
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ]);

        // Make this Capsule instance available globally via static methods... (optional)
        $capsule->setAsGlobal();
    }

    /**
     * Parse a DSN-string, user:password@host:port\database, and break it into it's components.
     * Password is optional.
     *
     * Many thanks to Vision.
     *
     * @param string $dsn DSN string to parse.
     * @return array|bool Array on success, false on error.
     */
    function parseDsn($dsn)
    {
        $result = array
        (
            'user' => '',
            'password' => '',
            'host' => 'localhost',
            'port' => 3306,
            'database' => '',
            'driver' => ''
        );
        if (strlen($dsn) == 0)
        {
            return false;
        }
        if (!preg_match(self::DSN_REGEX, $dsn, $matches))
        {
            return false;
        }
        if (count($matches) == 0)
        {
            return false;
        }
        foreach ($result as $key => $value)
        {
            if (array_key_exists($key, $matches) and !empty($matches[$key]))
            {
                $result[$key] = $matches[$key];
            }
        }
        return $result;
    }

}