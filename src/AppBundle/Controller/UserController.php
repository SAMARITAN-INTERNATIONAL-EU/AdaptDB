<?php

namespace AppBundle\Controller;

use AppBundle\Entity\AuthToken;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use AppBundle\Entity\User;
use AppBundle\Form\UserType;

/**
 * User controller.
 *
 * @package AppBundle\Controller
 * @Route("/user")
 */
class UserController extends Controller
{
    /**
     * Lists all Users with their permissions
     *
     * @Route("/", name="user_index")
     * @Method("GET")
     * @Security("has_role('ROLE_SYSTEM_ADMIN')")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $users = $em->getRepository('AppBundle:User')->findBy(array('isDeleted' => false));

        return $this->render('user/index.html.twig', array(
            'users' => $users,
        ));
    }

    /**
     * Creates a new User entity.
     *
     * @Route("/new", name="user_new")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_SYSTEM_ADMIN')")
     */
    public function newAction(Request $request)
    {
        $userManager = $this->get('fos_user.user_manager');
        /** @var User $user */
        $user = $userManager->createUser();
        $user->setEnabled(true);

        $form = $this->createForm('AppBundle\Form\UserType', $user);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            //Get the selected roles from the form and set them for the user
            $selectedRoles = $form->get('roles')->getData();
            $user->setRoles($selectedRoles);

            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/new.html.twig', array(
            'user' => $user,
            'form' => $form->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing User entity.
     *
     * @Route("/{id}/edit", name="user_edit")
     * @Method({"GET", "POST"})
     * @Security("has_role('ROLE_SYSTEM_ADMIN')")
     */
    public function editAction(Request $request, User $user)
    {


        $editForm = $this->createForm('AppBundle\Form\UserType', $user);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {

            //Get the selected roles from the form and set them for the user
            $selectedRoles = $editForm->get('roles')->getData();
            $user->setRoles($selectedRoles);

            $em = $this->getDoctrine()->getManager();
            $userFromDatabase = clone $em->getRepository('AppBundle:User')->find($user->getId());

            //Remove ROLE_USER from array before imploding because ROLE_USER isn't in the form
            //ROLE_USER is automatically added by the functions of FOSUserBundle
            $userRolesBefore = implode(",", array_diff($userFromDatabase->getRoles(), ["ROLE_USER"]));
            $userRolesAfter = implode(",", $selectedRoles);

            //Make AuthTokens exceeded when user is set to disabled
            //Or when the users password is changed (or attempted)
            //Or when roles have changed
            if (($user->isEnabled() == false) ||
                ($editForm->get("plainPassword")->getData() != "") ||
                ($userRolesAfter != $userRolesBefore)
            ) {

                $this->invalidateAuthTokensForUser($em, $user->getId());
            }

            $userManager = $this->get('fos_user.user_manager');
            $userManager->updateUser($user);

            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/edit.html.twig', array(
            'user' => $user,
            'edit_form' => $editForm->createView(),
        ));
    }

    /**
     * The action does a soft-delete - it sets the deleted flag to true
     *
     * @Route("/{id}/delete", name="user_delete")
     * @Method("GET")
     * @Security("has_role('ROLE_SYSTEM_ADMIN')")
     */
    public function deleteAction($id)
    {

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('AppBundle:User')->find($id);

        if ($user) {

            $user->setIsDeleted(true);

            //To prevent the user-login to work
            $user->setEnabled(false);

            $em->persist($user);
            $em->flush();
        }

        return $this->redirectToRoute('user_index');
    }

    private function invalidateAuthTokensForUser(EntityManager $em, $userId) {

        $authTokensOfUser = $em->getRepository('AppBundle:User')->getAuthTokensOfUser($userId);

        /** @var AuthToken $authToken */
        foreach ($authTokensOfUser as $authToken) {

            $authToken->setExceeds(new \DateTime());
            $em->persist($authToken);
        }
        $em->flush();

    }
}
