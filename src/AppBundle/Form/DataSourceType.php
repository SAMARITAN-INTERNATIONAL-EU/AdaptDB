<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * Class DataSourceType
 * @package AppBundle\Form
 */
class DataSourceType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('nameShort', null, array('label' => "Abbreviation of the name for lists"))
            ->add('isOfficial', null, array('label' => "Official"))
            ->add('defaultForAutomaticUpdateForClearlyIdentifiedAddresses', null, array('label' => "Automatic Update for clearly identified addresses"))
            ->add('defaultForDetectMissingPersons', null, array('label' => "Detect Missing Persons"))
            ->add('defaultForEnableGeocoding', null, array('label' => "Geocoding enabled"))
            ->add('importKeyColumns', EntityType::class, array(
                "label" => "Key columns for data-imports",
                "expanded" => true,
                "multiple" => true,
                "class" => "AppBundle:ImportKeyColumn"))
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\DataSource'
        ));
    }
}
