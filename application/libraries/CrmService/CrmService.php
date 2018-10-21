<?php

/**
 * Created by PhpStorm.
 * User: Саша
 * Date: 08.10.2018
 * Time: 15:56
 */
class CrmService
{
    private $provider = false;


    private $action = "";


    public function __construct($handler = "")
    {
        $this->provider = new $handler();
    }

    /*public function action($action = "")
    {
        if ( ! empty($action) &&
            is_string($action))
        {
            // Set the requested action
            $this->action = $action;
        }

        return $this;
    }*/

    /*public function method($method = "")
    {
        if ( ! empty($method) &&
            is_string($method))
        {
            // Set the request method
            $this->method = strtoupper($method);
        }

        return $this;
    }*/

    /*public function argums($argums = array())
    {
        if (is_array($argums) &&
            count($argums))
        {
            $this->argums = $argums;
        }

        return $this;
    }*/

    public function parse($response = "", $format = "")
    {

    }

    /**
     * Call service method
     *
     * @param string $method
     * @param array $argums
     * @param array $options
     * @return mixed
     */
    public function call($method = "", $argums = array(), $options = array())
    {
        // Build the request url address
        if ( !$url = $this->getUrl($method))
        {
            trigger_error("", E_USER_ERROR);
        }

        // Check if the request url is valid
        if ( ! $this->isValidUrl($url))
        {
            trigger_error("", E_USER_ERROR);
        }

        // Send request to the CRM service
        return $this->provider->request->create($url)
            ->with_argums($argums)
            ->with_options($options)
            ->send();
    }
}

# end of file