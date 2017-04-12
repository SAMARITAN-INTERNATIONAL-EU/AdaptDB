<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use AppBundle\Entity\DataSource;
use AppBundle\Form\DataSourceType;

/**
 * DataSource controller.
 *
 * @package AppBundle\Controller
 * @Route("/datasource")
 */
class DataSourceController extends Controller
{
    /**
     * Lists all DataSource entities.
     *
     * @Route("/", name="datasource_index")
     * @Method("GET")
     * @Security("has_role('ROLE_SYSTEM_ADMIN')")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $dataSources = $em->getRepository('AppBundle:DataSource')->findAll();

        return $this->render('datasource/index.html.twig', array(
            'dataSources' => $dataSources,
        ));
    }

    /**
     * Creates a new DataSource entity.
     *
     * @Route("/new", name="datasource_new")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_SYSTEM_ADMIN')")
     */
    public function newAction(Request $request)
    {
        $dataSource = new DataSource();
        $form = $this->createForm('AppBundle\Form\DataSourceType', $dataSource);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->persist($dataSource);
            $em->flush();

            return $this->redirectToRoute('datasource_index');
        }

        return $this->render('datasource/new.html.twig', array(
            'dataSource' => $dataSource,
            'form' => $form->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing DataSource entity.
     *
     * @Route("/{id}/edit", name="datasource_edit")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_SYSTEM_ADMIN')")
     */
    public function editAction(Request $request, DataSource $dataSource)
    {
        $editForm = $this->createForm('AppBundle\Form\DataSourceType', $dataSource);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->persist($dataSource);
            $em->flush();

            return $this->redirectToRoute('datasource_index');
        }

        return $this->render('datasource/edit.html.twig', array(
            'dataSource' => $dataSource,
            'edit_form' => $editForm->createView(),
        ));
    }

    /**
     * Deletes a DataSource entity.
     *
     * @Route("/{id}/delete", name="datasource_delete")
     * @Method("GET")
     * @Security("has_role('ROLE_SYSTEM_ADMIN')")
     */
    public function deleteAction($id)
    {

        $em = $this->getDoctrine()->getManager();

        /** @var DataSource $dataSource */
        $dataSource = $em->getRepository('AppBundle:DataSource')->find($id);

        if ($dataSource) {

            $personsOfDataSource = $em->getRepository('AppBundle:Person')->findOneBy(array("dataSource" => $dataSource));
            if ($personsOfDataSource != null) {
                $this->addFlash("error", 'Data Source "' . $dataSource->getName() . '" could not be deleted because there are Person-entities with reference to this entity.');
            } else {
                $em = $this->getDoctrine()->getManager();
                $em->remove($dataSource);
                $em->flush();
            }
        }

        return $this->redirectToRoute('datasource_index');
    }
}
