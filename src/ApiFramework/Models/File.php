<?php

namespace ApiFramework\Models;

use ApiFramework\Core;

/**
 * File class
 *
 * @package default
 * @author Mangolabs
 * @author Andrey Chekinev < andryxak4@gmail.com >
 * @author Andrii Kovtun < psagnat.af@gmail.com >
 */

class File extends Core
{

    /**
     * Check if a file exists
     *
     * @param $path File path
     * @return boolean True if the file exists, otherwise false
     */
    public function exists ($path) {
        return file_exists($path);
    }

    /**
     * Get the contents of a file
     *
     * @param $path File path
     * @return string File content
     */
    public function get ($path) {

        // Abort if the file does not exists
        if (!$path || !file_exists($path) || !is_readable($path)) {
            throw new \InvalidArgumentException('Invalid file path', 400);
        }

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
    public function put ($path, $contents) {

        // Abort if the path is not defined
        if (!$path) {
            throw new \InvalidArgumentException('Invalid file path', 400);
        }

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
    public function append ($path, $contents) {

        // Abort if the path is not defined
        if (!$path || !file_exists($path)) {
            throw new \InvalidArgumentException('Invalid file path', 400);
        }

        // Append the contents
        return file_put_contents($path, $contents, FILE_APPEND);
    }

    /**
     * Deletes a file
     *
     * @param $path File path
     * @return int|bool Number of bytes written, of false on failure
     */
    public function delete ($path) {

        // Abort if the path is not defined
        if (!$path || !file_exists($path)) {
            throw new \InvalidArgumentException('Invalid file path', 400);
        }

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
    public function copy ($path, $target) {

        // Abort if the path is not defined
        if (!$path || !file_exists($path)) {
            throw new \InvalidArgumentException('Invalid file path', 400);
        }

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
    public function move ($path, $target) {

        // Abort if the path is not defined
        if (!$path || !file_exists($path)) {
            throw new \InvalidArgumentException('Invalid file path', 400);
        }

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
    public function extension ($path) {

        // Abort if the path is not defined
        if (!$path || !file_exists($path)) {
            throw new \InvalidArgumentException('Invalid file path', 400);
        }

        // Return extension
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Gets a file size
     *
     * @param $path File path
     * @return boolean File size in bytes, or false on failure
     */
    public function size ($path) {

        // Abort if the path is not defined
        if (!$path || !file_exists($path)) {
            throw new \InvalidArgumentException('Invalid file path', 400);
        }

        // Return extension
        return filesize($path);
    }

}