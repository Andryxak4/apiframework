<?php

namespace ApiFramework\Models;

use ApiFramework\Core;

/**
 * Storage class
 *
 * @package default
 * @author Mangolabs
 * @author Andrey Chekinev < andryxak4@gmail.com >
 * @author Andrii Kovtun < psagnat.af@gmail.com >
 */

class Storage extends Core
{

    private $path = '';
    private $extension = '';

    /**
     * Check if a file exists
     *
     * @param $path File path
     * @return boolean True if the file exists, otherwise false
     */
    public function exists ($path) {
        
        // Set path in storage
        $path = $this->app->config('app.storage') . $path;
        
        return file_exists($path);
    }

    /**
     * Get the contents of a file
     *
     * @param $path File path
     * @return string File content
     */
    public function get ($path = '') {

        // Set path 
        $path = $this->setPath($path);

        // Get the file contents
        $contents = file_get_contents($path);

        // Return the contents
        return $contents;
    }

    /**
     * Save contents to a file
     *
     * @param $path File path
     * @param $path Contents
     * @return int|bol Number of bytes written, of false on failure
     */
    public function put ($path = '', $contents) {

        // Set path 
        $path = $this->setPath($path);

        // Save the contents to the file
        return file_put_contents($path, $contents);
    }

    /**
     * Appends contents to a file
     *
     * @param $path File path
     * @param $path Contents
     * @return int|bool Number of bytes written, of false on failure
     */
    public function append ($path = '', $contents) {

        // Set path 
        $path = $this->setPath($path);

        // Append the contents
        return file_put_contents($path, $contents, FILE_APPEND);
    }

    /**
     * Deletes a file
     *
     * @param $path File path
     * @return int|bool Number of bytes written, of false on failure
     */
    public function delete ($path = '') {

        // Set path 
        $path = $this->setPath($path);

        // Destroy the file
        return unlink($path);
    }

    /**
     * Copies a file
     *
     * @param $path File path
     * @param $target Target path
     * @return boolean Success or failure
     */
    public function copy ($path = '', $target) {
        
        // Set path 
        $path = $this->setPath($path);

        // Abort if the target is not defined
        if (!$target) {
            throw new \InvalidArgumentException('Invalid file target', 400);
        }

        // Copy the file to the new location
        return copy($path, $target);
    }

    /**
     * Moves a file
     *
     * @param $path File path
     * @param $target Target path
     * @return boolean Success or failure
     */
    public function move ($path = '', $target) {

        // Set path 
        $path = $this->setPath($path);

        // Abort if the target is not defined
        if (!$target) {
            throw new \InvalidArgumentException('Invalid file target', 400);
        }

        // Move the file to the new location
        return rename($path, $target);
    }

    /**
     * Gets a file extension
     *
     * @param $path File path
     * @return string File extension
     */
    public function extension ($path = '') {
        
        // Set path 
        $path = $this->setPath($path);

        // Set $this->extension
        $this->extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        // Return extension
        return $this->extension;
    }

    /**
     * Gets a file size
     *
     * @param $path File path
     * @return boolean File size in bytes, or false on failure
     */
    public function size ($path = '') {

        // Set path 
        $path = $this->setPath($path);

        // Return extension
        return filesize($path);
    }

    /**
     * Get data from file
     * 
     * @param $path File path
     * @return array||string||integer Set params to 
     */
    public function obtain ($path = '') {
        
        // Set path 
        $this->setPath($path);

        // Get extension
        $this->extension();

        // Return data
        return $this->getContents();
    }

    /**
     * Set path
     * 
     * @return string Path in storage
     */
    private function setPath ($path = '') {

        // Define path
        $path = $this->path != '' ? $this->path : $this->app->config('app.storage') . $path;

        // Set $this->path if it is empty
        if($this->path == '') {
            $this->path = $path;
        }

        // Abort if the path is not defined
        $this->checkPath($path);

        // Return path in storage
        return $path;
    }

    /**
     * Check if file not exists
     * 
     * @param string Path to file
     * @return boolean If file exists
     */
    private function checkPath ($path = '') {

        // Abort if the path is not defined
        if ($path == '' && !file_exists($path)) {
            throw new \InvalidArgumentException('Invalid file path', 400);
        }

        // Return if exists
        return true;
    }

    /**
     * Get data from supported formats
     * 
     * @return array||string||integer||object Return valid data
     */
    private function getContents () {

        // Define method
        $method = 'get'.ucwords($this->extension);

        // Check if extension supported
        if ($this->extension == '' && !method_exists($this, $method)) {
            throw new \InvalidArgumentException('Extension not supported', 400);
        };

        // Get data
        $data = $this->$method();

        // Return data
        return $data;
    }

    /**
     * Get data from php file
     * 
     * @return array||string||integer Of php file 
     */
    private function getPhp () {

        // Get data from file or die
        try {
            $data = include_once $this->path;
        } catch(\Throwable $e) {
            throw new \InvalidArgumentException('Error in: ' . $this->path, 400);
        }
        
        // Return data
        return $data;
    }

    /**
     * Get data from JSON file
     * 
     * @return object Of JSON 
     */
    private function getJson () {

        // Get content from file or die
        $content = $this->get($this->path);

        // Get data from content
        $data = json_decode($content);

        // Abort if the include error
        if (json_last_error()) {
            throw new \InvalidArgumentException(json_last_error_msg() . ": " . $this->path, 400);
        }

        // Return data
        return $data;
    }

    /**
     * Get data from CSV file
     * 
     * @return array Of CSV 
     */
    private function getCsv () {

        // Define data
        $data = [];

        // Convert csv to array $data
        if (($handle = fopen($this->path, "r")) !== FALSE) {
            while (($line = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $data[] = $line;
            }
            fclose($handle);
        }

        // Return data
        return $data;
    }
}