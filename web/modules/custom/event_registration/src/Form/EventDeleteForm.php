<?php

declare(strict_types=1);

namespace Drupal\event_registration\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\event_registration\Service\EventService;
use Drupal\event_registration\Service\RegistrationService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for confirming event deletion.
 */
class EventDeleteForm extends ConfirmFormBase
{

    /**
     * The event to delete.
     *
     * @var array|null
     */
    protected ?array $event = NULL;

    /**
     * The event ID.
     *
     * @var int
     */
    protected int $eventId;

    /**
     * Constructs an EventDeleteForm object.
     *
     * @param \Drupal\event_registration\Service\EventService $eventService
     *   The event service.
     * @param \Drupal\event_registration\Service\RegistrationService $registrationService
     *   The registration service.
     */
    public function __construct(
        protected EventService $eventService,
        protected RegistrationService $registrationService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('event_registration.event_service'),
            $container->get('event_registration.registration_service'),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'event_registration_event_delete_form';
    }

    /**
     * {@inheritdoc}
     */
    public function getQuestion()
    {
        return $this->t('Are you sure you want to delete the event "@name"?', [
            '@name' => $this->event['event_name'] ?? 'Unknown',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $count = $this->registrationService->getRegistrationCount($this->eventId);

        if ($count > 0) {
            return $this->t('This event has @count registration(s). Deleting this event will also delete all associated registrations. This action cannot be undone.', [
                '@count' => $count,
            ]);
        }

        return $this->t('This action cannot be undone.');
    }

    /**
     * {@inheritdoc}
     */
    public function getCancelUrl(): Url
    {
        return new Url('event_registration.admin_events');
    }

    /**
     * {@inheritdoc}
     */
    public function getConfirmText()
    {
        return $this->t('Delete');
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, ?int $event_id = NULL): array
    {
        $this->eventId = $event_id;
        $this->event = $this->eventService->getEvent($event_id);

        if (!$this->event) {
            $this->messenger()->addError($this->t('Event not found.'));
            return $this->redirect('event_registration.admin_events');
        }

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        try {
            // Delete associated registrations first.
            $deletedRegistrations = $this->registrationService->deleteRegistrationsByEvent($this->eventId);

            // Delete the event.
            $this->eventService->deleteEvent($this->eventId);

            if ($deletedRegistrations > 0) {
                $this->messenger()->addStatus($this->t('Event "@name" and @count registration(s) have been deleted.', [
                    '@name' => $this->event['event_name'],
                    '@count' => $deletedRegistrations,
                ]));
            } else {
                $this->messenger()->addStatus($this->t('Event "@name" has been deleted.', [
                    '@name' => $this->event['event_name'],
                ]));
            }
        } catch (\Exception $e) {
            $this->messenger()->addError($this->t('An error occurred while deleting the event.'));
            \Drupal::logger('event_registration')->error('Event deletion failed: @message', [
                '@message' => $e->getMessage(),
            ]);
        }

        $form_state->setRedirectUrl($this->getCancelUrl());
    }

}
