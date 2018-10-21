<?php
if ( !defined("DIRECT")) die("Direct access not permitted");

define("PAMM_GROUP_STARTED", 1);
define("PAMM_GROUP_FINISHED", 0);


require_once dirname(__DIR__) . "/core/apiservice.php";
require_once dirname(__DIR__) . "/helpers/validator.php";

require_once dirname(__DIR__) . "/usersession.php";


class PAMM extends APIService
{
    private $appletMode = "cfd";

    private $listAPIMethods = array();
    private $listActiveModes = array();

    private $listSupportedModes = array(
        "cfd"           => "CFD",
        "futures"       => "Futures",
        "margin"        => "Margin"
    );


    public function __construct()
    {
        parent::__construct();
        self::initialize();
    }

    /**
     * Get the applet mode
     * @return string
     */
    public function getAppletMode()
    {
        return $this->appletMode;
    }

    /**
     * Switch the applet mode
     * To another position
     * 
     * @param string $mode
     * @return void
     */
    public function switchAppletMode($mode = "")
    {
        if ( !empty($mode) &&
            array_key_exists($mode, $this->listSupportedModes))
        {
            $this->appletMode = $mode;
            $this->initialize();
        }
    }

    /**
     * Get list of the modes
     * @return array
     */
    public function getListModes()
    {
        return $this->listActiveModes;
    }

    /**
     * Check that is available the mode
     * @return bool
     */
    public function isAvailableMode()
    {
        $response = $this->connectToAPIService(
            $this->getAPIMethod("isAvailableModule")
        );


        if ( !array_key_exists("status", $response))
        {
            $this->errors[] = "Error: server returned incorrect response";
        }
        elseif ( !$response["status"])
        {
            $this->errors[] = "Error: PAMM type isn't available";
        }

        if ( !empty($this->errors)) return false;
    	return true;
    }

    /**
     * Get list of the allowed groups
     * @return bool|array
     */
    public function getListAllowedGroups()
    {
        if ( !$authUserData = UserSession::getInstance()->getUserData())
        {
            $this->errors[] = "Error: unauthorized user";
            return false;
        }

        $response = $this->connectToAPIService(
            $this->getAPIMethod("getAllowedGroups"), array(
                "client_id"         => $authUserData["id"]
            )
        );


        if ( !is_array($response) || !array_key_exists("status", $response))
        {
            $this->errors[] = "Error: server returned incorrect response";
        }
        elseif ( !$response["status"])
        {
            if ( !empty($response["message"]))
            {
                $this->errors[] = "Error: {$response["message"]}";
            }
            else
            {
                $this->errors[] = "Error: server returned an empty error message";
            }
        }

        if ( !empty($this->errors)) return false;

        return $response["data"];
    }

    /**
     * Associate a user with a group
     * 
     * @param array $listInVars
     * @return bool
     */
    public function associateUserWithGroup($listInVars = array())
    {
        if ( !$authUserData = UserSession::getInstance()->getUserData())
        {
            $this->errors[] = "Error: unauthorized user";
            return false;
        }

        $CValidator = new Validator();

        $CValidator->addAttribute("id", "account identifier", "required");
        $CValidator->addAttribute("min_deposit", "minimum of deposit", "required");
        $CValidator->addAttribute("applet_mode", "mode", "required");

        $CValidator->addAttribute("nickname", "nickname", "required|latin|max[15]");
        $CValidator->addAttribute("deposit", "amount of deposit", "required|numeric");
        $CValidator->addAttribute("country", "country", "required");

        if ( !$CValidator->validation($listInVars))
        {
            $this->errors = array_merge(
                $this->errors, $CValidator->getErrors()
            );

            return false;
        }

        if ($listInVars["deposit"] < $listInVars["min_deposit"])
        {
            $this->errors[] = "Error: deposit amount is not enough for the account entrance";
            return false;
        }

        // Initialize the applet mode
        $this->switchAppletMode($listInVars["applet_mode"]);

        $response = $this->connectToAPIService(
            $this->getAPIMethod("associateUserWithGroup"), array(
                "pamm_id"           => $listInVars["id"],
                "client_id"         => $authUserData["id"],

                "nickname"          => $listInVars["nickname"],
                "deposit"           => $listInVars["deposit"],
                "country_iso_code"  => $listInVars["country"]
            )
        );


        if ( !is_array($response) || !array_key_exists("status", $response))
        {
            $this->errors[] = "Error: server returned incorrect response";
        }
        elseif ( !$response["status"])
        {
            if ( !empty($response["message"]))
            {
                $this->errors[] = "Error: {$response["message"]}";
            }
            else
            {
                $this->errors[] = "Error: server returned an empty error message";
            }
        }

        if ( !empty($this->errors)) return false;

        UserSession::getInstance()->appendUserParam(
            "pamm_status", true
        );

    	return true;
    }

    /**
     * Check that is associated user
     * With any PAMM group
     * 
     * @return bool
     */
    public function isAssociatedUserWithGroup()
    {
        if ( !UserSession::getInstance()->isExistsUserParam("pamm_status"))
        {
            $listAssociatedGroups = $this->getListAssociatedGroups();
            $authUserPAMMStatus = ( !empty($listAssociatedGroups)) ? true : false;

            UserSession::getInstance()->appendUserParam(
                "pamm_status", $authUserPAMMStatus
            );
        }
        else
        {
            $authUserPAMMStatus = UserSession::getInstance()->getUserParam(
                "pamm_status"
            );
        }

        return $authUserPAMMStatus;
    }

