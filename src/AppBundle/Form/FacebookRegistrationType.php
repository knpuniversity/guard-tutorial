<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FacebookRegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', 'text')
            ->add('email', 'text', array(
                'disabled' => true
            ))
            ->add('plainPassword', 'repeated', array(
                'mapped' => false, // allows this to not be a real property on User
                'type' => 'password',
                'first_options'  => array('label' => 'Password'),
                'second_options'  => array('label' => 'Password again'),
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\User'
        ));
    }

    public function getName()
    {
        return 'app_bundle_user_registration_type';
    }
}
