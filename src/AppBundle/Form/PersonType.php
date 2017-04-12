<?php

namespace AppBundle\Form;

use FOS\UserBundle\Security\EmailUserProvider;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


/**
 * Class PersonType
 * @package AppBundle\Form
 */
class PersonType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('firstName', TextType::class, array(
                'label' => 'First Name*',
                'required' => true))
            ->add('lastName', TextType::class, array(
                'label' => 'Last Name*',
                'required' => true))
            ->add('fiscalCode', null, array(
                'label' => 'Fiscal Code',
                'required' => false))
            ->add('remarks', TextareaType::class, array
            ('required' => false
            ))
            ->add('email', EmailType::class, array(
                'required' => false,
                'label' => 'E-Mail'
            ))
            ->add('dateOfBirth', DateType::class, array(
                'required' => false,
                'label' => 'Date Of Birth',
                'widget' => 'single_text',
                'format' => 'dd-MM-yyyy',
                'attr' => [
                    'data-provide' => 'datepicker',
                    'data-date-format' => 'dd-mm-yyyy',
                    ]
            ))
            ->add('validUntil', DateType::class, array(
                'widget' => 'single_text',
                'format' => 'dd-MM-yyyy',
                'required' => false,
                'label' => 'Valid Until',
                'attr' => [
                    'data-provide' => 'datepicker',
                    'data-date-format' => 'dd-mm-yyyy',
                ]
            ))
            ->add('genderMale', ChoiceType::class, array(
                'label' => 'Gender*',
                'required' => true,
                'choices' => array(
                    'Male' => true,
                    'Female' => false),
            ))
             ->add('vulnerabilityLevel', EntityType::class, array(
                  'class' => 'AppBundle:VulnerabilityLevel',
                  'label' => 'Vulnerability Level*',
                  'required' => true,
                  'choice_label' => 'name',
              ))
            ->add('medicalRequirements', EntityType::class, array(
                'class' => 'AppBundle:MedicalRequirement',
                'label' => 'Medical Requirements',
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'required' => false
            ))
            ->add('transportRequirements', EntityType::class, array(
                'class' => 'AppBundle:transportRequirement',
                'label' => 'Transport Requirements',
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'required' => false
            ))
            ->add('landlinePhone', null, array('label' => 'Landline Phone'))
            ->add('cellPhone', null, array('label' => 'Cell Phone'));
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Person'
        ));
    }

    public function getName()
    {
        return 'person';
    }
}
