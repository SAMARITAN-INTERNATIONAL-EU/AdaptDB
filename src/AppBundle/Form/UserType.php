<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use AppBundle\Service\UserRole;


/**
 * Class UserType
 * @package AppBundle\Form
 */
class UserType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        /**
         * $user {AppBundle\Entity\User}
         */
        $user = $options['data'];
        if ($user) {
            $selectedRoles = $user->getRoles();
        } else {
            $selectedRoles = array();
        }

        //To set the password fields as not required for existing users
        if ($user->getId()) {
            $passwordRequired = false;
        } else {
            $passwordRequired = true;
        }

            $builder
            ->add('username', TextType::class, array('required' => true) )
            ->add('email', EmailType::class, array ('required' => true, 'label' => 'E-Mail'))
            ->add('plainPassword', RepeatedType::class, array(
                'type' => PasswordType::class,
                'invalid_message' => 'The password fields must match.',
                'options' => array('attr' => array('class' => 'password-field')),
                'required' => $passwordRequired,
                'first_options'  => array('label' => 'Password'),
                'second_options' => array('label' => 'Repeat Password'),
            ))
            ->add('enabled', CheckboxType::class, array(
                'label'    => 'User is enabled (can log in)',
                'required' => false,
            ))
            ->add('roles', ChoiceType::class, array(
                'choices'  => array(
                    'System Administrator' => UserRole::SYSTEM_ADMIN,
                    'Data Administrator' => UserRole::DATA_ADMIN,
                    'Rescue Worker' => UserRole::RESCUE_WORKER),
                'expanded' => true,
                'multiple' => true,
                'mapped' => false,
                'data' => $selectedRoles
            ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\User'
        ));
    }
}
