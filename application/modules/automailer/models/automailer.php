<?php
if ( !defined("DIRECT")) die("Direct access not permitted");

class AutoMailer
{
    private $dbh = false;

    private $errors = array();
    private $warnings = array();

    private $baseDir = "";
    private $basePath = "";
    private $defaultTplsDirPath = "/libs/autoMailer/default_tpls";

    private $listCNFOptions = array();
    private $SMTPMailer = false;


    public function __construct()
    {
        $this->dbh = new Database();

        $this->baseDir = rtrim(constant("BASE_DIR"), "/");
        $this->basePath = $_SERVER["DOCUMENT_ROOT"] . $this->baseDir;
        $this->defaultTplsDirPath = $this->basePath . $this->defaultTplsDirPath;

        $this->initialize();
    }

    /**
     * Send the notification message
     * 
     * @param string $action
     * @param string $langISOCode
     * @param array $dataSet
     * @return bool
     */
    public function sendNMessage($action = "", $langISOCode = "", $dataSet = array())
    {
        if ( !$NMTypeData = $this->getNMType($action))
        {
            $this->errors[] = "Error: the action doesn't support a notification message";
            $this->addEventMessage(array(
                "action"            => $action,
                "lang_iso_code"     => $langISOCode,
                "input_arrtibs"     => $dataSet
            ));

            return false;
        }

        if ( !$NMTypeData["status"])
        {
            $this->errors[] = "Error: the notification message for this action disabled";
            $this->addEventMessage(array(
                "action"            => $NMTypeData["action"],
                "lang_iso_code"     => $langISOCode,
                "input_arrtibs"     => $dataSet,
                "type"              => $NMTypeData["name"]
            ));

            return false;
        }

        if ($NMLangData = $this->getNMLanguage($langISOCode))
        {
            $listCMSSettings = $this->getListCMSSettings($NMLangData["id"]);
            foreach (array_keys($this->listCNFOptions) as $CNFOption)
            {
                if ( !empty($listCMSSettings[$CNFOption]))
                {
                    $this->listCNFOptions[$CNFOption] = $listCMSSettings[$CNFOption];
                }
            }
        }

        $this->listCNFOptions["smtp"] = $this->getListSMTPOptions();
        if ($this->listCNFOptions["smtp"]["status"])
        {
            $this->initializeSMTPMailer();
        }

        if ($NMTypeData["to_user"])
        {
            if (empty($dataSet["email_address"]) ||
                !filter_var($dataSet["email_address"], FILTER_VALIDATE_EMAIL))
            {
                
                $this->errors[] = "Error: the e-mail address of the recepient is empty or incorrect";
                $this->addEventMessage(array(
                    "action"            => $NMTypeData["action"],
                    "lang_iso_code"     => $langISOCode,
                    "input_arrtibs"     => $dataSet,
                    "type"              => $NMTypeData["name"],
                    "addressee"         => "toUser"
                ));
            }
            else
            {
                $NMDataToUser = $this->createNMessage(
                    $NMLangData["id"], $NMTypeData["name"], $NMTypeData["title"], "toUser", $dataSet
                );

                $NMDataToUser["status"] = $this->toSend(
                    $dataSet["email_address"], $NMDataToUser
                );

                $this->addEventMessage(array(
                    "action"            => $NMTypeData["action"],
                    "lang_iso_code"     => $langISOCode,
                    "input_arrtibs"     => $dataSet,
                    "type"              => $NMTypeData["name"],
                    "addressee"         => "toUser",
                    "email_to"          => $dataSet["email_address"],
                    "subject"           => $NMDataToUser["subject"],
                    "contents"          => $NMDataToUser["contents"]
                ));
            }
        }

        if ($NMTypeData["to_support"])
        {
            if (empty($this->listCNFOptions["addressee_email"]) ||
                !filter_var($this->listCNFOptions["addressee_email"], FILTER_VALIDATE_EMAIL))
            {
                $this->errors[] = "Error: the e-mail address of the recepient is empty or incorrect";
                $this->addEventMessage(array(
                    "action"            => $NMTypeData["action"],
                    "lang_iso_code"     => $langISOCode,
                    "input_arrtibs"     => $dataSet,
                    "type"              => $NMTypeData["name"],
                    "addressee"         => "toSupport"
                ));
            }
            else
            {
                $NMDataToSupport = $this->createNMessage(
                    $NMLangData["id"], $NMTypeData["name"], $NMTypeData["title"], "toSupport", $dataSet
                );

                $NMDataToSupport["status"] = $this->toSend(
                    $this->listCNFOptions["addressee_email"], $NMDataToSupport
                );

                $this->addEventMessage(array(
                    "action"            => $NMTypeData["action"],
                    "lang_iso_code"     => $langISOCode,
                    "input_arrtibs"     => $dataSet,
                    "type"              => $NMTypeData["name"],
                    "addressee"         => "toSupport",
                    "email_to"          => $dataSet["email_address"],
                    "subject"           => $NMDataToUser["subject"],
                    "contents"          => $NMDataToUser["contents"]
                ));
            }
        }

        if (($NMTypeData["to_user"] && !$NMDataToUser["status"]) ||
            ($NMTypeData["to_support"] && !$NMDataToSupport["status"])) return false;

        return true;
    }


