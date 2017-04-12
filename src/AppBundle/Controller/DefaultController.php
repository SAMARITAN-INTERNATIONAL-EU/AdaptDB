<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;


class DefaultController extends Controller
{

    /**
     * Shows the welcome page when user is authenticated, shows login-page if not
     *
     * @package AppBundle\Controller
     * @Route("/", name="default")
     */
    public function defaultAction()
    {
        //Maybe this could be solved by a statement in security.yml too
        $securityContext = $this->container->get('security.authorization_checker');
        if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED') || $securityContext->isGranted('IS_AUTHENTICATED_FULLY ')) {
            return $this->redirect($this->generateUrl('welcome'));
        } else {
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
    }

    /**
     * @package AppBundle\Controller
     * @Route("/welcome", name="welcome")
     */
    public function welcomeAction()
    {
        return $this->render('default/welcome.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..'),
        ]);
    }

}
