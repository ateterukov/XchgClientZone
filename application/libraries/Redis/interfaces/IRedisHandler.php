<?php
namespace application\libraries\Redis\interfaces;


interface IRedisHandler
{
    /**
     * Initialize the redis handler
     * @return bool|object
     */
    public function initialize();
}

# end of file