    // ==================================== PRIVATE METHODS ==================================== //

    /**
     * Initialize the CNF options
     * @return void
     */
    private function initialize()
    {
        $webSiteUri = $this->getWEBSiteUri();
        $webSiteUriHost = parse_url($webSiteUri, PHP_URL_HOST);

        $this->listCNFOptions = array(
            "tr_uri"            => $this->getTRUri(),
            "clZone_uri"        => $this->getClientZoneUri(),
            "webSite_uri"       => $webSiteUri,

            "company_name"      => (preg_match("#\.(.+?)\.#is", $webSiteUriHost, $matches))
                ? strtoupper($matches[1])
                : strtoupper($webSiteUriHost),
            "support_email"     => "support@{$webSiteUriHost}",

            "contact_phone"     => "",
            "contact_address"   => "",
            "addressee_email"   => ""
        );
    }

    /**
     * Get base uri of the web site
     * @return string
     */
    private function getWEBSiteUri()
    {
        return rtrim(constant("BASE_URI"), "/");
    }

    /**
     * Get base uri of the TR platform
     * @return string
     */
    private function getTRUri()
    {
        if ( !defined("TR_URI"))
        {
            $webSiteUri = $this->getWEBSiteUri();
            $TRUri = $webSiteUri . "/trading-room";

            return $TRUri;
        }

        return rtrim(constant("TR_URI"), "/");
    }

    /**
     * Get base uri of the client zone
     * @return string
     */
    private function getClientZoneUri()
    {
        if ( !defined("CLZONE_URI"))
        {
            $webSiteUri = $this->getWEBSiteUri();

            $webSiteUriScheme = parse_url($webSiteUri, PHP_URL_SCHEME);
            $webSiteUriHost = parse_url($webSiteUri, PHP_URL_HOST);

            $CLZoneUri = $webSiteUriScheme . "://clientzone." . $webSiteUriHost;
            return $CLZoneUri;
        }

        return rtrim(constant("CLZONE_URI"), "/");
    }

    /**
     * Get list of the CMS settings
     * 
     * @param int $langIDx
     * @return array
     */
    private function getListCMSSettings($langIDx = 0)
    {
        $listCMSSettings = array();

        $this->dbh->query("
            SELECT tstgs.name, tstgv.value
            FROM settings AS tstgs
            LEFT JOIN setting_values AS tstgv
            ON tstgs.id = tstgv.setting_id
            AND tstgv.language_id = :langIDx"
        );

        $this->dbh->bind(":langIDx", $langIDx);
        $this->dbh->execute();

        $resultDbProcess = $this->dbh->resultset();
        if ( !empty($resultDbProcess))
        {
            foreach ($resultDbProcess as $dataSet)
            {
                $listCMSSettings[$dataSet["name"]] = $dataSet["value"];
            }
        }

        return $listCMSSettings;
    }

    /**
     * Get list of the SMTP options
     * @return mixed
     */
    private function getListSMTPOptions()
    {
        $listSMTPOptions = array(
            "status"            => false
        );

        $this->dbh->query("
            SELECT tamopt.name, tamopt.value
            FROM autoMailer_options AS tamopt
            WHERE tamopt.bundle = :bundleName"
        );

        $this->dbh->bind(":bundleName", "smtp");
        $this->dbh->execute();

        $resultDbProcess = $this->dbh->resultset();
        if ( !empty($resultDbProcess))
        {
            foreach ($resultDbProcess as $dataSet)
            {
                $listSMTPOptions[$dataSet["name"]] = $dataSet["value"];
            }
        }

        return $listSMTPOptions;
    }

