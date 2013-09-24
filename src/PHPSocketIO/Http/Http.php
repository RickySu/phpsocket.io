<?php
namespace PHPSocketIO\Http;

use PHPSocketIO\Response\Response;
use PHPSocketIO\Request\Request;
use PHPSocketIO\Protocol\Handshake;

class Http
{

    public static function handleRequest(Request $request)
    {
        $handshakeResult = Handshake::initialize($request);

        if ($handshakeResult === Handshake::PROTOCOL_HTMLFILE) {
            return;
        }

        if ($handshakeResult instanceof Response) {
            $request->getConnection()->write($handshakeResult, true);

            return;
        }
    }

    public static function parseRequest($server, $headers, $content)
    {
        $SERVER = array_merge($server, static::parseServer($headers));
        if (isset($headers['HTTP_COOKIE'])) {
            $COOKIE = static::parseCookie($headers['HTTP_COOKIE']);
        } else {
            $COOKIE = array();
        }
        if (($pos = strpos('?', $SERVER['REQUEST_URI'])) !== false) {
            $SERVER['QUERY_STRING'] = substr($SERVER['REQUEST_URI'], $pos+1);
        } else {
            $SERVER['QUERY_STRING'] = '';
        }
        $GET = static::parseGET($SERVER['QUERY_STRING']);
        $POST = static::parsePOST($content);

        return new Request($GET, $POST, array(), $COOKIE, array(), $SERVER, $content);
    }

    protected static function parseGET($rawGET)
    {
        parse_str($rawGET, $GET);

        return $GET;
    }

    protected static function parsePOST($rawPOST)
    {
        parse_str($rawPOST, $POST);

        return $POST;
    }

    protected static function parseCookie($rawCookie)
    {
        $COOKIE = array();
        foreach (explode(';', $rawCookie) as $cookie) {
            $pos = strpos($cookie, '=');
            if ($pos === null) {
                return array();
            }
            $COOKIE[trim(substr($cookie, 0, $pos))] = trim(substr($cookie, $pos+1));
        }

        return $COOKIE;
    }

    protected static function parseServer($headers)
    {
        foreach ($headers as $key => $value) {
            $key = strtoupper(str_replace('-', '_', $key));
            $SERVER["HTTP_$key"] = $value;
        }

        return $SERVER;
    }

}
