<?php
namespace application\modules\questionnaire\controllers;

defined("BASEPATH") OR exit("No direct script access allowed");

use application\core\CMS_Controller;


/**
 * Questionnaire controller
 *
 * @property \application\modules\questionnaire\models\Questionnaire $questionnaire
 * @package application\modules\questionnaire\controllers
 */
class Questionnaire extends CMS_Controller
{
    /**
     * Save the results
     * of the user's questionnaire
     *
     * @return void
     */
    public function save()
    {
        // Load model of questionnaire
        $this->load->model("questionnaire");

        if ( !$this->load->is_model_loaded("questionnaire"))
        {
            trigger_error(sprintf(
                "Error: failed to load model",
                "questionnaire"), E_USER_ERROR
            );


            //$this->errors[] = "Error: failed to load model";
            $this->output->failure(array(
                "Error: failed to load model"
            ));
        }

        // List of input arguments
        $argums = $this->input->post();

        // Save user's questionnaire results
        if ( !$this->questionnaire->save($argums))
        {
            $this->output->failure();
        }

        // Output success
        $this->output->success();
    }
}

# end of file