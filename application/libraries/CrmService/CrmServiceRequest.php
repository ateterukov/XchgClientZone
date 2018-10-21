<?php
namespace CrmService;


use CrmService\interfaces\ICrmServiceRequest;

abstract class CrmServiceRequest implements ICrmServiceRequest
{
    // Request uri address
    protected $uri = "";

    // Request setting options
    protected $options = array();

    protected $argums = array();


    /*
     * class Object {
    function ResetObject() {
        foreach ($this as $key => $value) {
            unset($this->$key);
        }
    }
}
     */
}

# end of file