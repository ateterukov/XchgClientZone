<?php
defined("BASEPATH") OR exit("No direct script access allowed");


if ( ! function_exists("assets_path"))
{
    /**
     * Create path to asset files
     *
     * @param	string	$path
     * @param	bool    $check_existence
     * @return	string
     */
    function assets_path($path = "", $check_existence = false)
    {
        return set_realpath(ASSETPATH .
            ltrim($path, DIRECTORY_SEPARATOR),
            $check_existence
        );
    }
}

# end of file