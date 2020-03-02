<?php

namespace ApiFramework\Models;

use ApiFramework\Core;

/**
 * Http class
 *
 * @package default
 * @author Mangolabs
 * @author Andrey Chekinev < andryxak4@gmail.com >
 * @author Andrii Kovtun < psagnat.af@gmail.com >
 */

class Http extends Core
{

    /**
     * @var array Fixed parameters array
     */
    private $params = [];

    /**
     * Sets fixed parameters to be sent in all calls
     *
     * @param array $params Parameters
     * @return object Http instance
     */
    public function setupParams ($params = []) {
        if (is_array($params)) {
            $this->params = array_merge($this->params, $params);
        }
        return $this;
    }

    /**
     * Makes an HTTP call
     *
     * @param string $url URL to request
     * @param string $method HTTP method
     * @param array $params Parameters
     * @return object Http instance
     */
    public function call ($url, $method = 'GET', $params = []) {

        // Set call parameters
        $params = array_merge($this->params, is_array($params)? $params : []);

        // Setup parameters
        $content = '';
        $queryString = http_build_query($params);
        if ($method === 'GET') {
            $url = $url . '?' . $queryString;
        } else {
            $content = $queryString;
        }

        // Make call
        $result = file_get_contents($url, false, stream_context_create([
            'http' => [
                'method'  => $method,
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' => $content
            ]
        ]));

        // Return result
        return $result;
    }

}