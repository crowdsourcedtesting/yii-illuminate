<?php

use Illuminate\Support\Str;

if (! function_exists('asset')) {
    /**
     * Generate an asset path for the application theme.
     *
     * @param  string  $path
     * @return string
     */
    function asset($path)
    {
        $baseUrl = Yii::app()->getBaseUrl(true);
        if (substr($path, 0, 1) == '/') {
            $themePath = Yii::app()->theme->baseUrl . $path;
        } else {
            $themePath = Yii::app()->theme->baseUrl . '/' . $path;
        }

        return $baseUrl . $themePath;
    }
}

if (! function_exists('url')) {
    /**
     * Generate a an absolute url for the application.
     *
     * @param  string  $path
     * @param  mixed   $parameters
     * @param  bool    $secure
     * @return string
     */
    function url($path = null, $parameters = [], $secure = null)
    {
        $schema = $secure === true ? 'https' : '';

        return Yii::app()->createAbsoluteUrl($path, $parameters, $schema);
    }
}

if (! function_exists('view')) {
    /**
     * Renders the evaluated view contents for the given view.
     *
     * @param  string  $view
     * @param  array   $data
     * @param  bool   $return
     * @return string
     */
    function view($view = null, $data = [], $return = false)
    {
        return app('controller')->render($view, $data, $return);
    }
}

if (! function_exists('viewPartial')) {
    /**
     * Renders the evaluated view contents for the given view and it does not apply a layout to the rendered result
     *
     * @param  string  $view
     * @param  array   $data
     * @param  bool    $return
     * @return string
     */
    function viewPartial($view = null, $data = [], $return = false)
    {
        return app('controller')->renderPartial($view, $data, $return);
    }
}

if (! function_exists('app')) {
    /**
     * Get the Yii App instance.
     *
     * @param  string  $component
     * @return string
     */
    function app($component = null)
    {
        if ($component) {
            return Yii::app()->{$component};
        }
        return Yii::app();
    }
}

if (! function_exists('request')) {
    /**
     * Get an instance of the current request or an input item from the request.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return \CHttpRequest|mixed
     */
    function request($key = null, $default = null)
    {
        if (is_null($key)) {
            return app('request');
        }
        return app('request')->getParam($key, $default);
    }
}

if (! function_exists('env')) {
    /**
     * Gets the value of an environment variable. Supports boolean, empty and null.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    function env($key, $default = null)
    {
        $value = getenv($key);
        if ($value === false) {
            return value($default);
        }
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return;
        }
        if (Str::startsWith($value, '"') && Str::endsWith($value, '"')) {
            return substr($value, 1, -1);
        }
        return $value;
    }
}