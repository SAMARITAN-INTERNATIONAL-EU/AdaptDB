<?php

namespace AppBundle\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ImportStep2Type
 * @package AppBundle\Form
 */
class ImportStep2Type extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('dataSource', EntityType::class, array('class' => 'AppBundle:DataSource','label' => 'Data Source', 'required' => true, 'placeholder' => "please select...", 'empty_data'  => null,))
//            ->add('csvFile', FileType::class, array('label' => 'Select CSV file'))
            ->add('csvFilePath', HiddenType::class)
            ->add('csvClientFileName', HiddenType::class)
            ->add('enableGeocoding', CheckboxType::class, array('label' => 'Enable Geocoding', 'required' => false))
            ->add('detectMissingPersons', CheckboxType::class, array('label' => 'Detect Missing Persons', 'required' => false))
            ->add('automaticUpdateForClearlyIdentifiedAddresses', CheckboxType::class, array('label' => "Automatic Update for clearly identified addresses", 'required' => false))
            ->add('useGeoPointsWhenAvailable', CheckboxType::class, array('label' => "Use Geo-Points from import when available", 'required' => false))
        ;
    }

    public function getName()
    {
        return 'importstep2type';
    }
}
