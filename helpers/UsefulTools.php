<?php
trait UsefulTools
{
    /**
     * Check if the platform is actual
     *
     * @param string $type
     * @return bool
     */
    public function isActualPlatform($type = "")
    {
        return (defined("PLATFORM_TYPE") && PLATFORM_TYPE == $type);
    }
}

# end of file