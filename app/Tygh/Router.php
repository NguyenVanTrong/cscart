<?php

/*
 * Based on AltoRouter class by Danny van Kooten
 * Copyright (c) 2012-2013 Danny van Kooten hi@dannyvankooten.com
 */

namespace Tygh;

class Router {

    protected $req;
    protected $routes = array();
    protected $matchTypes = array(
        'i'  => '[0-9]++',
        'a'  => '[0-9A-Za-z]++',
        'h'  => '[0-9A-Fa-f]++',
        '*'  => '.+?',
        '**' => '.++',
        ''   => '[^/\.]++'
    );

    /**
     * Create router in one call from config.
     *
     * @param array $req request vars
     * @param array $matchTypes
     */
    public function __construct($req = array()) 
    {
            $this->req = $req;
    }

    /**
     * Add multiple routes at once from array in the following format:
     *
     *   $routes = array(
     *      array($route, $target)
     *   );
     *
     * @param array $routes
     * @return void
     * @author Koen Punt
     */
    public function addRoutes($routes)
    {
        foreach($routes as $route => $target) {
            $this->map($route, $target);
        }
    }

    /**
     * Add named match types. It uses array_merge so keys can be overwritten.
     *
     * @param array $matchTypes The key is the name and the value is the regex.
     */
    public function addMatchTypes($matchTypes) 
    {
        $this->matchTypes = array_merge($this->matchTypes, $matchTypes);
    }

    /**
     * Map a route to a target
     *
     * @param string $route The route regex, custom regex must start with an @. You can use multiple pre-set regex filters, like [i:id]
     * @param mixed $target The target where this route should point to. Can be anything.
     */
    public function map($route, $target)
    {
        $this->routes[] = array($route, $target);

        return;
    }

    /**
     * Match a given Request Url against stored routes
     * @param string $requestUrl
     * @return array|boolean Array with route information on success, false on failure (no match).
     */
    public function match($requestUrl) 
    {
        $params = array();
        $match = false;

        // Strip query string (?a=b) from Request Url
        if (($strpos = strpos($requestUrl, '?')) !== false) {
            $requestUrl = substr($requestUrl, 0, $strpos);
        }

        foreach($this->routes as $handler) {
            list($_route, $target) = $handler;

            // Check for a wildcard (matches all)
            if ($_route === '*') {
                $match = true;
            } elseif (isset($_route[0]) && $_route[0] === '@') {
                $pattern = '`' . substr($_route, 1) . '`u';
                $match = preg_match($pattern, $requestUrl, $params);
            } else {
                $route = null;
                $regex = false;
                $j = 0;
                $n = isset($_route[0]) ? $_route[0] : null;
                $i = 0;

                // Find the longest non-regex substring and match it against the URI
                while (true) {
                    if (!isset($_route[$i])) {
                        break;
                    } elseif (false === $regex) {
                        $c = $n;
                        $regex = $c === '[' || $c === '(' || $c === '.';
                        if (false === $regex && false !== isset($_route[$i+1])) {
                            $n = $_route[$i + 1];
                            $regex = $n === '?' || $n === '+' || $n === '*' || $n === '{';
                        }
                        if (false === $regex && $c !== '/' && (!isset($requestUrl[$j]) || $c !== $requestUrl[$j])) {
                            continue 2;
                        }
                        $j++;
                    }
                    $route .= $_route[$i++];
                }

                $regex = $this->compileRoute($route);
                $match = preg_match($regex, $requestUrl, $params);
            }

            if (($match == true || $match > 0)) {
                if ($params) {
                    foreach($params as $key => $value) {
                        if(is_numeric($key)) {
                            unset($params[$key]);
                        }
                    }
                }

                return fn_array_merge($this->req, $target, $params);
            }
        }
        return false;
    }

    /**
     * Compile the regex for a given route (EXPENSIVE)
     */
    private function compileRoute($route) 
    {
        if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER)) {

            $matchTypes = $this->matchTypes;
            foreach($matches as $match) {
                list($block, $pre, $type, $param, $optional) = $match;

                if (isset($matchTypes[$type])) {
                    $type = $matchTypes[$type];
                }
                if ($pre === '.') {
                    $pre = '\.';
                }

                //Older versions of PCRE require the 'P' in (?P<named>)
                $pattern = '(?:'
                    . ($pre !== '' ? $pre : null)
                    . '('
                    . ($param !== '' ? "?P<$param>" : null)
                    . $type
                    . '))'
                    . ($optional !== '' ? '?' : null);

                $route = str_replace($block, $pattern, $route);
            }
        }

        return "`^$route$`u";
    }
}
