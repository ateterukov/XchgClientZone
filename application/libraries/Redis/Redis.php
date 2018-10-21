<?php
namespace application\libraries\Redis;


use application\core\APPErrors;
use application\libraries\Redis\interfaces\IRedisHandler;

class Redis
{
    // Errors handler
    private $errors = false;

    // Storage driver
    private $driver = "predis";

    // Connection handler
    private $connection = false;


    /**
     * Redis constructor
     *
     * @param array $params
     * @return Redis
     */
    public function __construct($params = array())
    {
        // Initialize the errors handler
        $this->errors = new APPErrors();

        if ( !empty($params["driver"]))
        {
            // Set custom storage driver
            $this->driver = $params["driver"];
        }

        // Class name of the storage driver
        $class = sprintf("%sHandler.php",
            $this->driver
        );

        if ( !class_exists($class, false))
        {
            if ( !file_exists($path = __DIR__ . "/drivers/" . $class . ".php"))
            {
                // Add error message
                $this->errors->append(
                    "Error: failed to initialize the data storage"
                );

                // Add message to event log
                log_message("debug", sprintf(
                    "Error: %s storage driver not found",
                    $this->driver
                ));
            }

            require_once $path;

            if ( !class_exists($class, false))
            {
                // Add error message
                $this->errors->append(
                    "Error: failed to initialize the data storage"
                );

                // Add message to event log
                log_message("debug", sprintf(
                    "Error: %s storage driver not declared",
                    $this->driver
                ));
            }
        }

        // Initialize storage handler
        $handler = new $class($params);

        if ( !$handler instanceof IRedisHandler)
        {
            // Add error message
            $this->errors->append(
                "Error: failed to initialize the data storage"
            );

            // Add message to event log
            log_message("debug", sprintf(
                "Error: %s doesn't implement IStorageHandler",
                $class
            ));
        }

        // Initialize storage handler
        $this->connection = $handler->initialize();

        // Add message to event log
        log_message("info", sprintf(
            "Storage: class initialized using %s driver",
            $this->driver
        ));
    }

    /**
     * Call the storage provider method
     *
     * @param string $name
     * @param array $argums
     * @return mixed
     */
    public function __call($name = "", $argums = array())
    {
        // Check if the method is exists
        if ( !is_object($this->connection) ||
             !is_callable(array($this->connection, $name)))
        {
            return false;
        }

        // Call the storage provider method
        return call_user_func_array(array(
            $this->connection, $name), $argums
        );
    }
}

# end of file