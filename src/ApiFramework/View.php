<?php namespace ApiFramework;

/**
 * View class
 *
 * @package default
 * @author Mangolabs
 * @author Andrey Chekinev < andryxak4@gmail.com >
 * @author Andrii Kovtun < psagnat.af@gmail.com >
 */

class View extends Core {

    /**
     * Renders a template with optional data
     *
     * @param string $template Name of the template
     * @param array $values Array of values to replace in the template
     * @return string Rendered HTML
     */
    public function render ($view, $values = array()) {

        // Setup the template path
        $folder = $this->app->config('templates.path');
        $view = $folder . $view . '.php';

        // Check if the template exists
        if (!file_exists($view)) {
            throw new \Exception('Template not found: ' . $view, 404);
        }

        // Extract values and include template
        extract($values);
        ob_start();
        include $view;
        $html = ob_get_contents();
        ob_end_clean();

        // Return rendered HTML
        return $html;
    }

}
