<?php

// src/Form/VehicleType.php

namespace App\Form;

use App\Entity\Vehicle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('brand', TextType::class, [
                'label' => 'Marque',
                'required' => true,
            ])
            ->add('registration', TextType::class, [
                'label' => 'Immatriculation',
                'required' => true,
            ])
            ->add('dailyPrice', NumberType::class, [
                'label' => 'Prix par jour',
                'required' => true,
            ])
            ->add('available', CheckboxType::class, [
                'label' => 'Disponible',
                'required' => false,
                'disabled' => $options['data']->isCurrentlyReserved(), // Par défaut, le véhicule est disponible
            ]);
           
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Vehicle::class, // On lie le formulaire à l'entité Vehicle
        ]);
    }
}

