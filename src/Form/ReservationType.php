<?php

namespace App\Form;

use App\Entity\Reservation;
use App\Entity\Vehicle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('vehicle', EntityType::class, [
                'label' => 'Véhicule',
                'mapped' => false,
                'data' => $options['vehicle']->getBrand(), // Affiche la marque du véhicule
                'attr' => ['readonly' => true]
            ])
            ->add('startDate', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('endDate', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('totalPrice', IntegerType::class, [
                'label' => 'Prix total (€)',
                'mapped' => false,
                'data' => 0,
                'attr' => ['readonly' => true] // Le prix est calculé et affiché, mais ne peut pas être modifié
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Réserver',
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
            'vehicle' => null, // Ajout d'une option pour passer le véhicule sélectionné
        ]);
    }
}