    /**
     * Get type data of the notification message
     * 
     * @param $action
     * @return mixed
     */
    private function getNMType($action = "")
    {
        $this->dbh->query("
            SELECT tamt.id, tamt.action,
            tamt.name, tamt.title,
            tamt.to_user, tamt.to_support, tamt.status
            FROM autoMailer_types AS tamt
            WHERE tamt.action = :action"
        );

        $this->dbh->bind(":action", $action);
        $this->dbh->execute();

        return $this->dbh->single();
    }

    /**
     * Get language data of the notification message
     * 
     * @param int $langISOCode
     * @return mixed
     */
    private function getNMLanguage($langISOCode = "")
    {
        $this->dbh->query("
            SELECT tslng.id, tslng.name,
            tslng.lang as iso_code, tslng.description
            FROM site_languages AS tslng
            WHERE tslng.lang = :ISOCode
            AND tslng.status = 1"
        );

        $this->dbh->bind(":ISOCode", $langISOCode);
        $this->dbh->execute();

        return $this->dbh->single();
    }

    /**
     * Get template data of the notification message by default
     * 
     * @param string $NMType
     * @param string $NMSubject
     * @param string $addressee [ toUser|toSupport ]
     * @return array
     */
    private function getDefaultNMTemplate($NMType = "", $NMSubject = "", $addressee = "")
    {
        $defaultTplData = array(
            "subject"       => $NMSubject,
            "contents"      => ""
        );

        if (file_exists($this->defaultTplsDirPath . "/{$addressee}/{$NMType}.tpl"))
        {
            $defaultTplData["contents"] = file_get_contents(
                $this->defaultTplsDirPath . "/{$addressee}/{$NMType}.tpl"
            );
        }

        return $defaultTplData;
    }

    /**
     * Get template data of the notification message
     * 
     * @param int $NMLangID
     * @param string $NMType
     * @param string $addressee
     * @return mixed
     */
    private function getNMTemplate($NMLangID = 0, $NMType = "", $addressee = "")
    {
        $this->dbh->query("
            SELECT tamtpls.subject, tamtpls.contents
            FROM autoMailer_templates AS tamtpls
            WHERE tamtpls.type = :type AND tamtpls.language_id = :lId
            AND tamtpls.addressee = :addressee"
        );

        $this->dbh->bind(":lId", $NMLangID);
        $this->dbh->bind(":type", $NMType);
        $this->dbh->bind(":addressee", $addressee);
        $this->dbh->execute();

        return $this->dbh->single();
    }

    /**
     * Get wrapper data of the notification message by default
     * @return array
     */
    private function getDefaultNMWpapper()
    {
        $defaultWpData = array(
            "header_contents"       => "",
            "footer_contents"       => ""
        );

        if (file_exists($this->defaultTplsDirPath . "/wrapper/WP_HEADER.tpl"))
        {
            $defaultWpData["header_contents"] = file_get_contents(
                $this->defaultTplsDirPath . "/wrapper/WP_HEADER.tpl"
            );
        }

        if (file_exists($this->defaultTplsDirPath . "/wrapper/WP_FOOTER.tpl"))
        {
            $defaultWpData["footer_contents"] = file_get_contents(
                $this->defaultTplsDirPath . "/wrapper/WP_FOOTER.tpl"
            );
        }

        return $defaultWpData;
    }

    /**
     * Get wrapper data of the notification message
     * 
     * @param int $NMLangID
     * @return mixed
     */
    private function getNMWrapper($NMLangID = 0)
    {
        $this->dbh->query("
            SELECT tamwps.header_contents,
            tamwps.footer_contents
            FROM autoMailer_wrappers AS tamwps
            WHERE tamwps.language_id = :lId"
        );

        $this->dbh->bind(":lId", $NMLangID);
        $this->dbh->execute();

        return $this->dbh->single();
    }

    /**
     * Parse the HTML data
     * 
     * @param string $HTMLData
     * @param array $dataSet
     * @return string
     */
    private function parseHTMLData($HTMLData = "", $dataSet = array())
    {
        $listPlaceholders = $this->listCNFOptions;
        if (is_array($dataSet) && count($dataSet))
        {
            $listPlaceholders = array_merge($listPlaceholders, $dataSet);
        }

        foreach ($listPlaceholders as $attribName => $attribVal)
        {
            $HTMLData = str_replace("[[+{$attribName}]]", $attribVal, $HTMLData);
        }

        return $HTMLData;
    }

