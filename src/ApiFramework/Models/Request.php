<?php

namespace ApiFramework\Models;

use ApiFramework\Core;

/**
 * Request class
 *
 * @package default
 * @author Mangolabs
 * @author Andrey Chekinev < andryxak4@gmail.com >
 * @author Andrii Kovtun < psagnat.af@gmail.com >
 */

class Request extends Core
{

    /**
     * @var array Reserved parameters
     */
    private $reserved = [
        'token',
        'locale',
        '_method'
    ];

    /**
     * @var array Received inputs
     */
    private $inputs = null;

    /**
     * @var array Additional inputs
     */
    private $additionalInputs = [];

    /**
     * Retrieves the request method
     *
     * @return string Method
     */
    public function method () {

        // Return the emulated method from GET
        $emulated = filter_input(INPUT_GET, '_method', FILTER_SANITIZE_STRING);

        // Return the emulated method from POST
        if (!$emulated) {
            $emulated = filter_input(INPUT_POST, '_method', FILTER_SANITIZE_STRING);
        }

        // Set the emulated method
        if ($this->app->config('request.emulate') && $emulated) {
            return $emulated;
        }

        // Or the real method
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Retrieves the current URL
     *
     * @return string URL
     */
    public function url () {
        $parts = explode('?', $_SERVER['REQUEST_URI']);
        return reset($parts);
    }

    /**
     * Retrieves the token
     *
     * @return mixed Token or false
     */
    public function token () {
        $inputs = $this->getInputs();
        $headers = $this->headers();

        // Read token from request custom header
        if (isset($headers['X-Auth-Token'])) {
            return $headers['X-Auth-Token'];
        }

        // Read token from request input
        if (isset($inputs['token'])) {
            return $inputs['token'];
        }

        // Otherwise, return false
        return false;
    }

    /**
     * Retrieves the locale
     *
     * @return mixed Locale or false
     */
    public function locale () {
        $inputs = $this->getInputs();
        return isset($inputs['locale']) ? $inputs['token'] : false;
    }

    /**
     * Retrieves all the headers
     *
     * @return array|boolean Headers array or false
     */
    public function headers () {
        return getallheaders();
    }

    /**
     * Returns a request input
     *
     * @param string $input Input key
     * @param string $default Default value to return
     * @return mixed Array of inputs, or single input if a key is specified
     */
    public function input ($input = null, $default = null) {

        // Get all inputs
        $inputs = array_merge($this->getInputs(), $this->additionalInputs);

        // Exclude reserved inputs
        foreach ($inputs as $key => $value) {
            if (in_array($key, $this->reserved)) {
                unset($inputs[$key]);
            }
        }

        // Return one input
        if ($input) {
            return isset($inputs[$input])? $inputs[$input] : $default;
        }

        // Or the complete array of inputs
        return $inputs;
    }

    /**
     * Tells if a request input exists
     *
     * @param string $input Input key
     * @return boolean Has the desired input or not
     */
    public function hasInput ($input) {
        $inputs = $this->input();
        return isset($inputs[$input]);
    }

    /**
     * Adds an additional input
     *
     * @param string $input Input key
     * @param string $value Input value
     * @return string Added input
     */
    public function addInput ($input, $value) {
        $this->additionalInputs[$input] = $value;
        return $input;
    }

    /**
     * Gets a request file
     *
     * @param string $input Input key
     * @return mixed File info, or false
     */
    public function file ($input = null) {

        // The input name must be defined
        if (!isset($input) || !isset($_FILES[$input])) {
            throw new \InvalidArgumentException('Invalid file', 400);
        }

        // Return the file info
        return $_FILES[$input];
    }

    /**
     * Tells if a request file exists
     *
     * @param string $input Input key
     * @return boolean Has the desired input or not
     */
    public function hasFile ($input = null) {

        // The input name must be defined
        if (!isset($input)) {
            throw new \InvalidArgumentException('Undefined input name', 400);
        }

        // Return the file info
        return isset($_FILES[$input]);
    }

    /**
     * Stores a request file
     *
     * @param string $input Input key
     * @param string $target Target path
     * @return boolean Success or fail of the store operation
     */
    public function storeFile ($input = null, $target) {

        // The input name and target path must be defined
        if (!isset($input) || !isset($target)) {
            throw new \InvalidArgumentException('Undefined input name or target', 400);
        }

        // Move the uploaded file
        $result = move_uploaded_file($_FILES[$input]['tmp_name'], $target);

        // Check if the file was stored
        if (!$result) {
            throw new \InvalidArgumentException('The file could not be stored', 500);
        }

        // Return the file info
        return $result;
    }

    /**
     * Stores the parameters from the request in the inputs array
     *
     * @return array Array of inputs
     */
    private function getInputs () {

        // If defined, return the inputs array
        if ($this->inputs) {
            return $this->inputs;
        }

        // Check for JSON data
        if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') === 0) {
            $input = file_get_contents('php://input');
            $decoded = json_decode($input, true);
            if ($decoded) {
                $this->inputs = $decoded;
                return $this->inputs;
            }
        }

        // Check by request method
        switch ($_SERVER['REQUEST_METHOD']) {

            // Get PUT inputs
            case 'PUT':
                parse_str(file_get_contents("php://input"), $this->inputs);
                break;

            // Get POST inputs
            case 'POST':
                $this->inputs = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
                break;

            // Get GET inputs
            default:
                $this->inputs = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
                break;
        }

        // Return array of inputs
        return $this->inputs ? : [];
    }

}