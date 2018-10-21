<?php
namespace application\libraries\Redis;


use application\core\APPErrors;

class RedisHandler
{
    // Errors handler
    protected $errors = false;

    // Connection configs
    protected $configs = array();


    /**
     * Redis handler constructor
     *
     * @param array $params
     * @return RedisHandler
     */
    public function __construct($params = array())
    {
        // Initialize the errors handler
        $this->errors = new APPErrors();

        if (is_array($params) &&
            count($params))
        {
            // Merge list of connection configs
            $this->configs = array_merge(
                $this->configs, $params
            );
        }
    }
}

# end of file