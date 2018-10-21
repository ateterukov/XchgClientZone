<?php
namespace application\core;


/**
 * Class API_Model
 *
 * @property APPUser $user
 * @property \CI_Input $input
 * @property \CI_Loader $load
 */
class API_Model extends \CI_Model
{
    /**
     * API model constructor
     */
    public function __construct()
    {
        parent::__construct();

        // Initialize the user handler
        $this->user = new APPUser();
    }

    /**
     * Parse results of the request
     *
     * @param array $results
     * @param string $attribute
     * @return mixed
     */
    protected function parse($results = array(), $attribute = "")
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
                // Add error on incorrect response
                $this->errors[] = "Error: server returned an empty error";
            }
            else
            {
                // Add error to the user
                $this->errors[] = "Error: operation failed";
            }
        }

        if ( !empty($this->errors))
        {
            return false;
        }

        if ( !empty($attribute))
        {
            if ( !key_exists($attribute, $results))
            {
                return false;
            }

            return $results[$attribute];
        }

        return $results;
    }
}

# end of file