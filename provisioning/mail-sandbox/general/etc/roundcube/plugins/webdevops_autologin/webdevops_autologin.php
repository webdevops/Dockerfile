<?php

class webdevops_autologin extends rcube_plugin
{
    public $task = 'login';

    function init()
    {
        $this->add_hook('startup', array($this, 'startup'));
        $this->add_hook('authenticate', array($this, 'authenticate'));
    }

    function startup($args)
    {
        // change action to login
        if (empty($_SESSION['user_id'])) {
            $args['action'] = 'login';
        }

        return $args;
    }

    function authenticate($args) {
        $args['user'] = getenv('MAILBOX_USERNAME');
        $args['pass'] = getenv('MAILBOX_PASSWORD');
        $args['host'] = 'localhost';
        $args['cookiecheck'] = false;
        $args['valid'] = true;

        return $args;
    }
}
