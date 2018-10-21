<?php
namespace CrmService\interfaces;


interface ICrmServiceRequester
{
    /**
     * Create a new request
     *
     * @param string $uri
     * @return mixed
     */
    public function create($uri = "");
}

# end of file