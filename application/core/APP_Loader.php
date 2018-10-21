<?php
namespace application\core;


class APP_Loader extends \CI_Loader
{
    /**
     * Application loader constructor
     * @return APP_Loader
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Check if the model
     * is loaded
     *
     * @param string $name
     * @return bool
     */
    public function is_model_loaded($name = "")
    {
        return in_array($name, $this->_ci_models, true);
    }
}

# end of file