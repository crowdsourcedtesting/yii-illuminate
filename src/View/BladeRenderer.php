<?php
namespace CST\Yii\Illuminate\View;

use CST\Yii\Illuminate\View\Engines\Blade;

class BladeRenderer extends \CViewRenderer
{
    /**
     * @var string the file-extension for viewFiles this renderer should handle
     * for smarty templates this usually is .tpl
     */
    public $fileExtension = '.php';
    /**
     * @var string path alias of the directory where the Smarty.class.php file can be found.
     * Also plugins and sysplugins directory should be there.
     */
    public $viewsDir = 'application.views';
    public $cacheDir = 'application.runtime.cache';

    /**
     * @var Blade
     */
    public $blade;

    /**
     * Renders a view file.
     *
     * This method is required by {@link IViewRenderer}.
     *
     * @param \CBaseController $context the controller or widget who is rendering the view file.
     * @param string $sourceFile the view file path
     * @param mixed $data the data to be passed to the view
     * @param boolean $return whether the rendering result should be returned
     * @return mixed the rendering result, or null if the rendering result is not needed.
     * @throws \CException
     */
    public function renderFile($context, $sourceFile, $data, $return)
    {
        // current controller properties will be accessible as {$this->property}
        $data['this'] = $context;
        $data['controller'] = $context;
        // Yii::app()->... is available as {Yii->...} (deprecated, use {Yii::app()->...} instead, Smarty3 supports this.)
        $data['Yii'] = \Yii::app();
        // time and memory information
        //$data['TIME'] = sprintf('%0.5f', \Yii::getLogger()->getExecutionTime());
        // check if view file exists


        if (!is_file($sourceFile) || ($file = realpath($sourceFile)) === false)
            throw new \CException(\Yii::t('Messages', 'View file "{file}" does not exist.', array('{file}' => $sourceFile)));

        $path = explode(DIRECTORY_SEPARATOR, $sourceFile);
        $path = array_slice($path, array_search('views', $path) + 1);
        $file = implode(DIRECTORY_SEPARATOR, $path);
        $file = pathinfo($file, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . pathinfo($file, PATHINFO_FILENAME);


        //if blade
        if (stripos($file, '.blade') !== false) {

            $viewsPath = explode(DIRECTORY_SEPARATOR, $sourceFile);
            $viewsPath = array_slice($viewsPath, 0, array_search('views', $viewsPath) + 1);
            $viewsPath = implode(DIRECTORY_SEPARATOR, $viewsPath);

            $cache = \Yii::getPathOfAlias($this->cacheDir);
            $this->blade = new Blade($viewsPath, $cache);

            $file = str_ireplace('.blade', '', $file);

            /**
             * @var $template \Illuminate\View\View
             */
            $template = $this->blade->view()->make($file, $data);

            // render or return
            if ($return)
                return $template->render();

            echo $template->render();
        }

        return \Yii::app()->controller->renderInternal($sourceFile, $data, $return);
    }

    /**
     * Parses the source view file and saves the results as another file.
     * @param string $sourceFile the source view file path
     * @param string $viewFile the resulting view file path
     */
    protected function generateViewFile($sourceFile, $viewFile)
    {
        $path = explode(DIRECTORY_SEPARATOR, $sourceFile);
        $path = array_slice($path, array_search('views', $path) + 1);
        $file = implode(DIRECTORY_SEPARATOR, $path);
        $file = pathinfo($file, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . pathinfo($file, PATHINFO_FILENAME);

        $template = $this->blade->view()->make($file);

        file_put_contents($viewFile, $template->render());
    }

    /**
     * Generates the resulting view file path.
     * @param string $file source view file path
     * @return string resulting view file path
     */
    protected function getViewFile($file)
    {
        if ($this->useRuntimePath) {
            $crc = sprintf('%x', crc32(get_class($this) . \Yii::getVersion() . dirname($file)));
            $viewFile = \Yii::app()->getRuntimePath() . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $crc . DIRECTORY_SEPARATOR . basename($file);
            if (!is_file($viewFile))
                @mkdir(dirname($viewFile), $this->filePermission, true);
            return $viewFile;
        } else
            return $file . 'c';
    }
}