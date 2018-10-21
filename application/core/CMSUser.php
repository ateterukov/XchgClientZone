<?php
namespace application\core;


class CMSUser extends CMSModel
{
    // Unique identifier
    private $id = "";

    // Personal info
    private $firstName = "";
    private $lastName = "";
    private $middleName = "";

    // Contact info
    private $email = "";
    private $phone = "";

    // Address info
    private $country = "";
    private $state = "";
    private $city = "";
    private $address = "";
    private $postalCode = "";


    /**
     * Initialize the user
     *
     * @param array $argums
     * @return void
     */
    public function initialize($argums = array())
    {

    }

    /**
     * Check if the current user
     * is authorized
     *
     * @return bool
     */
    public function authorized()
    {
        return $this->session->has_userdata("user");
    }

    /**
     * Returns authorized user data
     *
     * @param string $attribute
     * @return bool
     */
    public function get($attribute = "")
    {
        if ( !$this->authorized())
        {
            // Unauthorized user
            return false;
        }

        // Get authorized user data
        $user = $this->session->userdata(
            "user");

        // Parse authorized user data
        $user = json_decode($user, true);

        if ( !is_array($user))
        {
            // Failed to parse user data
            return false;
        }

        if ( !empty($attribute))
        {
            if ( !is_string($attribute) ||
                 !key_exists($attribute, $user))
            {
                // Not found or not exists
                return false;
            }

            // Returns user attribute
            return $user[$attribute];
        }

        // Returns user data
        return $user;
    }

    /**
     * Returns unique identifier
     * of the current user
     *
     * @return bool|int
     */
    public function getId()
    {
        if ( !$this->authorized())
        {
            return false;
        }

        return $this->get("id");
    }

    /**
     * Terminate user session
     * @return void
     */
    public function terminate()
    {
        unset($this->session->user);
    }
}

# end of file