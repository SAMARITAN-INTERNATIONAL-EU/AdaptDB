<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * Class FindVulnerablePeopleType
 * @package AppBundle\Form
 */
class PeopleAddressesFilterType extends AbstractType
{
    const FILTER_ISACTIVE_ACTIVE = 1;
    const FILTER_ISACTIVE_ALL = 2;
    const FILTER_ISACTIVE_INACTIVE = 3;
    const FILTER_ISACTIVE_NOADDRESS = 4;

    const FILTER_SAFETYSTATUS_ALL = 1;
    const FILTER_SAFETYSTATUS_SAFE = 2;
    const FILTER_SAFETYSTATUS_NOTSAFE = 3;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('queryIsActive', ChoiceType::class, array(
                'mapped' => false,
                'expanded' => false,
                'label' => 'Is Active:',
                'data' => PeopleAddressesFilterType::FILTER_ISACTIVE_ALL,
                'choices' => array(
                    'Active' => PeopleAddressesFilterType::FILTER_ISACTIVE_ACTIVE,
                    'All' => PeopleAddressesFilterType::FILTER_ISACTIVE_ALL,
                    'Inactive' => PeopleAddressesFilterType::FILTER_ISACTIVE_INACTIVE,
                    'No Address' => PeopleAddressesFilterType::FILTER_ISACTIVE_NOADDRESS
                ),
            ))
            ->add('querySafetyStatus', ChoiceType::class, array(
                'mapped' => false,
                'expanded' => false,
                'label' => 'Is Active:',
                'data' => PeopleAddressesFilterType::FILTER_SAFETYSTATUS_ALL,
                'choices' => array(
                    'All' => PeopleAddressesFilterType::FILTER_SAFETYSTATUS_ALL,
                    'Safe' => PeopleAddressesFilterType::FILTER_SAFETYSTATUS_SAFE,
                    'Not Safe' => PeopleAddressesFilterType::FILTER_SAFETYSTATUS_NOTSAFE,
                ),
            ))
            ->add('queryFiscalCode', TextType::class, array('required' => false, 'mapped' => false,))
            ->add('queryFirstName', TextType::class, array('required' => false, 'mapped' => false,))
            ->add('queryLastName', TextType::class, array('required' => false, 'mapped' => false,))
            ->add('queryDateOfBirth', DateType::class, array(
                'label' => 'Date Of Birth',
                'widget' => 'single_text',
                'format' => 'dd-MM-yyyy',
                'attr' => [
                    'data-provide' => 'datepicker',
                    'data-date-format' => 'dd-mm-yyyy',
                ]
            ))
            ->add('queryAge', TextType::class, array('required' => false))
            ->add('queryAgeGrSm', TextType::class, array('required' => false))
            ->add('queryStreetName', TextType::class, array('required' => false, 'mapped' => false,))
            ->add('queryStreetNr', TextType::class, array('required' => false, 'mapped' => false,))
            ->add('queryZipcode', TextType::class, array('required' => false, 'mapped' => false,))
            ->add('queryCity', TextType::class, array('required' => false, 'mapped' => false,))
            ->add('currentPage', TextType::class, array('mapped' => false, 'empty_data' => 1))
            ->add('apply', SubmitType::class, array('label' => 'Apply'));
    }
}
