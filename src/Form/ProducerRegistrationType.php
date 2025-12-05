<?php
namespace App\Form;

use App\Entity\ProducersInfo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;

class ProducerRegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contact_name', TextType::class, [
                'label' => 'Nom et prénom du contact',
                'attr' => ['placeholder' => 'Ex: Jean Dupont']
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email de contact',
                'attr' => ['placeholder' => 'exemple@email.com']
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse complète',
                'attr' => ['placeholder' => 'Adresse postale complète']
            ])
            ->add('phone', TelType::class, [
                'label' => 'Numéro de téléphone',
                'attr' => ['placeholder' => '06 12 34 56 78']
            ])
            ->add('siret', TextType::class, [
                'label' => 'Numéro SIRET (14 chiffres)',
                'attr' => [
                    'maxlength' => 14,
                    'placeholder' => '12345678901234'
                ]
            ])
            ->add('activity', TextareaType::class, [
                'label' => 'Description de votre activité',
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Décrivez votre activité, vos produits, votre expérience...'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProducersInfo::class,
        ]);
    }
}