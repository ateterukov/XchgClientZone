<?php
defined("APP_PATH") || exit("No direct script access allowed");

// Connect the class builder
require_once APP_PATH . "/classes/core/builder.php";


class WS_Helper extends Builder
{
    public function __construct()
    {
        // Initialize the builder
        parent::__construct();

        // Initialize the CRM connection
        $this->crmService = Connector::instantiate()
            ->initialize("crmService");
    }
}

# end of file