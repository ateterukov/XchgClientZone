<?php
namespace application\core;


class APP_Output extends \CI_Output
{
    /**
     * Output success
     *
     * @param array $dataset
     * @param string $message
     * @return void
     */
    public function success($dataset = array(), $message = "")
    {
        $response = array(
            "status"        => "success",
            "message"       => ( !empty($message))
                ? $message
                : "Operation is succeeded"
        );

        if (is_array($dataset) && count($dataset))
        {
            $response = array_merge(
                $response, $dataset
            );
        }

        $this->set_status_header(200)
            ->set_content_type("application/json", "utf-8")
            ->set_output(json_encode($response))
            ->_display();

        exit();
    }

    /**
     * Output failure
     * @return void
     */
    public function failure()
    {
        $response = array(
            "status"        => "error",
            "errors"        => implode("<br/>", array_map(
                "nl2br", APPErrors::getInstance()->get()
            ))
        );

        $this->set_status_header(200)
            ->set_content_type("application/json", "utf-8")
            ->set_output(json_encode($response))
            ->_display();

        exit();
    }
}

# end of file