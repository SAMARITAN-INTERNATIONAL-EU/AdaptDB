<?php

namespace AppBundle\Controller;

use AppBundle\Service\ApiHelperService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use AppBundle\Entity\ApiKey;
use AppBundle\Form\ApiKeyType;

/**
 * ApiKey controller.
 *
 * @Route("/apikey")
 */
class ApiKeyController extends Controller
{
    /**
     * Lists all ApiKey entities.
     *
     * @Route("/", name="apikey_index")
     * @Method("GET")
     * @Security("has_role('ROLE_SYSTEM_ADMIN')")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $apiKeys = $em->getRepository('AppBundle:ApiKey')->findAll();

        return $this->render('apikey/index.html.twig', array(
            'apiKeys' => $apiKeys,
        ));
    }

    /**
     * Creates a new ApiKey entity.
     *
     * @Route("/new", name="apikey_new")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_SYSTEM_ADMIN')")
     */
    public function newAction(Request $request)
    {
        $apiKey = new ApiKey();

        /** @var ApiHelperService $apiHelperService */
        $apiHelperService = $this->get('app.api_helper_service');
        $apiKey->setApiKey($apiHelperService->generateApiKey());

        $form = $this->createForm('AppBundle\Form\ApiKeyType', $apiKey);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $em->persist($apiKey);
            $em->flush();

            return $this->redirectToRoute('apikey_index');
        }

        return $this->render('apikey/new.html.twig', array(
            'apiKey' => $apiKey,
            'form' => $form->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing ApiKey entity.
     *
     * @Route("/{id}/edit", name="apikey_edit")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_SYSTEM_ADMIN')")
     */
    public function editAction(Request $request, ApiKey $apiKey)
    {
        $editForm = $this->createForm('AppBundle\Form\ApiKeyType', $apiKey);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($apiKey);
            $em->flush();

            return $this->redirectToRoute('apikey_index');
        }

        return $this->render('apikey/edit.html.twig', array(
            'apiKey' => $apiKey,
            'edit_form' => $editForm->createView(),
        ));
    }

    /**
     * Deletes a ApiKey entity.
     *
     * @Route("/{id}/delete", name="apikey_delete")
     * @Method("GET")
     * @Security("has_role('ROLE_SYSTEM_ADMIN')")
     */
    public function deleteAction($id)
    {

        $em = $this->getDoctrine()->getManager();
        $apiKey = $em->getRepository('AppBundle:ApiKey')->find($id);

        if ($apiKey === null ) {
            throw new \Doctrine\ORM\EntityNotFoundException();
        }

        //Delete all referenced AuthTokens
        $authTokensForApiKey = $em->getRepository('AppBundle:AuthToken')->findBy(array("apiKey" => $apiKey));

        foreach ($authTokensForApiKey as $authToken) {
            $em->remove($authToken);
        }

        $em->flush();

        if ($apiKey) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($apiKey);
            $em->flush();
        }

        return $this->redirectToRoute('apikey_index');
    }
}
