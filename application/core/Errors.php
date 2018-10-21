<?php
namespace application\core;


class Errors extends \ArrayObject
{
    private static $instance = false;


    public static function instance()
    {

    }

    /**
     * Application errors constructor
     * @return Errors
     */
    public function __construct()
    {
        parent::__construct(array(),
            \ArrayObject::ARRAY_AS_PROPS
        );
    }

    /**
     * Add GetMessage function - check error code, check error alias, check "Error: %s"
     * Add messages storage
     */


    /**
     * @param string $message
     */
    public function add($message = "")
    {
        parent::offsetSet(null, $message);
    }

    public function merge($errors = array())
    {
        if (is_array($errors) &&
            count($errors))
        {
            foreach ($errors as $message)
            {
                parent::offsetSet(null, $message);
            }
        }
    }

    public function offsetSet($cursor, $errors)
    {
        if (is_string($errors) &&
            mb_strlen($errors))
        {
            parent::offsetSet(null, $errors);
        }
        elseif (is_array($errors) &&
            count($errors))
        {
            foreach ($errors as $message)
            {
                parent::offsetSet(null, $message);
            }
        }
    }

    public function export()
    {
        return parent::getArrayCopy();
    }

    public function clear()
    {
        parent::exchangeArray(array());
    }
}

# end of file