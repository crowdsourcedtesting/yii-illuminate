<?php
namespace CST\Yii\Illuminate\Exception;

use CLogger;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler as Handler;
use Yii;

class ExceptionHandler implements Handler
{

    /**
     * Report or log an exception.
     *
     * @param  \Exception $e
     * @throws Exception
     */
    public function report(Exception $e)
    {
        throw $e;
//        Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Exception $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Exception $e)
    {
        // TODO: Implement render() method.
    }

    /**
     * Render an exception to the console.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface $output
     * @param  \Exception $e
     * @return void
     */
    public function renderForConsole($output, Exception $e)
    {
        // TODO: Implement renderForConsole() method.
    }
}