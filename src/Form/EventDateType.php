<?php

namespace App\Form;

use App\Entity\EventDate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use App\Service\AppServices;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Entity\Venue;
use App\Entity\Scanner;
use App\Entity\PointOfSale;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Form\EventTicketType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class EventDateType extends AbstractType {

    private $services;
    private $user;
    private $entityManager;

    public function __construct(AppServices $services, TokenStorageInterface $tokenStorage, EntityManagerInterface $entityManager) {
        $this->services = $services;
        $this->user = $tokenStorage->getToken()->getUser();
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {

        $choices = [];

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $nowFormatted = $now->format('Y-m-d H:i:s');

        $sqlMeetingList = "SELECT id, topic FROM event_zoom_meeting_list WHERE org_id = :org_id AND end_date > :now";
        $statementMeetingList = $this->entityManager->getConnection()->prepare($sqlMeetingList);
        if(!$this->user->hasRole('ROLE_ADMINISTRATOR') || !$this->user->hasRole('ROLE_SUPER_ADMIN') ){
            $statementMeetingList->bindValue(':org_id', $this->user->getID());
        }else{
            $statementMeetingList->bindValue(':org_id', '');
        }
        $statementMeetingList->bindValue(':now', $nowFormatted);
        // Assuming $org_id is the value you want to filter by
        $statementMeetingList->execute();
        $meeting_lists = $statementMeetingList->fetchAll();
        foreach ($meeting_lists as $meeting) {
            $choices[$meeting['topic']] = $meeting['id'];
        }

        $builder
                ->add('active', ChoiceType::class, [
                    'required' => true,
                    'multiple' => false,
                    'expanded' => true,
                    'label' => 'Enable sales for this event date ?',
                    'choices' => ['Yes' => true, 'No' => false],
                    'attr' => ['class' => 'is-event-date-active'],
                    'label_attr' => ['class' => 'radio-custom radio-inline'],
                    'help' => 'Enabling sales for an event date does not affect the tickets individual sale status'
                ])
                ->add('startdate', DateTimeType::class, [
                    'required' => true,
                    'label' => 'Starts On',
                    'widget' => 'single_text',
                    'html5' => false,
                    'attr' => ['class' => 'datetimepicker']
                ])
                ->add('enddate', DateTimeType::class, [
                    'required' => false,
                    'label' => 'Ends On',
                    'widget' => 'single_text',
                    'html5' => false,
                    'attr' => ['class' => 'datetimepicker']
                ])
                // ->add('New', ChoiceType::class, [
                //     'multiple' => false,
                //     'expanded' => true,
                //     'label' => false,
                //     'choices' => ['Whatsapp' => false, 'Zoom' => true, 'Teams' => 2],
                //     'attr' => ['class' => 'is-new-component'],
                //     'label_attr' => ['class' => 'radio-custom radio-inline']
                // ])
                // ->add('online', ChoiceType::class, [
                //     'required' => true,
                //     'multiple' => false,
                //     'expanded' => true,
                //     'label' => 'Is this event date online ?',
                //     'choices' => ['Yes' => true, 'No' => false],
                //     'attr' => ['class' => 'is-event-date-online'],
                //     'label_attr' => ['class' => 'radio-custom radio-inline']
                // ])

                // ->add('online', ChoiceType::class, [
                //     'required' => true,
                //     'multiple' => false,
                //     'expanded' => true,
                //     'label' => 'Is this event date online ?',
                //     'choices' => ['No' => false, 'Yes' => true],
                //     'attr' => ['class' => 'is-event-date-online'],
                //     'label_attr' => ['class' => 'radio-custom radio-inline']
                // ])

                ->add('online', ChoiceType::class, [
                    'required' => true,
                    'multiple' => false,
                    'expanded' => true,
                    'label' => 'Is this event date online ?',
                    'choices' => ['No' => false, 'Yes' => true],
                    'attr' => ['class' => 'is-event-date-online'],
                    'label_attr' => ['class' => 'radio-custom radio-inline']
                ]);

                // ->add('venue', EntityType::class, [
                //     'required' => false,
                //     'class' => Venue::class,
                //     'choice_label' => 'name',
                //     'label' => 'Venue',
                //     'attr' => ['class' => 'event-date-venue'],
                //     'query_builder' => function () {
                //         return $this->services->getVenues(array("organizer" => $this->user->getOrganizer()->getSlug()));
                //     },
                // ]);

                // $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($builder, $choices) {

                //     $form = $event->getForm();
                //     $data = $event->getData();

                //     // Check if the 'online' field is set to false
                //     if ($data && !$data->getOnline()) {
                //         $form->remove('meetingLink'); // Remove the 'Meeting Schedule' field
                //     } else {
                //         // Add 'Meeting Schedule' field if it's not present
                //         $form->add('meetingLink', ChoiceType::class, [
                //             'required' => false,
                //             'choices' => $choices,
                //             'placeholder' => 'Select a Meeting Schedule',
                //             'label' => 'Meeting Schedule',
                //             'attr' => ['class' => 'meeting-link-select'], // Add this class
                //             'help' => "If your event is online you must be set the meeting link",
                //         ]);
                //     }
                // });

                $builder->add('venue', EntityType::class, [
                    'required' => true,
                    'class' => Venue::class,
                    'choice_label' => 'name',
                    'label' => 'Venue',
                    'attr' => ['class' => 'event-date-venue'],
                    'query_builder' => function () {
                        return $this->services->getVenues(array("organizer" => $this->user->getOrganizer()->getSlug()));
                    },
                ]);

                $builder->add('meetingLink', ChoiceType::class, [
                    'required' => false,
                    'choices' => $choices,
                    'placeholder' => 'Select a Meeting Schedule',
                    'label' => 'Meeting Schedule',
                    'help' => "If your event is online you must be set the meeting link",
                    'attr' => ['class' => 'meeting-link-select'],
                ]);

                $builder->add('scanners', EntityType::class, [
                    'required' => false,
                    'multiple' => true,
                    'expanded' => false,
                    'class' => Scanner::class,
                    'choice_label' => 'name',
                    'label' => 'Scanners',
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('s')
                                ->where('s.organizer = :organizer')
                                ->leftJoin('s.user', 'user')
                                ->andWhere('user.enabled = :enabled')
                                ->setParameter('organizer', $this->user->getOrganizer())
                                ->setParameter('enabled', true)
                        ;
                    },
                    'attr' => ['class' => 'select2']
                ])
                ->add('pointofsales', EntityType::class, [
                    'required' => false,
                    'multiple' => true,
                    'expanded' => false,
                    'class' => PointOfSale::class,
                    'choice_label' => 'name',
                    'label' => 'Points of sale',
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('p')
                                ->where('p.organizer = :organizer')
                                ->leftJoin('p.user', 'user')
                                ->andWhere('user.enabled = :enabled')
                                ->setParameter('organizer', $this->user->getOrganizer())
                                ->setParameter('enabled', true)
                        ;
                    },
                    'attr' => ['class' => 'select2']
                ])
                ->add('tickets', CollectionType::class, array(
                    'label' => 'Event tickets',
                    'entry_type' => EventTicketType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'prototype' => true,
                    'prototype_name' => '__eventticket__',
                    'required' => true,
                    'by_reference' => false,
                    'attr' => array(
                        'class' => 'form-collection eventtickets-collection manual-init',
                    ),
                ))
                // Set automatically on entity creation (generation function on entity class),
                // added here as a trick to identity the event date on the form to disable the wrapping
                // fieldset when payout request is pending on approved
                ->add('reference', HiddenType::class, [
                    'attr' => [
                        'class' => 'event-date-reference']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => EventDate::class,
            'validation_groups' => ['create', 'update']
        ]);
    }

}

