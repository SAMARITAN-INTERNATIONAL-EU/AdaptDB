<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
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
class FindVulnerablePeopleType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('queryFirstName', TextType::class, array('required' => false))
            ->add('queryLastName', TextType::class, array('required' => false))
            ->add('queryAge', TextType::class, array('required' => false))
            ->add('queryAgeGrSm', TextType::class, array('required' => false))
            ->add('queryStreet', TextType::class, array('required' => false))
            ->add('queryStreetNumber', TextType::class, array('required' => false))
            ->add('queryZip', TextType::class, array('required' => false))
            ->add('queryCity', TextType::class, array('required' => false))
            ->add('queryFloor', TextType::class, array('required' => false))
            ->add('queryFloorGrSm', TextType::class, array('required' => false))
            ->add('queryRemarks', TextType::class, array('required' => false))
            ->add('showAllEntities', HiddenType::class, array('data' => 0,))
            ->add('orderValue', HiddenType::class, array())
            ->add('orderKey', HiddenType::class, array())
            ->add('findMode', HiddenType::class, array('data' => 'map'))
            ->add('resultPageInitialView', HiddenType::class, array('data' => 'street'))
            ->add('findPageInitialView', HiddenType::class, array('data' => 'step1'))
            ->add('customGeoAreasArray', HiddenType::class, array('mapped' => false))
            ->add('activeGeoAreaIdsArray', HiddenType::class, array('mapped' => false))
            ->add('currentPage', HiddenType::class, array('mapped' => false, 'empty_data' => 1))
            ->add('apply', SubmitType::class, array('label' => 'Apply'))
            ->add('submit', SubmitType::class, array('label' => 'Find Vulnerable People'));

        if (isset($options['data'])) {

            $vulnerabilityLevels = $options['data']['vulnerabilityLevels'];
            $medicalRequirements = $options['data']['medicalRequirements'];
            $transportRequirements = $options['data']['transportRequirements'];
            $safetyStatus = $options['data']['safetyStatus'];
            $selectedStreetIds = $options['data']['selectedStreetIds'];
            $streetListIds = $options['data']['streetListIds'];

            $builder->add('selectedStreetIds', HiddenType::class, array('data' => $selectedStreetIds));
            $builder->add('streetListIds', HiddenType::class, array('data' => $streetListIds));

            $builder->add('vulnerabilityLevel', EntityType::class, array(
                'class' => 'AppBundle:VulnerabilityLevel',
                'label' => 'Select vulnerability level to include into results:',
                'choice_label' => 'name',
                'expanded' => true,
                'multiple' => true,
                'data' => $vulnerabilityLevels
            ))
            ->add('medicalRequirements', EntityType::class, array(
                'class' => 'AppBundle:MedicalRequirement',
                'label' => 'Select medical requirements to include into results:',
                'choice_label' => 'name',
                'expanded' => true,
                'multiple' => true,
                'data' => $medicalRequirements,
            ))
            ->add('transportRequirements', EntityType::class, array(
                'class' => 'AppBundle:TransportRequirement',
                'label' => 'Select transport requirements to include into results:',
                'choice_label' => 'name',
                'expanded' => true,
                'multiple' => true,
                'data' => $transportRequirements
            ))
            ->add('safetyStatus', ChoiceType::class, array(
                'expanded' => false,
                'label' => 'Select safety status to include into results:',
                'choices' => array('Only NOT yet safe' => 'notSafe', 'All' => 'all'),
                'data' => $safetyStatus,
            ));
        } else {
            $builder->add('selectedStreetIds', HiddenType::class, array());
            $builder->add('streetListIds', HiddenType::class, array());

            $builder->add('vulnerabilityLevel', EntityType::class, array(
                'class' => 'AppBundle:VulnerabilityLevel',
                'label' => 'Select vulnerability level to include into results:',
                'choice_label' => 'name',
                'expanded' => true,
                'multiple' => true,
            ))
            ->add('medicalRequirements', EntityType::class, array(
                'class' => 'AppBundle:MedicalRequirement',
                'label' => 'Select medical requirements to include into results:',
                'choice_label' => 'name',
                'expanded' => true,
                'multiple' => true,
            ))
            ->add('transportRequirements', EntityType::class, array(
                'class' => 'AppBundle:TransportRequirement',
                'label' => 'Select transport requirements to include into results:',
                'choice_label' => 'name',
                'expanded' => true,
                'multiple' => true,
            ))
            ->add('safetyStatus', ChoiceType::class, array(
                'expanded' => false,
                'label' => 'Select safety status to include into results:',
                'choices' => array('Only NOT yet safe' => 'notSafe', 'All' => 'all'),
            ));
        }
    }
}
