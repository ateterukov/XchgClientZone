<?php
defined("APP_PATH") || exit("No direct script access allowed");



// Order lifetime
define("ORDER_LIFETIME", 259200);

// Connect the helper builder
require_once __DIR__ . "/core/ws_helper.php";


class Orders extends WS_Helper
{
    public function __construct()
    {
        parent::__construct();

        // Initialize the redis connection
        $this->redis = Connector::instantiate()
            ->initialize("redis");
    }

    /**
     * Get list of the order statuses
     * @return bool|array
     */
    public function getListStatuses()
    {
        // Call the CRM service
        $response = $this->crmService->callMethod(
            "getOrderStatuses"
        );

        return $this->process($response, "data");
    }

    /**
     * Get data of the user's orders
     *
     * @param array $filter
     * @return bool|array
     */
    public function findUserOrders($filter = array())
    {
        if ( !$this->session->isAuthorizedUser())
        {
            $this->errors[] = "Error: unauthorized user";
            return false;
        }

        // Collect request parameters
        $parameters = array(
            "limit"     => 15,
            "id"        => $this->session->getUserParam("id")
        );

        if ( !empty($filter["archive"]))
        {
            // Archive data only
            $parameters["statusArchive"] = true;
        }

        if ( !empty($filter["pair"]))
        {
            list($target, $source) = explode(
                "/", $filter["pair"]
            );

            // Add search condition
            $parameters["currencyFiat"] = $source;
            $parameters["currencyDigital"] = $target;
        }

        if ( !empty($filter["status"]))
        {
            // Add search condition
            $parameters["statusOrders"] = $filter["status"];
        }

        if ( !empty($filter["page"]))
        {
            // Add pagination option
            $parameters["page"] = intval($filter["page"]);
        }

        // Call the CRM service
        $response = $this->crmService->callMethod(
            "getPurchaseOrders", $parameters
        );

        return $this->process($response, "data");
    }

    /**
     * Get data of the user's order
     *
     * @param int $ID
     * @return bool|array
     */
    public function findUserOrder($ID = 0)
    {
        if ( !$this->session->isAuthorizedUser())
        {
            $this->errors[] = "Error: unauthorized user";
            return false;
        }

        // Collect request parameters
        $parameters = array(
            "id"        => $ID,
            "user_id"   => $this->session->getUserParam("id")
        );

        // Call the CRM service
        $response = $this->crmService->callMethod(
            "getPurchaseOrder", $parameters
        );

        return $this->process($response, "data");
    }

    /**
     * Confirm a new user's purchase order
     *
     * @param string $hash
     * @return bool|int
     */
    public function confirmUserOrder($hash = "")
    {
        if ( !$this->session->isAuthorizedUser())
        {
            $this->errors[] = "Error: unauthorized user";
            return false;
        }

        // Collect request parameters
        $parameters = array(
            "id"        => $hash,
            "user_id"   => $this->session->getUserParam("id")
        );

        // Call the CRM service
        $response = $this->crmService->callMethod(
            "confirmPurchaseOrder", $parameters
        );

        return $this->process($response, "orderId");
    }

    /**
     * Pay the user's purchase order
     *
     * @param string $hash
     * @return bool
     */
    public function payUserOrder($hash = "")
    {
        if ( !$this->session->isAuthorizedUser())
        {
            $this->errors[] = "Error: unauthorized user";
            return false;
        }

        // Call the CRM service
        $response = $this->crmService->callMethod(
            "payOrderDashCustomer", array("id" => $hash)
        );

        return $this->process($response, "status");
    }

    /**
     * Check that is allowed the operation
     *
     * @param int $status
     * @param string $operation
     * @return bool
     */
    public function isAllowedOperation($status = 0, $operation = "")
    {
        if ( !is_numeric($status))
        {
            return false;
        }

        // Deposits or pay
        if (in_array($operation, array("deposit", "pay")) &&
            $status == ORDER_CREATED)
        {
            return true;
        }

        // Withdrawals
        if (in_array($status, array(ORDER_PROCEED, ORDER_COMPLETED)) &&
            $operation == "withdraw")
        {
            return true;
        }

        return false;
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