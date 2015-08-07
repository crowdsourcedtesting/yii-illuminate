<?php
namespace CST\Yii\Illuminate\Console;

class MigrateCommand extends \MigrateCommand
{
    public $interactive = false;

    /**
     * @var string table name
     */
    public $table;

    protected function getTemplate()
    {
        if ($this->templateFile !== null) {
            $path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'stubs';

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
            $name = $args[0];
        } else {
            $this->usageError('Please provide the name of the new migration.');
        }

        if (!preg_match('/^\w+$/', $name)) {
            echo "Error: The name of the migration must contain letters, digits and/or underscore characters only.\n";

            return 1;
        }

        //detect if it's a CREATE or ALTER
        if (stripos($name, 'create_') === 0) {
            $this->templateFile = 'create';
        } else {
            $this->templateFile = 'alter';
        }

        $name = 'm' . gmdate('ymd_His') . '_' . $name;
        $content = strtr($this->getTemplate(), array('{ClassName}' => $name));
        $file = $this->migrationPath . DIRECTORY_SEPARATOR . $name . '.php';

        //set table name (if available)
        if (!empty($this->table)) {
            $content = str_ireplace('TABLE_NAME', $this->table, $content);
        }

        if ($this->confirm("Create new migration '$file'?")) {
            file_put_contents($file, $content);
            echo "New migration created successfully.\n";
        }
    }

}