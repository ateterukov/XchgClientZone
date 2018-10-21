<?php
defined("APP_PATH") || exit("No direct script access allowed");

// Connect the helper builder
require_once __DIR__ . "/core/ws_helper.php";


class Account extends WS_Helper
{
    private $connInfo = array();


    public function __construct()
    {
        parent::__construct();

        $this->connInfo = array(
            "ip_address"        => $this->getUserIP(),
            "browser_data"      => $this->getUserAgent(),
            "platform_url"      => $this->getWebSiteUrl()
        );
    }

    /**
     * Get user balance sheet
     * @return bool|array
     */
    public function getUserInfo()
    {
        if ( !$this->session->isAuthorizedUser())
        {
            $this->errors[] = "Error: unauthorized user";
            return false;
        }

        // Collect request parameters
        $parameters = array_merge($this->connInfo, array(
            "user_id"   => $this->session->getUserParam("id")
        ));

        // Call the CRM service
        $response = $this->crmService->callMethod(
            "getAccountData", false, $parameters
        );

        return $this->process($response, "data_set");
    }


    // ====================================== PRIVATE METHODS ====================================== //

    /**
     * Process results of the request
     *
     * @param array $results
     * @param string $attribute
     * @return mixed
     */
    private function process($results = array(), $attribute = "")
    {
        if ( !is_array($results) ||
            !key_exists("status", $results))
        {
            if ( !$this->isEnabledDebugMode())
            {
                // Add error to the user
                $this->errors[] = "Error: operation failed";
            }
            else
            {
                // Collect connection error
                $this->attachErrors($this->crmService->collectErrors());

                // Add error on incorrect server response
                $this->errors[] = "Error: server returned incorrect response";
            }
        }
        elseif ( !$results["status"])
        {
            if ( !empty($results["message"]))
            {
                // Add error to the user
                $this->errors[] = sprintf("Error: %s", $results["message"]);
            }
            elseif ($this->isEnabledDebugMode())
            {
                // Add error on incorrect server response
                $this->errors[] = "Error: server returned an empty error message";
            }
        }

        if ( !empty($this->errors))
        {
            return false;
        }

        if ( !empty($attribute) &&
            key_exists($attribute, $results))
        {
            return $results[$attribute];
        }

        return $results;
    }
}

# end of file