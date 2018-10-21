<?php
namespace application\core;

require_once BASEPATH . "core/Model.php";

use application\core\interfaces\ModelInterface;


/**
 * Application model class
 *
 * @property \CI_Input $input
 * @property \application\core\APP_Loader $load
 * @property \application\core\Errors $errors
 */
class CMSModel extends \CI_Model implements ModelInterface
{
    /**
     * Application model constructor
     * @return CMSModel
     */
    public function __construct()
    {
        parent::__construct();

        // Initialize the error handler
        $this->errors = Errors::instance();

        // Initialize the user handler
        $this->user = CMSUser::instance();
    }

    public static function create()
    {

    }
}

# end of file