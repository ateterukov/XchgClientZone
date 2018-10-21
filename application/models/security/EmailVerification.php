<?php
class EmailVerification
{
    public function __construct()
    {
        $this->load->library("CrmService", array(
            "driver"    => "ServiceCurlHandler"
        ));
    }


    public function resend($action = "")
    {
        // Check user authorization
        if ( !$this->app->user->is_authorized())
        {
            $this->errors[] = "Unauthorized user";
            return false;
        }

        // Check user permissions
        if ( ! $this->app->user->can($action))
        {
            $this->errors[] = "You don't have permission";
            return false;
        }

        // Collect request arguments
        $argums = array(
            "user_id"       => $this->app->user->getId()
        );

        return $this->crmService->call("resendEmailVfCode")
            ->with_argums($argums)
            ->with_http_method("post")
            ->parse();
    }
}

#end of file