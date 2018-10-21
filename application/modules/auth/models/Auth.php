<?php
namespace application\modules\auth\models;


use application\core\CMSModel;
use application\modules\users\controllers\Users;

/**
 * Authorization class
 *
 * @property Users $users
 * @package application\modules\auth\models
 */
class Auth extends CMSModel
{
    /**
     * Identify user
     *
     * @param array $argums
     * @return mixed
     */
    public function identify($argums = array())
    {
        // fetch username & password
        // validation

        $this->users = new Users();
        if ( ! $user = $this->users->authenticate($argums))
        {
            trigger_error("Login or password is incorrect");
        }

        return $user;
    }

    /**
     * Authorize user
     *
     * @param array $user
     * @return void
     */
    public function authorize(Array $user = array())
    {
        $this->user->initialize($user);
    }
}

# end of file