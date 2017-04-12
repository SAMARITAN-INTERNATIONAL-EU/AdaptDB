<?php

namespace AppBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;


/**
 * AppBundle
 * @package AppBundle
 */
class AppBundle extends Bundle
{
    public function getParent()
    {
        return 'FOSUserBundle';
    }
}
