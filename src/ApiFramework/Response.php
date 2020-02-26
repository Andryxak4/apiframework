<?php namespace ApiFramework;

/**
 * Response class
 *
 * @package default
 * @author Mangolabs
 * @author Andrey Chekinev < andryxak4@gmail.com >
 * @author Andrii Kovtun < psagnat.af@gmail.com >
 */

class Response extends Core
{

    /**
     * @var string Allowed response formats
     */
    private $allowedFormats = ['json', 'html'];

    /**
     * @var string Response format
     */
    private $format = 'json';

    /**
     * @var array Response headers
     */
    private $headers = [];

    /**
     * @var array Response cookies
     */
    private $cookies = [];

    /**
     * @var array Reponse extra data
     */
    private $extra = [];

    /**
     * @var array Error codes and messages
     */
    private $errors = [
        400 => 'HTTP/1.1 400 Bad Request',
        401 => 'HTTP/1.1 401 Unauthorized',
        403 => 'HTTP/1.1 403 Forbidden',
        404 => 'HTTP/1.1 404 Not Found',
        500 => 'HTTP/1.1 500 Internal Server Error',
    ];

    /**
     * Sets a header for the response
     *
     * @param string $header Header to set
     * @return object Response instance
     */
    public function header ($header) {
        $this->headers[] = $header;
        return $this;
    }

    /**
     * Sets a cookie for the response
     *
     * @param string $key Cookie key
     * @param string $value Cookie value
     * @return object Response instance
     */
    public function cookie ($key, $value) {
        $this->cookies[$key] = $value;
        return $this;
    }

    /**
     * Sets the format of the response
     *
     * @param string $format
     * @return object Response instance
     */
    public function format ($format) {
        if (in_array($format, $this->allowedFormats)) {
            $this->format = $format;
        }
        return $this;
    }


    /**
     * Sets extra data for the response
     *
     * @param array $extra Extra data
     * @return object Response instance
     */
    public function extra ($extra) {
        $this->extra = array_merge_recursive($this->extra, $extra);
        return $this;
    }


    /**
     * Echoes out the response
     *
     * @param array $response Response data
     * @return string HTTP Response
     */
    public function output ($response = []) {

        // Set format
        switch ($this->format) {
            case 'json':
                $response = array_merge_recursive($response, $this->extra);
                $this->header('Content-type: application/json; charset=utf-8');
                $response = json_encode($response);

                // Replace string numbers for integers
                $response = preg_replace('/(")([0-9]+)(")/is', '\\2', $response);
                break;
            case 'html':
                $this->header('Content-type: text/html; charset=utf-8');
                $response = $this->html($response);
                break;
        }

        // Set cookies
        foreach ($this->cookies as $key => $value) {
            setcookie($key, $value, time() + 3600, '/');
        }

        // Set headers
        foreach ($this->headers as $header) {
            header($header);
        }

        // Return response
        echo $response;
        exit;
    }

    /**
     * Sets an error header and echoes out the response
     *
     * @param int $code Error code
     * @param string $message Error message
     * @return string HTTP Response
     */
    public function error ($code, $message) {
        $response['success'] = false;
        if (!in_array($code, array_keys($this->errors))) {
            $code = 500;
        }
        $response['error']['code'] = $code;
        $this->header($this->errors[$code]);
        $response['error']['status'] = $this->errors[$code];
        $response['error']['message'] = $message;
        return $this->output($response);
    }

    /**
     * Transforms an array into an HTML list
     *
     * @param array $data Array of data to transform
     * @return string HTML list
     */
    private function html ($data) {
        if (!is_array($data)) {
            return $data;
        }
        $return = '';
        foreach ($data as $key => $value) {
            $return .= '<li>' . $key . ': ' . (is_array($value) ? $this->html($value) : $value) . '</li>';
        }
        return '<ul>' . $return . '</ul>';
    }

    /**
     * Groups elements with similar keys into an object
     * 
     * @param string $collection The collection to convert
     * @param string $keys Name of keys to objectify
     * @return array The resulting array
     */
    public function objectify ($collection, $indexes) {
        foreach ($collection as $k => $v) {
            if (is_array($v)) {
                $collection[$k] = self::objectify($v, $indexes);
            } else if (($key = current(explode('_', $k))) && in_array($key, $indexes) ) {
                $collection[$key][str_replace($key.'_', '', $k)] = $v;
                unset($collection[$k]);
            }
        }
        return $collection;
    }
}