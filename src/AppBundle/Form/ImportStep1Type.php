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
 * Class ImportStep1Type
 * @package AppBundle\Form
 */
class ImportStep1Type extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('dataSource', EntityType::class, array('class' => 'AppBundle:DataSource','label' => 'Data Source', 'required' => true, 'placeholder' => "please select...", 'empty_data'  => null,))
            ->add('csvFile', FileType::class, array('label' => 'Select CSV file'))
        ;
    }

    public function getName()
    {
        return 'importstep1type';
    }
}
