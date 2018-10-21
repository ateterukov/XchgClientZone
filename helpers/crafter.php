<?php
// Order statuses
define("ORDER_CREATED", 0);
define("ORDER_CONFIRMED", 1);
define("ORDER_STARTED", 2);
define("ORDER_FINISHED", 3);
define("ORDER_CANCELED", 100);

// Order payment statuses
define("ORDER_PAID", 1);
define("ORDER_NOT_PAID", 0);
define("ORDER_PAY_EXPIRED", 2);

// Connect the helper builder
require_once __DIR__ . "/core/ws_helper.php";


class Crafter extends WS_Helper
{
    // Settlement unit
    private $fiscalUnit = "USD";


    public function __construct()
    {
        parent::__construct();

        // Load finance utility
        if ( !$this->loadUtility("/classes/finance"))
        {
            die("Error: failed to load the system utility");
        }

        // Load finance utility
        if ( !$this->loadUtility("/classes/cart"))
        {
            die("Error: failed to load the system utility");
        }

        // Set the default fiscal unit
        $this->fiscalUnit = $this->finance->getFiscalUnit();
    }

    /**
     * Get list of the hardware options
     * @return mixed
     */
    public function getListHardwareOptions()
    {
        // Call the CRM service
        $response = $this->crmService->callMethod(
            "getListHardwareOptions"
        );

        return $this->process($response, "options");
    }

    /**
     * Collect list of the best hardware
     *
     * @param array $filter
     * @return mixed
     */
    public function collectListBestHardware($filter = array())
    {
        // Request parameters
        $parameters = array();

        // Append filter values
        if (is_array($filter) && count($filter))
        {
            // Merge request parameters
            $parameters = array_merge(
                $parameters, $filter
            );
        }

        // Set the settlement currency
        $parameters["currency"] = $this->fiscalUnit;

        // Call the CRM service
        $response = $this->crmService->callMethod(
            "collectListBestHardware", $parameters
        );

        return $this->process($response, "machines");
    }

    /**
     * Collect list of the available hardware
     *
     * @param array $filter
     * @return mixed
     */
    public function collectListHardware($filter = array())
    {
        // Set the fiscal unit
        $filter["currency"] = $this->finance->getFiscalUnit();

        // Call the CRM service
        $response = $this->crmService->callMethod(
            "collectListHardware", $filter
        );

        return $this->process($response);
    }

    /**
     * Collect information
     * about available hardware
     *
     * @param array $argums
     * @return mixed
     */
    public function collectHardwareInfo($argums = array())
    {
        // Call the CRM service
        $response = $this->crmService->callMethod(
            "collectHardwareInfo", $argums
        );

        return $this->process($response, "hardware");
    }

    /**
     * Get list of hardware in the cart
     * @return bool|array
     */
    public function getListHardwareInCart()
    {
        // Count the number of items
        if ( !$this->cart->size()) return false;

        // Get items from the cart
        $dataset = $this->cart->collect();

        // Call the CRM service
        $response = $this->crmService->callMethod(
            "isValidHardwareList", false, array(
                "list"      => $dataset,
                "currency"  => $this->fiscalUnit
            )
        );

        // Parse hardware list
        return $this->process($response, "hardware");
    }

    /**
     * Add hardware to the cart
     *
     * @param array $argums
     * @return bool
     */
    public function addHardwareToCart($argums = array())
    {
        // Request parameters
        $parameters = array();

        // Append filter values
        if (is_array($argums) && count($argums))
        {
            // Merge request parameters
            $parameters = array_merge(
                $parameters, $argums
            );
        }

        // Call the CRM service
        $response = $this->crmService->callMethod(
            "isAvailableHardware", $parameters
        );

        // Parse hardware attributes
        if ($hardware = $this->process($response, "hardware"))
        {
            // Add hardware to cart
            return $this->cart->append($hardware["details"],
                $hardware["lifetime"]
            );
        }

        return false;
    }

    /**
     * Find orders
     *
     * @param array $argums
     * @return mixed
     */
    public function findOrders($argums = array())
    {
        if ( !$this->session->isAuthorizedUser())
        {
            $this->errors[] = "Error: unauthorized user";
            return false;
        }

        // Request parameters
        $parameters = $this->input->fetch(array(
            "hdw_type"          => "",
            "hdw_currency"      => "",
            "start_date"        => "",
            "finish_date"       => ""
        ), $argums);

        // Get data of the actual user
        $actualUser = $this->session->getUserData();

        // Set the actual user
        $parameters["user_id"] = $actualUser["id"];

        // Call the CRM service
        $response = $this->crmService->callMethod(
            "findCraftOrders", $parameters
        );

        return $this->process($response, "orders");
    }

    /**
     * Add a new craft order
     *
     * @param array $argums
     * @return bool
     */
    public function addNewOrder($argums = array())
    {
        // Request parameters
        $parameters = array();

        // Append filter values
        if (is_array($argums) && count($argums))
        {
            // Merge request parameters
            $parameters = array_merge(
                $parameters, $argums
            );
        }

        // Get data of the actual user
        $actualUser = $this->session->getUserData();

        // Set the actual user
        $parameters["user_id"] = $actualUser["id"];

        // Call the CRM service
        $response = $this->crmService->callMethod(
            "addCraftOrder", $parameters
        );

        return $this->process($response);
    }

    /**
     * Add list of the craft orders
     * @return bool|mixed
     */
    public function addOrderList()
    {
        // Check the user's session
        if ( !$this->session->isAuthorizedUser())
        {
            $this->errors[] = "Error: unauthorized user";
        }
        elseif ( !$this->cart->size())
        {
            $this->errors[] = "Error: cart is empty";
        }

        if ( !empty($this->errors))
        {
            return false;
        }

        // Get data of the user
        $actualUser = $this->session->getUserData();

        // Get items from the cart
        $collection = $this->cart->collect();

        // Request parameters
        $parameters = array(
            "user_id"       => $actualUser["id"],
            "order_items"   => $collection,
        );

        // Call the CRM service
        $response = $this->crmService->callMethod(
            "addCraftOrderList", false, $parameters
        );

        // Parse statement
        $statement = $this->process($response, "statement");
        if ( !$statement)
        {
            return false;
        }

        // Clear the cart
        if ($statement["status"] === "success")
        {
            $this->cart->clear();
        }

        return $statement;
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

                // Add error on incorrect response
                $this->errors[] = "Error: server returned incorrect response";
            }
        }
        elseif ($results["status"] !== "success")
        {
            if ( !empty($results["errors"]))
            {
                if (is_array($results["errors"]))
                {
                    // Merge error list to the user
                    $this->errors = array_merge(
                        $this->errors, $results["errors"]
                    );
                }
                else
                {
                    // Add error to the user
                    $this->errors[] = sprintf(
                        "Error: %s", $results["message"]
                    );
                }
            }
            elseif ($this->isEnabledDebugMode())
            {
                $this->errors[] = "Error: server returned an empty error";
            }
            else
            {
                // Add error to the user
                $this->errors[] = "Error: operation failed";
            }
        }

        // Output error
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