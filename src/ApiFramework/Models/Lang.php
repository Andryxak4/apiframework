<?php

namespace ApiFramework\Models;

use ApiFramework\Core;

/**
 * Lang class
 *
 * @package default
 * @author Mangolabs
 * @author Andrey Chekinev < andryxak4@gmail.com >
 * @author Andrii Kovtun < psagnat.af@gmail.com >
 */

class Lang extends Core
{

    /**
     * @var string Language key
     */
    protected $lang = null;

    /**
     * @var array Languages array
     */
    protected $languages = [];


    /**
     * Constructor
     * 
     * @param App $app Application instance
     */
    public function __construct (App $app) {
        parent::__construct($app);
        $this->lang = $this->app->config('lang.default');
    }


    /**
     * Loads a language
     *
     * @param string $lang Language key
     * @return boolean Success or fail of language load
     */
    public function load ($lang) {

        // Return if the language is already loaded
        if (isset($this->languages[$lang])) {
            return true;
        }

        // Add the terms to the internal dictionary
        $path = $this->app->config('lang.folder')  . $lang . '.json';
        $terms = $this->app->file->get($path);
        return $this->languages[$lang] = json_decode($terms, true);
    }


    /**
     * Gets a translated term
     *
     * @param string $term Term to be translated
     * @param string $lang Language key
     * @return string Translated term
     */
    public function get ($term, $lang = null) {

        // If we don't receive a language key, use the default
        $lang = isset($lang)? $lang : $this->lang;

        // Load the language terms
        if (!isset($this->languages[$lang])) {
            $this->load($lang);
        }

        // Return the translated term
        return isset($this->languages[$lang][$term])? $this->languages[$lang][$term] : $term;
    }


    /**
     * Sets the default language
     * 
     * @param string $lang Language key
     * @return mixed Language key setted, or false if failed
     */
    public function locale ($lang) {
        return $this->lang = $lang;
    }
}
