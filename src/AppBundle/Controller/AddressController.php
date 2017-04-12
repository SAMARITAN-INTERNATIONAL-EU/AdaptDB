<?php

namespace AppBundle\Controller;

use AppBundle\Entity\DataChangeHistory;
use AppBundle\Entity\PersonAddress;
use AppBundle\Service\GeocoderService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use AppBundle\Entity\Address;
use AppBundle\Entity\Person;
use AppBundle\Form\AddressType;
use AppBundle\Form\PersonAddressWithoutPersonType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * Address controller.
 *
 * @package AppBundle\Controller
 * @Route("/address")
 */
class AddressController extends Controller
{

    /**
     * Creates a new Address entity.
     *
     * @Route("/new/forPersonId/{personId}", name="person_address_new")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function newAction(Request $request, $personId)
    {
        $personAddressFromForm = new PersonAddress();
        $personAddressFromForm->setIsActive(true);
        $form = $this->createForm('AppBundle\Form\PersonAddressWithoutPersonType', $personAddressFromForm);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $addressArray = array();
            $addressArray["street"] = array();
            $addressArray["street"]["name"] = $personAddressFromForm->getAddress()->getStreet()->getName();
            $addressArray["street"]["zipcode"] = array();
            $addressArray["street"]["zipcode"]["zipcode"] = $personAddressFromForm->getAddress()->getStreet()->getZipcode()->getZipcode();
            $addressArray["street"]["zipcode"]["city"] = $personAddressFromForm->getAddress()->getStreet()->getZipcode()->getCity();
            $addressArray["street"]["zipcode"]["country"] = $personAddressFromForm->getAddress()->getStreet()->getZipcode()->getCountry()->getId();
            $addressArray["houseNr"] = $personAddressFromForm->getAddress()->getHouseNr();

            $em = $this->getDoctrine()->getManager();

            /** @var Person $person */
            $person =  $em->getRepository('AppBundle:Person')->find($personId);
            $personAddressFromForm->setPerson($person);

            $geocoderService = $this->get('app.geocoder_service');
            $nominatimEmailAddress = $this->container->getParameter('nominatim_email_address');

            $persistAddressHelperService = $this->get('app.persist_address_helper_service');

            $geocodingEnabled = true;

            //Perist the personAddress and its related entities

            /** @var Address $address */
            $address = $persistAddressHelperService->persistAddress($geocoderService, $em, $addressArray, $nominatimEmailAddress, $geocodingEnabled);

            /** @var PersonAddress $newPersonAddress */
            $newPersonAddress = new PersonAddress();
            $newPersonAddress->setAddress($address);
            $newPersonAddress->setPerson($person);
            $newPersonAddress->setIsActive($personAddressFromForm->getIsActive());
            $em->persist($newPersonAddress);
            $em->flush();

            return new Response("success");
        }

        return $this->render('address/new.html.twig', array(
            //'address' => $address,
            'form' => $form->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Address entity. This is used to render the "Edit-Address" overlay on the "Person-Show"-page
     *
     * @Route("/{personAddressId}/edit", name="person_address_edit")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function editAction(Request $request, $personAddressId)
    {

        $em = $this->getDoctrine()->getManager();

        $personAddress_newFromDatabase = $em->getRepository('AppBundle:PersonAddress')->find($personAddressId);
        $addressString_newFromDatabase = $personAddress_newFromDatabase->getCompleteAddressString();

        $editForm = $this->createForm('AppBundle\Form\PersonAddressWithoutPersonType', $personAddress_newFromDatabase);

        if ($request != null) {

            //manually getting the values from POST
            $dataFromForm = $request->request->get("person_address_without_person");
            if ($dataFromForm != null) {
                if (isset($dataFromForm['address'])) {
                    $addressArray = array();
                    foreach ($dataFromForm['address'] as $key => $value) {
                        if ($key != "_token") {
                            $addressArray[$key] = $value;
                        }
                    }

                    $persistAddressHelperService = $this->get('app.persist_address_helper_service');

                    $nominatimEmailAddress = $this->container->getParameter('nominatim_email_address');

                    /** @var GeocoderService $geocoderService */
                    $geocoderService = $this->get('app.geocoder_service');

                    if (isset( $dataFromForm['geocodingEnabled']) && $dataFromForm['geocodingEnabled'] == 1) {
                        $geocodingEnabled = true;
                    } else {
                        $geocodingEnabled = false;
                    }

                    //Perist the personAddress and its related entities
                    $address = $persistAddressHelperService->persistAddress($geocoderService, $em, $addressArray, $nominatimEmailAddress , $geocodingEnabled);

                    $personAddress_newFromDatabase->setAddress($address);
                    $editForm = $this->createForm(PersonAddressWithoutPersonType::class, $personAddress_newFromDatabase);
                    $editForm->handleRequest($request);

                    //Return the form if there are errors
                    if (!$editForm->isValid()) {
                        return $this->render('address/edit.html.twig', array(
                            'edit_form' => $editForm->createView(),
                        ));
                    }

                    $addressString_personAddress = $personAddress_newFromDatabase->getCompleteAddressString();

                    $em->flush();

                    //If the old and new addresses are different create a new DataChangeHistory-item for it
                    if ($addressString_personAddress != $addressString_newFromDatabase) {

                        //Update the PersonAddress
                        $newDataChangeHistory = new DataChangeHistory();
                        $newDataChangeHistory->setPerson($personAddress_newFromDatabase->getPerson());
                        $newDataChangeHistory->setOldValue($addressString_newFromDatabase);
                        $newDataChangeHistory->setNewValue($addressString_personAddress);
                        $newDataChangeHistory->setProperty("PersonAddress.[" . $personAddress_newFromDatabase->getId() . '].Address');
                        $newDataChangeHistory->setTimestamp(new \DateTime());
                        $newDataChangeHistory->setChangedByUser($this->getUser());
                        $newDataChangeHistory->setSendEmailCronjobDone(0);
                        $em->persist($newDataChangeHistory);
                        $em->flush();
                    }

                    return new Response("success");

                }
            }
        }

        return $this->render('address/edit.html.twig', array(
            'edit_form' => $editForm->createView(),
        ));
    }

    /**
     * Deletes a Address entity.
     *
     * @Route("/{id}", name="address_delete")
     * @Method("DELETE")
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function deleteAction(Request $request, Address $address)
    {
        $form = $this->createDeleteForm($address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($address);
            $em->flush();
        }

        return $this->redirectToRoute('address_index');
    }

    /**
     * Creates a form to delete a Address entity.
     *
     * @param Address $address The Address entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Address $address)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('address_delete', array('id' => $address->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }

    /**
     * @Route("/JSON/getPersonAddressByPersonAndAddress/{personId}/{addressId}", name="json_getPersonAddressByPersonAndAddress")
     * @Method("GET")
     * @Security("has_role('ROLE_DATA_ADMIN')")
     */
    public function getPersonAddressByPersonAndAddressJSONAction($personId, $addressId)
    {

        $em = $this->getDoctrine()->getManager();

        $person =  $em->getRepository('AppBundle:Person')->find($personId);
        $address =  $em->getRepository('AppBundle:Address')->find($addressId);
        $personAddress =  $em->getRepository('AppBundle:PersonAddress')->findBy(array('person' => $person, 'address' =>$address));

        $apiHelperService = $this->get("app.api_helper_service");
        return $apiHelperService->getJsonResponseFromData($personAddress);
    }
}
