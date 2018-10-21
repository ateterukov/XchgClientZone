<?php
defined("BASEPATH") OR exit("No direct script access allowed");


class CAPTCHA
{
    // CodeIgniter instance
    protected $CI = false;

    // CAPTCHA configuration
    protected $config = array(
        "img_url"           => "",
        "expiration"        => "",
        "img_path"          => ""
    );


    /**
     * CAPTCHA constructor
     * @return CAPTCHA
     */
    public function __construct()
    {
        // Initialize the CodeIgniter instance
        $this->CI = &get_instance();

        // Load the session library
        $this->CI->load->library("session");

        // Initialize default image url
        $this->config["img_url"] = assets_url(
            $this->CI->config->item("captcha_storage")
        );

        // Initialize default image path
        $this->config["img_path"] = assets_path(
            $this->CI->config->item("captcha_storage")
        );

        // Initialize default image lifetime
        $this->config["expiration"] = $this->CI
            ->config->item("captcha_lifetime");
    }

    /**
     * Create CAPTCHA image
     *
     * @param array $config
     * @return mixed
     */
    public function create($config = array())
    {
        // Load the CAPTCHA helper
        $this->CI->load->helper("captcha");

        // Merge CAPTCHA config
        $this->config = array_merge(
            $this->config, array_filter($config)
        );

        // Create CAPTCHA image
        $captcha = create_captcha(
            $this->config
        );

        // Add CAPTCHA control word to session
        $this->CI->session->set_tempdata("captcha_word",
            $captcha["word"], $captcha["time"]
        );

        return $captcha["image"];
    }

    /**
     * Check if the CAPTCHA code
     * is valid
     *
     * @param string $code
     * @return bool
     */
    public function isValidCode($code = ""): bool
    {
        if ( ! $code) return false;

        // Get the CAPTCHA word for check
        $word = $this->CI->session->tempdata(
            "captcha_word"
        );

        // Check code with control CAPTCHA word
        if ( ! $word || $code !== $word)
        {
            return false;
        }

        return true;
    }
}

# end of file