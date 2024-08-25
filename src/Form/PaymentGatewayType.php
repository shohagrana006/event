<?php

namespace App\Form;

use App\Entity\PaymentGateway;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Vich\UploaderBundle\Form\Type\VichImageType;
use App\Form\PaymentGatewayConfigType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use A2lix\TranslationFormBundle\Form\Type\TranslationsType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class PaymentGatewayType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('name', TextType::class, [
                    'purify_html' => true,
                    'required' => true,
                    'constraints' => array(
                        new NotBlank(),
                        new Length([
                            'min' => 2,
                            'max' => 30])
                    ),
                ])
                ->add('factoryName', ChoiceType::class, [
                    'required' => true,
                    'expanded' => false,
                    'multiple' => false,
                    'label' => 'Choose a payment gateway',
                    'choices' => [
                        //'Authorize.Net AIM' => 'authorize_net_aim',
                        //'Be2Bill Direct' => 'be2bill_direct',
                        //'Be2Bill Offsite' => 'be2bill_offsite',
                        //'Klarna Checkout' => 'klarna_checkout',
                        //'Klarna Invoice' => 'klarna_invoice',
                        'Cash / Check / Bank Transfer / Other' => 'offline',
                        //'Payex' => 'payex',
                        'Paypal Express Checkout' => 'paypal_express_checkout',
                        //'Paypal Rest' => 'paypal_rest',
                        //'Paypal Pro Checkout' => 'paypal_pro_checkout',
                        //'Sofort' => 'sofort',
                        //'Stripe.js' => 'stripe_js',
                        'Stripe Checkout (credit cards)' => 'stripe_checkout',
                        // 'Hyper Switch' => 'hyper_switch'
                    ],
                    'constraints' => array(
                        new NotBlank(),
                    ),
                    'help' => "For 'Cash / Check / Bank Transfer / Other', the order will remain on the 'Awaiting payment' status until the organizer or the administrator approves it"
                ])
                ->add('translations', TranslationsType::class, [
                    'required' => false,
                    'label' => 'Instructions',
                    'fields' => [
                        'instructions' => [
                            'required' => false,
                            'field_type' => TextareaType::class,
                            'attr' => ['class' => 'wysiwyg'],
                            'locale_options' => [
                                'en' => ['label' => 'Instructions'],
                                'fr' => ['label' => 'Instructions'],
                                'es' => ['label' => 'Instrucciones'],
                                'ar' => ['label' => 'تعليمات'],
                                'pt' => ['label' => 'Instruções'],
                                'de' => ['label' => 'Anweisungen'],
                            ]
                        ],
                    ],
                ])
                ->add('gatewayLogoFile', VichImageType::class, [
                    'required' => true,
                    'allow_delete' => true,
                    'download_label' => false,
                    'download_uri' => false,
                    'image_uri' => false,
                    'imagine_pattern' => 'scale',
                    'label' => 'Image',
                    'translation_domain' => 'messages'
                ])
                ->add('enabled', ChoiceType::class, [
                    'required' => true,
                    'multiple' => false,
                    'expanded' => true,
                    'label' => 'Status',
                    'choices' => ['Disabled' => false, 'Enabled' => true],
                    'label_attr' => ['class' => 'radio-custom radio-inline']
                ])
                ->add('number', IntegerType::class, [
                    'label' => 'Order of appearance',
                    'required' => true,
                    'constraints' => array(
                        new NotBlank(),
                        new Length([
                            'min' => 1,
                            'max' => 30])),
                    'attr' => ['class' => 'touchspin-integer']
                ])
                ->add('config', PaymentGatewayConfigType::class, [
                    'label' => false,
                    'auto_initialize' => false,
                ])
                ->add('save', SubmitType::class, [
                    'label' => 'Save',
                    'attr' => ['class' => 'btn btn-primary'],
                ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => PaymentGateway::class,
            'validation_groups' => ['create', 'update']
        ]);
    }

}
