<?php

declare(strict_types=1);

namespace Flat3\Lodata\Helper;

/**
 * Url
 * @link https://github.com/jakeasmith/http_build_url
 * @package Flat3\Lodata\Helper
 */
class Url
{
    const HTTP_URL_REPLACE = 1;
    const HTTP_URL_JOIN_PATH = 2;
    const HTTP_URL_JOIN_QUERY = 4;
    const HTTP_URL_STRIP_USER = 8;
    const HTTP_URL_STRIP_PASS = 16;
    const HTTP_URL_STRIP_AUTH = 32;
    const HTTP_URL_STRIP_PORT = 64;
    const HTTP_URL_STRIP_PATH = 128;
    const HTTP_URL_STRIP_QUERY = 256;
    const HTTP_URL_STRIP_FRAGMENT = 512;
    const HTTP_URL_STRIP_ALL = 1024;

    /**
     * Build a URL.
     * The parts of the second URL will be merged into the first according to
     * the flags argument.
     * @param  mixed  $url  (part(s) of) an URL in form of a string or
     *                       associative array like parse_url() returns
     * @param  mixed  $parts  same as the first argument
     * @param  int  $flags  a bitmask of binary or'ed HTTP_URL constants;
     *                       HTTP_URL_REPLACE is the default
     * @param  array  $new_url  if set, it will be filled with the parts of the
     *                       composed url like parse_url() would return
     * @return string
     */
    public static function http_build_url($url, $parts = array(), $flags = self::HTTP_URL_REPLACE, &$new_url = array())
    {
        $flagsMap = [
            'HTTP_URL_REPLACE' => self::HTTP_URL_REPLACE,
            'HTTP_URL_JOIN_PATH' => self::HTTP_URL_JOIN_PATH,
            'HTTP_URL_JOIN_QUERY' => self::HTTP_URL_JOIN_QUERY,
            'HTTP_URL_STRIP_USER' => self::HTTP_URL_STRIP_USER,
            'HTTP_URL_STRIP_PASS' => self::HTTP_URL_STRIP_PASS,
            'HTTP_URL_STRIP_AUTH' => self::HTTP_URL_STRIP_AUTH,
            'HTTP_URL_STRIP_PORT' => self::HTTP_URL_STRIP_PORT,
            'HTTP_URL_STRIP_PATH' => self::HTTP_URL_STRIP_PATH,
            'HTTP_URL_STRIP_QUERY' => self::HTTP_URL_STRIP_QUERY,
            'HTTP_URL_STRIP_FRAGMENT' => self::HTTP_URL_STRIP_FRAGMENT,
            'HTTP_URL_STRIP_ALL' => self::HTTP_URL_STRIP_ALL,
        ];

        is_array($url) || $url = parse_url($url);
        is_array($parts) || $parts = parse_url($parts);

        isset($url['query']) && is_string($url['query']) || $url['query'] = null;
        isset($parts['query']) && is_string($parts['query']) || $parts['query'] = null;

        $keys = array('user', 'pass', 'port', 'path', 'query', 'fragment');

        // HTTP_URL_STRIP_ALL and HTTP_URL_STRIP_AUTH cover several other flags.
        if ($flags & self::HTTP_URL_STRIP_ALL) {
            $flags |= self::HTTP_URL_STRIP_USER | self::HTTP_URL_STRIP_PASS
                | self::HTTP_URL_STRIP_PORT | self::HTTP_URL_STRIP_PATH
                | self::HTTP_URL_STRIP_QUERY | self::HTTP_URL_STRIP_FRAGMENT;
        } elseif ($flags & self::HTTP_URL_STRIP_AUTH) {
            $flags |= self::HTTP_URL_STRIP_USER | self::HTTP_URL_STRIP_PASS;
        }

        // Schema and host are alwasy replaced
        foreach (array('scheme', 'host') as $part) {
            if (isset($parts[$part])) {
                $url[$part] = $parts[$part];
            }
        }

        if ($flags & self::HTTP_URL_REPLACE) {
            foreach ($keys as $key) {
                if (isset($parts[$key])) {
                    $url[$key] = $parts[$key];
                }
            }
        } else {
            if (isset($parts['path']) && ($flags & self::HTTP_URL_JOIN_PATH)) {
                if (isset($url['path']) && substr($parts['path'], 0, 1) !== '/') {
                    // Workaround for trailing slashes
                    $url['path'] .= "\0";
                    $url['path'] = rtrim(
                            str_replace(basename($url['path']), '', $url['path']),
                            '/'
                        ).'/'.ltrim($parts['path'], '/');
                } else {
                    $url['path'] = $parts['path'];
                }
            }

            if (isset($parts['query']) && ($flags & self::HTTP_URL_JOIN_QUERY)) {
                if (isset($url['query'])) {
                    parse_str($url['query'], $url_query);
                    parse_str($parts['query'], $parts_query);

                    $url['query'] = http_build_query(
                        array_replace_recursive(
                            $url_query,
                            $parts_query
                        )
                    );
                } else {
                    $url['query'] = $parts['query'];
                }
            }
        }

        if (isset($url['path']) && $url['path'] !== '' && substr($url['path'], 0, 1) !== '/') {
            $url['path'] = '/'.$url['path'];
        }

        foreach ($keys as $key) {
            $strip = $flagsMap['HTTP_URL_STRIP_'.strtoupper($key)];
            if ($flags & $strip) {
                unset($url[$key]);
            }
        }

        $parsed_string = '';

        if (!empty($url['scheme'])) {
            $parsed_string .= $url['scheme'].'://';
        }

        if (!empty($url['user'])) {
            $parsed_string .= $url['user'];

            if (isset($url['pass'])) {
                $parsed_string .= ':'.$url['pass'];
            }

            $parsed_string .= '@';
        }

        if (!empty($url['host'])) {
            $parsed_string .= $url['host'];
        }

        if (!empty($url['port'])) {
            $parsed_string .= ':'.$url['port'];
        }

        if (!empty($url['path'])) {
            $parsed_string .= $url['path'];
        }

        if (!empty($url['query'])) {
            $parsed_string .= '?'.$url['query'];
        }

        if (!empty($url['fragment'])) {
            $parsed_string .= '#'.$url['fragment'];
        }

        $new_url = $url;

        return $parsed_string;
    }
}