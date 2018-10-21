<?php
trait Loader
{
    /**
     * Load the system utility
     *
     * @param string $sources
     * @return bool
     */
    public function loadUtility($sources = "")
    {
        if (is_array($sources))
        {
            return array_walk($sources, function($source){
                $this->loadUtility($source);
            });
        }

        // Get the utility name
        $utility = pathinfo(
            $sources, PATHINFO_FILENAME
        );

        // Utility is already initialized
        if (property_exists($this, lcfirst($utility)))
        {
            return true;
        }

        if ( !class_exists($utility, false))
        {
            // Build the path to the utility
            $path = implode(DIRECTORY_SEPARATOR, array(
                APP_PATH, pathinfo(trim(
                    $sources, DIRECTORY_SEPARATOR
                ), PATHINFO_DIRNAME),
                sprintf("%s.php", $utility)
            ));

            if ( !file_exists($path))
            {
                return false;
            }

            require_once $path;
        }

        if ( !class_exists($utility, false))
        {
            return false;
        }

        $utility = lcfirst($utility);

        // Initialize the utility
        $this->{$utility} = new $utility();

        return true;
    }
}

# end of file