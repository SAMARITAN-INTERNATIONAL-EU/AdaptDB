<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * Class ContactPersonType
 * @package AppBundle\Form
 */
class ContactPersonType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName', null, array('label' => 'First Name*'))
            ->add('lastName', null, array('label' => 'Last Name*'))
            ->add('remarks', TextareaType::class, array('required' => false))
            ->add('person', EntityType::class, array(
                'class' => 'AppBundle:Person',
                'choice_label' => 'firstname',
                'choice_value' => 'id',
            ))
            ->add('phone');
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\ContactPerson'
        ));
    }
}
