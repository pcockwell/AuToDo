<?php namespace Autodo\Facades;

use Illuminate\Support\Facades\Response as BaseResponse;
use Autodo\Http\Response as AutodoResponse;

class Response extends BaseResponse{

    /**
     * Return a new response from the application.
     *
     * @param  string  $content
     * @param  int     $status
     * @param  array   $headers
     * @return \Illuminate\Http\Response
     */
    public static function make($content = '', $status = 200, array $headers = array())
    {
        return new AutodoResponse($content, $status, $headers);
    }
}