<?php
namespace application\modules\questionnaire\models;


use application\core\APP_Model;

class Questionnaire extends APP_Model
{
    public function get($name = "")
    {
        if ( !$this->user->authorized())
        {
            $this->errors[] = "Error: unauthorized user";
            return false;
        }

        $this->load->library("CrmService");
        if ( ! $this->load->is_loaded("CrmService"))
        {
            //trigger_error("Error: failed to load the CrmService");
        }

        // Call the CRM service method
        $response = $this->crmService->call(
            "getQuestionnaire", array(          // getQNNaireData replace
                "user_id"   => $this->user->getId(), // customer_id replace
                "name"      => $name
            )
        );
    }






    public function __construct()
    {
        parent::__construct();
        self::initialize();
    }

    /**
     * Get the questionnaire
     * for the user
     * 
     * @param string $name
     * @return mixed
     */
    public function getQNNaire($name = "")
    {
        /*if ( !UserSession::getInstance()->isAuthorizedUser())
        {
            $this->errors[] = "Error: unauthorized user";
            return false;
        }*/

        $response = $this->connectToAPIService(
            "getQNNaireData", false, array(
                "name"              => $name,
                "customer_id"       => UserSession::getInstance()
                    ->getUserParam("id")
            )
        );


        if ( !is_array($response) ||
             !array_key_exists("status", $response))
        {
            $this->errors[] = "Error: server returned incorrect response";
        }
        elseif ($response["status"] !== "success")
        {
            if (empty($response["errors"]))
            {
                $this->errors[] = "Error: server returned an empty error message";
            }
            elseif (is_array($response["errors"]))
            {
                $this->errors = array_merge(
                    $this->errors, $response["errors"]
                );
            }
            else
            {
                $this->errors[] = $response["errors"];
            }
        }

        if ( !empty($this->errors)) return false;
    	return $response["data_set"];
    }

    /**
     * Accept questionnaire results of the user
     *
     * @param array $listInVars
     * @return bool
     */
    public function acceptUserResults($listInVars = array())
    {
        if ( !UserSession::getInstance()->isAuthorizedUser())
        {
            $this->errors[] = "Error: unauthorized user";
            return false;
        }

        $this->APPHelpers["validator"]->addAttribute("name", "qnn identifier", "required");
        if ( !$this->APPHelpers["validator"]->validation($listInVars))
        {
            $this->errors = array_merge(
                $this->errors, $this->APPHelpers["validator"]->getErrors()
            );

            return false;
        }

        $thisQNNResults = array(
            "name"              => $listInVars["name"],
            "qnnaire_results"   => $listInVars,
            "customer_id"       => UserSession::getInstance()
                ->getUserParam("id")
        );

        $response = $this->connectToAPIService(
            "acceptQNNaireResults", false, $thisQNNResults
        );


        if ( !is_array($response) ||
             !array_key_exists("status", $response))
        {
            $this->errors[] = "Error: server returned incorrect response";
        }
        elseif ($response["status"] !== "success")
        {
            if (empty($response["errors"]))
            {
                $this->errors[] = "Error: server returned empty error message";
            }
            elseif (is_array($response["errors"]))
            {
                $this->errors = array_merge(
                    $this->errors, $response["errors"]
                );
            }
            else
            {
                $this->errors[] = $response["errors"];
            }
        }

        if ( !empty($this->errors)) return false;
    	return true;
    }

    /**
     * Get list of the errors
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }


    // ==================================== PRIVATE METHODS ==================================== //

    /**
     * Initialize
     * @return void
     */
    private function initialize()
    {
        $this->APPUrl = rtrim(BASE_URI, "/");
        $this->APPRootPath = $_SERVER["DOCUMENT_ROOT"] . rtrim(
            BASE_DIR, DIRECTORY_SEPARATOR
        );

        // Define directory of all helpers
        $this->APPHelpersDirPath = $this->APPRootPath . $this->APPHelpersDirPath;

        // Initialize the validator
        $this->APPHelpers["validator"] = $this->initializeHelper(
            "validator"
        );
    }

    /**
     * Initialize the helper class
     *
     * @param string $className
     * @return mixed
     */
    private function initializeHelper($className = "")
    {
        if ( !class_exists($className, false))
        {
            if ( !file_exists($this->APPHelpersDirPath . "/{$className}.php"))
            {
                return false;
            }

            require_once $this->APPHelpersDirPath . "/{$className}.php";
        }

        if ( !class_exists($className, false))
        {
            return false;
        }

        $CHelperClass = new $className();
        return $CHelperClass;
    }
}

# end of file