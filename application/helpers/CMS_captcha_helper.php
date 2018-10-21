<?php
defined("BASEPATH") OR exit("No direct script access allowed");


if ( ! function_exists("show_captcha"))
{
    /**
     * Show CAPTCHA image
     *
     * @param array $config
     * @return bool|string
     */
    function show_captcha($config = array())
    {
        // Initialize CodeIgniter instance
        $CI = &get_instance();

        // Check if the library is loaded
        if ( ! $CI->load->is_loaded("captcha"))
        {
            // Load the additional library
            $CI->load->library("captcha");
        }

        // Return the image
        return $CI->captcha->create(
            $config
        );
    }
}

# end of file