<?php

namespace AppBundle\Form;

use AppBundle\AppBundle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * Class PersonAddressType
 * @package AppBundle\Form
 */
class PersonAddressType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('address', AddressType::class )
            ->add('person', PersonType::class)
            ->add('isActive', null, array(
                'label' => 'Is Active?',
                'required' => false,
            ))
            ->add('floor', null, array())

            ->add('absenceFrom', DateType::class, array(
                'required' => false,
                'label' => "Absence From",
                'widget' => 'single_text',
                'format' => 'dd-MM-yyyy',
                'attr' => [
                    'data-provide' => 'datepicker',
                    'data-date-format' => 'dd-mm-yyyy',
                ]
            ))
            ->add('absenceTo', DateType::class, array(
                'required' => false,
                'label' => "Absence To",
                'widget' => 'single_text',
                'format' => 'dd-MM-yyyy',
                'attr' => [
                    'data-provide' => 'datepicker',
                    'data-date-format' => 'dd-mm-yyyy',
                ]
            ))
            ->add('remarks', TextareaType::class, array('required' => false));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\PersonAddress',
        ));
    }
}
