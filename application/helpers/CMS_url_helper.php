<?php
defined("BASEPATH") OR exit("No direct script access allowed");


if ( ! function_exists("assets_url"))
{
    /**
     * Create a assets URL
     *
     * @param	string	$uri
     * @param	string	$protocol
     * @return	string
     */
    function assets_url($uri = "", $protocol = null)
    {
        return base_url("/assets/" . trim($uri, "/"), $protocol);
    }
}

/**
 * Build the module URL address
 *
 * @param string $module
 * @param string $uri
 * @param null $protocol
 * @return string
 */
/*function module_url($module = "", $uri = "", $protocol = NULL)
{
    // Define the default module
    if ( !$module) $module = "dashboard";

    return base_url(sprintf("/%s/%s",
        $module, trim($uri, "/")), $protocol
    );
}*/

/**
 * Check that the module is active
 *
 * @param   string $module
 * @return  bool
 */
/*function is_active_module($module = "")
{
    $listUriSegments = explode(
        "/", uri_string()
    );

    // Get the actual module
    $actualModule = array_shift($listUriSegments);

    return ($actualModule == $module);
}*/

# end of file