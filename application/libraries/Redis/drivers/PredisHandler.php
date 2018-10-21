<?php
namespace application\libraries\Redis\drivers;


use application\libraries\Redis\interfaces\IRedisHandler;
use application\libraries\Redis\RedisHandler;

class PredisHandler extends RedisHandler implements IRedisHandler
{
    /**
     * Predis handler constructor
     *
     * @param array $configs
     * @return PredisHandler
     */
    public function __construct($configs = array())
    {
        parent::__construct($configs);
    }

    /**
     * Initialize the predis connection
     * @return bool|object
     */
    public function initialize()
    {
        if ( !file_exists(__DIR__ . "/predis/autoload.php"))
        {
            // Add error message
            $this->errors->append(
                "Error: failed to connect to the data storage"
            );

            // Add message to application event log
            log_message("debug", "Error: predis not found");

            return false;
        }

        // Connect the predis autoloader
        require __DIR__ . "/predis/autoload.php";

        try
        {
            // Initialize the predis connection
            $connection = new Predis\Client($this->configs);
        }
        catch (Predis\Connection\ConnectionException $exception)
        {
            // Add error message
            $this->errors->append(
                "Error: failed to connect to the data storage"
            );

            // Add message to application event log
            log_message("debug", $exception->getMessage());

            return false;
        }

        return $connection;
    }
}

# end of file