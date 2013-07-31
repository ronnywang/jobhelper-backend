<?php

/**
 * Pix_Controller_Helper_Json
 *
 * @package Controller
 * @copyright 2003-2012 PIXNET Digital Media Corporation
 * @license http://framework.pixnet.net/license BSD License
 */
class Pix_Controller_Helper_Json extends Pix_Helper
{
    public function getFuncs()
    {
        return array('isJson', 'json', 'jsonp');
    }

    public function isJson($controller)
    {
        return preg_match('#application/json#', $_SERVER['HTTP_ACCEPT']);
    }

    public function json($controller, $obj)
    {
        header('Content-Type: application/json');
        $this->encodingFilter(@json_encode($obj));
        return $controller->noview();
    }

    public function jsonp($controller, $obj, $callback)
    {
        header('Content-Type: application/javascript');
        if (!preg_match('/^[a-zA-Z0-9_]+$/', strval($callback))) {
            return $controller->json($obj);
        }
        $this->encodingFilter($callback . '(' . @json_encode($obj) . ')');
        return $controller->noview();
    }

    public function encodingFilter($content)
    {
        $HTTP_ACCEPT_ENCODING = $_SERVER["HTTP_ACCEPT_ENCODING"];
        if( headers_sent() )
            $encoding = false;
        else if( strpos($HTTP_ACCEPT_ENCODING, 'x-gzip') !== false )
            $encoding = 'x-gzip';
        else if( strpos($HTTP_ACCEPT_ENCODING,'gzip') !== false )
            $encoding = 'gzip';
        else
            $encoding = false;

        if( $encoding ) {
            $_temp1 = strlen($content);
            if ($_temp1 < 2048)    // no need to waste resources in compressing very little data
                print($content);
            else {
                header('Content-Encoding: '.$encoding);
                print("\x1f\x8b\x08\x00\x00\x00\x00\x00");
                $content = gzcompress($content, 9);
                $content = substr($content, 0, $_temp1);
                print($content);
            }
        } else {
            echo $content;
        }
    }
}