    /**
     * Get list of the associated groups
     * @return bool|mixed
     */
    public function getListAssociatedGroups()
    {
        if ( !$authUserData = UserSession::getInstance()->getUserData())
        {
            $this->errors[] = "Error: unauthorized user";
            return false;
        }

        $response = $this->connectToAPIService(
            $this->getAPIMethod("getAssociatedGroups"), array(
                "client_id"         => $authUserData["id"]
            )
        );


        if ( !is_array($response) || !array_key_exists("status", $response))
        {
            $this->errors[] = "Error: server returned incorrect response";
        }
        elseif ( !$response["status"])
        {
            if ( !empty($response["message"]))
            {
                $this->errors[] = "Error: {$response["message"]}";
            }
            else
            {
                $this->errors[] = "Error: server returned an empty error message";
            }
        }

        if ( !empty($this->errors)) return false;

        return $response["data"];
    }

    /**
     * Get list of the closed group trades
     * 
     * @param int $ID
     * @param array $listFilterParams
     * @return bool|array
     */
    public function getListClosedTrades($ID = 0, $listFilterParams = array())
    {
        /**if ( !$authUserData = UserSession::getInstance()->getUserData())
        {
            $this->errors[] = "Error: unauthorized user";
            return false;
        }

        $listPOSTVars = array(
            "pamm"              => $ID,
            "client_id"         => $authUserData["id"]
        );

        if ( !empty($listFilterParams["from"]) &&
            $this->isValidDate($listFilterParams["from"]))
        {
            $listPOSTVars["start_date"] = $this->convertToUnixDate(
                $listFilterParams["from"]
            );
        }

        if ( !empty($listFilterParams["to"]) &&
            $this->isValidDate($listFilterParams["to"]))
        {
            $listPOSTVars["end_date"] = $this->convertToUnixDate(
                $listFilterParams["to"], true
            );
        }

        if ( !empty($listFilterParams["number_page"]) &&
            is_numeric($listFilterParams["number_page"]))
        {
            $listPOSTVars["page"] = $listFilterParams["number_page"];
        }

        $response = $this->connectToAPIService(
            $this->getAPIMethod("getClosedTrades"), $listPOSTVars
        );


        if ( !empty($response["status"]))
        {
            if (empty($response["data"]["closeTrade"]["result"]))
            {
                $this->errors[] = "Error: server returned an empty result";
                return false;
            }

            return $response["data"]["closeTrade"]["result"];
        }

        if (empty($response["result"]))
        {
            $this->errors[] = "Error: server returned an empty result";
            return false;
        }

        return $response["result"];
        **/
    }

    /**
     * Get list of the errors
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }


    // ===================================== PRIVATE METHODS ===================================== //

    /**
     * Initialize list of the API methods
     * @return void
     */
    private function initialize()
    {
        $this->listActiveModes = $this->getListActiveModes();
        if (is_array($this->listActiveModes) && count($this->listActiveModes))
        {
            if ( !array_key_exists($this->appletMode, $this->listActiveModes))
            {
                $this->appletMode = key($this->listActiveModes);
            }

            if (file_exists($this->APPRootPath . "/classes/helpers/etc/pamm.stdc"))
            {
                $fileSTDCData = file_get_contents(
                    $this->APPRootPath . "/classes/helpers/etc/pamm.stdc"
                );

                $listAPIMethods = json_decode($fileSTDCData, true);
                if (array_key_exists($this->appletMode, $listAPIMethods))
                {
                    $this->listAPIMethods = $listAPIMethods[$this->appletMode];
                }
            }
        }
    }

    /**
     * Initialize a list of active modes
     * @return mixed
     */
    private function getListActiveModes()
    {
        $response = $this->connectToAPIService(
            "listActivePAMMModes"
        );


        if ( !is_array($response) || !array_key_exists("status", $response))
        {
            $this->errors[] = "Error: server returned incorrect response";
        }
        elseif ( !$response["status"])
        {
            if ( !empty($response["message"]))
            {
                $this->errors[] = "Error: {$response["message"]}";
            }
            else
            {
                $this->errors[] = "Error: server returned an empty error message";
            }
        }
        elseif (empty($response["data"]) || !is_array($response["data"]))
        {
            $this->errors[] = "Error: list of available PAMM types is empty";
        }

        if ( !empty($this->errors)) return false;

        $listActiveModes = array_intersect_key(
            $this->listSupportedModes, array_flip($response["data"])
        );

    	return $listActiveModes;
    }

    /**
     * Get the API method
     * 
     * @param string $action
     * @return string
     */
    private function getAPIMethod($action = "")
    {
        $APIMethod = "";
        if (array_key_exists($action, $this->listAPIMethods))
        {
            $APIMethod = $this->listAPIMethods[$action];
        }

        return $APIMethod;
    }

    /**
     * Check that is valid date
     * 
     * @param string $date
     * @param string $delimiter
     * @return bool
     */
    private function isValidDate($date = "", $delimiter = "/")
    {
        if ( !$date) return false;

        list($day, $month, $year) = explode($delimiter, $date);
        if ( !checkdate($month, $day, $year))
        {
            return false;
        }

        return true;
    }

    /**
     * Convert date to unit timestamp
     * 
     * @param string $date
     * @param string $delimiter
     * @param bool $overlap
     * @return bool
     */
    private function convertToUnixDate($date = "", $delimiter = "/", $overlap = false)
    {
        if ( !$date) return false;

        list($day, $month, $year) = explode($delimiter, $date);
        if ($overlap) return mktime(23, 59, 59, $month, $day, $year);

        return mktime(0, 0, 0, $month, $day, $year);
    }
}

# end of file