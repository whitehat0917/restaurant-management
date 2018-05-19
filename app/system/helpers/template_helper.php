<?php

/**
 * Template helper functions
 */

if (!function_exists('page')) {
    /**
     * Get the page content
     * @return string
     */
    function page()
    {
        return controller()->renderPage();
    }
}

if (!function_exists('partial')) {
    /**
     * Load a partial view file
     *
     * @param string $partial
     * @param array $data
     *
     * @return string
     */
    function partial($partial = '', array $data = [])
    {
        return controller()->renderPartial($partial, $data);
    }
}

if (!function_exists('has_components')) {
    /**
     * Check if Partial Area has rendered components
     *
     * @param string $partialArea
     *
     * @return string
     */
    function has_components($partialArea = '')
    {
        return controller()->hasPartialComponents($partialArea);
    }
}

if (!function_exists('component')) {
    /**
     * Check if Partial Area has rendered components
     *
     * @param string $component
     * @param $params
     *
     * @return string
     */
    function component($component = '', array $params = [])
    {
        return controller()->renderComponent($component, $params);
    }
}

if (!function_exists('get_title')) {
    /**
     * Get page title html tag
     * @return    string
     */
    function get_title()
    {
        return controller()->getPage()->title;
    }
}
