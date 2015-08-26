<?php
namespace CST\Yii\Illuminate\Console;

class MigrateCommand extends \MigrateCommand
{
    public $interactive = false;

    /**
     * @var string table name
     */
    public $table;

    protected function getNewMigrations()
    {
        $applied = array();
        foreach ($this->getMigrationHistory(-1) as $version => $time) {
//            $applied[substr($version, 1, 13)] = true;
            $applied[$version] = true;
        }

        $migrations = array();
        $handle = opendir($this->migrationPath);
        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $this->migrationPath . DIRECTORY_SEPARATOR . $file;
            if (preg_match('/^(m(\w+?)_.*?)\.php$/', $file,
                    $matches) && is_file($path) && !isset($applied[$matches[1]])
            ) {
                $migrations[] = $matches[1];
            }
        }
        closedir($handle);
        sort($migrations);
        return $migrations;
    }

    protected function getTemplate()
    {
        if ($this->templateFile !== null) {
            $reflector = new \ReflectionClass('\CST\Yii\Illuminate\Database\Migration\Migration');
            $path = dirname($reflector->getFileName()) . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR;

            return file_get_contents($path . $this->templateFile . '.stub');
        }

        return parent::getTemplate();
    }

    /**
     * @param $args
     * @return int|void
     */
    public function actionCreate($args)
    {
        if (isset($args[0])) {
            $tableClassName = $args[0];
        } else {
            $this->usageError('Please provide the name of the new migration.');
        }

        if (!preg_match('/^\w+$/', $tableClassName)) {
            echo "Error: The name of the migration must contain letters, digits and/or underscore characters only.\n";

            return 1;
        }

        //detect if it's a CREATE or ALTER
        if (stripos($tableClassName, 'create_') === 0) {
            $this->templateFile = 'create';
        } else {
            $this->templateFile = 'alter';
        }

        $tableClassName = 'm' . gmdate('ymd_His') . '_' . $tableClassName;

        $stub = str_replace(
            ['{{table}}', '{{tableClassName}}'], [$this->table, $tableClassName],
            $this->getTemplate()
        );

        $file = $this->migrationPath . DIRECTORY_SEPARATOR . $tableClassName . '.php';

        if ($this->confirm("Create new migration '$file'?")) {
            file_put_contents($file, $stub);
            echo "New migration created successfully.\n";
        }
    }

}