    /**
     * Get HTML contents of the notification message
     * 
     * @param array $NMWrapperData
     * @param array $NMTemplateData
     * @param array $dataSet
     * @return mixed
     */
    private function getNMHTMLContents($NMWrapperData = array(), $NMTemplateData = array(), $dataSet = array())
    {
        $NMHTMLContents = "";
        if (file_exists($this->defaultTplsDirPath . "/layout/NM_LAYOUT.tpl"))
        {
            $NMHTMLContents = file_get_contents($this->defaultTplsDirPath . "/layout/NM_LAYOUT.tpl");
            $NMHTMLContents = str_replace(array(
                "[[~title]]",
                "[[~header]]",
                "[[~body]]",
                "[[~footer]]"
            ), array(
                $NMTemplateData["subject"],
                html_entity_decode($NMWrapperData["header_contents"], ENT_QUOTES, "UTF-8"),
                html_entity_decode($NMTemplateData["contents"], ENT_QUOTES, "UTF-8"),
                html_entity_decode($NMWrapperData["footer_contents"], ENT_QUOTES, "UTF-8")
            ), $NMHTMLContents);
        }
        else
        {
            $NMHTMLContents = html_entity_decode($NMWrapperData["header_contents"], ENT_QUOTES, "UTF-8") .
                html_entity_decode($NMTemplateData["contents"], ENT_QUOTES, "UTF-8") .
                html_entity_decode($NMWrapperData["footer_contents"], ENT_QUOTES, "UTF-8");
        }

        $NMHTMLContents = $this->parseHTMLData($NMHTMLContents, $dataSet);
        return $NMHTMLContents;
    }

    /**
     * Create the nofication message
     * 
     * @param int $NMLangIDx
     * @param string $NMType
     * @param string $NMSubject
     * @param string $addressee
     * @param array $dataSet
     * @return array
     */
    private function createNMessage($NMLangIDx = 0, $NMType = "", $NMSubject = "", $addressee = "", $dataSet = array())
    {
        if ( !empty($NMLangIDx))
        {
            $NMWrapperData = $this->getNMWrapper($langData["id"]);
            if ( !$NMWrapperData)
            {
                $this->warnings[] = "Warning: the wrapper is not found.";
                $this->warnings[] = "Warning: the message to be sent with a wrapper by default.";

                $NMWrapperData = $this->getDefaultNMWpapper();
            }

            $NMTemplateData = $this->getNMTemplate(
                $NMType, $langData["id"], $addressee
            );

            if ( !$NMTemplateData)
            {
                $this->warnings[] = "Warning: the template is not found.";
                $this->warnings[] = "Warning: the message to be sent with a template by default.";

                $NMTemplateData = $this->getDefaultNMTemplate(
                    $NMType, $NMSubject, $addressee
                );
            }
        }
        else
        {
            $this->warnings[] = "Warning: the language is not found.";
            $this->warnings[] = "Warning: the message to be sent with a template and wrapper by default.";

            $NMWrapperData = $this->getDefaultNMWpapper();
            $NMTemplateData = $this->getDefaultNMTemplate(
                $NMType, $NMSubject, $addressee
            );
        }

        $NMessageData = array(
            "subject"       => $NMTemplateData["subject"],
            "contents"      => $this->getNMHTMLContents($NMWrapperData, $NMTemplateData, $dataSet)
        );

        return $NMessageData;
    }

