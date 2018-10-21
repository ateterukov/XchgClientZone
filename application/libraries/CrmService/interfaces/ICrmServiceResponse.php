<?php
interface ICrmServiceResponse
{
    /**
     * Returns response attribute
     * or list of all attributes
     *
     * @param string $attribute
     * @return mixed
     */
    public function get($attribute = "");

    /**
     * Returns response results
     *
     * @param string $attribute
     * @return mixed
     */
    public function results($attribute = "");

    /**
     * Parse response
     *
     * @param string $format
     * @return mixed
     */
    public function parse($format = "");
}

# end of file