    /**
     * Initialize the SMTP mailer
     * @return void
     */
    private function initializeSMTPMailer()
    {
        if (file_exists($this->basePath . "/libs/phpmailer/PHPMailerAutoload.php"))
        {
            require($this->basePath . "/libs/phpmailer/PHPMailerAutoload.php");
            $this->SMTPMailer = new PHPMailer();

            $this->SMTPMailer->isSMTP();
            $this->SMTPMailer->Timeout = 5;

            $this->SMTPMailer->Host = htmlspecialchars_decode($this->listCNFOptions["smtp"]["host"], ENT_QUOTES, "UTF-8");
            $this->SMTPMailer->Port = $this->listCNFOptions["smtp"]["port"];

            $this->SMTPMailer->Username = htmlspecialchars_decode($this->listCNFOptions["smtp"]["username"], ENT_QUOTES, "UTF-8");
            $this->SMTPMailer->Password = htmlspecialchars_decode($this->listCNFOptions["smtp"]["pass"], ENT_QUOTES, "UTF-8");
            $this->SMTPMailer->SMTPAuth = ($this->listCNFOptions["smtp"]["auth_status"]) ? true : false;
            $this->SMTPMailer->SMTPSecure = $this->listCNFOptions["smtp"][""];

            $this->SMTPMailer->SMTPDebug = 0;
            $this->SMTPMailer->CharSet = "UTF-8";

            return true;
        }

        return false;
    }

    /**
     * To send the notification message
     * 
     * @param array $emailTo
     * @param array $NMessageData
     * @return bool
     */
    private function toSend($emailTo = "", $NMessageData = array())
    {
        if ($this->listCNFOptions["smtp"]["status"])
        {
            $this->SMTPMailer->setFrom(
                $this->listCNFOptions["company_name"],
                $this->listCNFOptions["support_email"]
            );

            $this->SMTPMailer->addAddress($emailTo);
            $this->SMTPMailer->Subject = $NMessageData["subject"];
            $this->SMTPMailer->msgHTML($NMessageData["contents"]);

            if ( !$this->SMTPMailer->send()) 
            {
                $this->SMTPMailer->ClearAllRecipients();
                $this->SMTPMailer->ClearAttachments();

                $this->errors[] = "Error: " . $this->SMTPMailer->ErrorInfo;
                return false;
            }

            $this->SMTPMailer->ClearAllRecipients();
            $this->SMTPMailer->ClearAttachments();

            return true;
        }

        $NMHeaders  = "MIME-Version: 1.0\r\n";
        $NMHeaders .= "Content-type: text/html; utf-8\r\n";
        $NMHeaders .= "From: {$this->listCNFOptions["company_name"]}";
        $NMHeaders .= "<{$this->listCNFOptions["support_email"]}>\r\n";

        if ( !mail($emailTo, $NMessageData["subject"], $NMessageData["contents"], $NMHeaders))
        {
            $this->errors[] = "Error: the sendmail service error";
            return false;
        }

        return true;
    }

    /**
     * Add a new event message to the event log
     * 
     * @param array $attribs
     * @return bool
     */
    private function addEventMessage($attribs = array())
    {
        $listEventParams = array(
            "action"            => "",
            "lang_iso_code"     => "",
            "input_attribs"     => "",

            "type"              => "",
            "addressee"         => "",
            "email_to"          => "",

            "subject"           => "",
            "contents"          => ""
        );

        $listEventParams = array_merge($listEventParams, $attribs);
        $this->dbh->query("
            INSERT INTO autoMailer_logs
            (date, action, lang_iso_code, input_attribs, type, addressee, email_to,
            subject, contents, status, warnings, errors)
            VALUES (:date, :action, :langISOCode, :attribs, :type,
            :addressee, :emailTo, :subject, :contents, :status, :warnings, :errors)"
        );

        $this->dbh->bind(":date", time());
        $this->dbh->bind(":action", $listEventParams["action"]);
        $this->dbh->bind(":langISOCode", $listEventParams["lang_iso_code"]);
        $this->dbh->bind(":attribs", ( !empty($listEventParams["input_attribs"])) ? json_encode($listEventParams["input_attribs"]) : "");

        $this->dbh->bind(":type", $listEventParams["type"]);
        $this->dbh->bind(":addressee", $listEventParams["addressee"]);
        $this->dbh->bind(":emailTo", $listEventParams["email_to"]);

        $this->dbh->bind(":subject", $listEventParams["subject"]);
        $this->dbh->bind(":contents", htmlentities($listEventParams["contents"], ENT_QUOTES, "UTF-8"));

        $this->dbh->bind(":status", ( !empty($this->errors)) ? "error" : "success");
        $this->dbh->bind(":warnings", ( !empty($this->warnings)) ? json_encode($this->warnings) : "");
        $this->dbh->bind(":errors", ( !empty($this->errors)) ? json_encode($this->errors) : "");
        $this->dbh->execute();

        $this->errors = array();
        $this->warnings = array();
    }
}