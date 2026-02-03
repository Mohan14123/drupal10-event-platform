<?php

declare(strict_types=1);

namespace Drupal\event_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\event_registration\Service\EventService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for creating and editing events.
 */
class EventConfigForm extends FormBase
{

    /**
     * The event ID being edited (NULL for new events).
     *
     * @var int|null
     */
    protected ?int $eventId = NULL;

    /**
     * Constructs an EventConfigForm object.
     *
     * @param \Drupal\event_registration\Service\EventService $eventService
     *   The event service.
     */
    public function __construct(
        protected EventService $eventService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('event_registration.event_service'),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'event_registration_event_config_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, ?int $event_id = NULL): array
    {
        $this->eventId = $event_id;
        $event = NULL;

        if ($event_id) {
            $event = $this->eventService->getEvent($event_id);
            if (!$event) {
                $this->messenger()->addError($this->t('Event not found.'));
                return $this->redirect('event_registration.admin_events');
            }
        }

        $form['event_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Event Name'),
            '#required' => TRUE,
            '#maxlength' => 255,
            '#default_value' => $event['event_name'] ?? '',
            '#description' => $this->t('Enter a descriptive name for the event.'),
        ];

        // Get existing categories for autocomplete-like behavior.
        $existingCategories = $this->eventService->getAllCategories();
        $categoryOptions = array_combine($existingCategories, $existingCategories);

        $form['category_type'] = [
            '#type' => 'radios',
            '#title' => $this->t('Category'),
            '#options' => [
                'existing' => $this->t('Select existing category'),
                'new' => $this->t('Create new category'),
            ],
            '#default_value' => 'existing',
            '#required' => TRUE,
        ];

        $form['category_existing'] = [
            '#type' => 'select',
            '#title' => $this->t('Existing Category'),
            '#options' => ['' => $this->t('- Select -')] + $categoryOptions,
            '#default_value' => $event['category'] ?? '',
            '#states' => [
                'visible' => [
                    ':input[name="category_type"]' => ['value' => 'existing'],
                ],
                'required' => [
                    ':input[name="category_type"]' => ['value' => 'existing'],
                ],
            ],
        ];

        $form['category_new'] = [
            '#type' => 'textfield',
            '#title' => $this->t('New Category'),
            '#maxlength' => 100,
            '#description' => $this->t('Enter a new category name.'),
            '#states' => [
                'visible' => [
                    ':input[name="category_type"]' => ['value' => 'new'],
                ],
                'required' => [
                    ':input[name="category_type"]' => ['value' => 'new'],
                ],
            ],
        ];

        $form['dates'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Registration Dates'),
        ];

        $form['dates']['registration_start'] = [
            '#type' => 'date',
            '#title' => $this->t('Registration Start Date'),
            '#required' => TRUE,
            '#default_value' => $event['registration_start'] ?? '',
            '#description' => $this->t('The date when registration opens.'),
        ];

        $form['dates']['registration_end'] = [
            '#type' => 'date',
            '#title' => $this->t('Registration End Date'),
            '#required' => TRUE,
            '#default_value' => $event['registration_end'] ?? '',
            '#description' => $this->t('The date when registration closes.'),
        ];

        $form['event_date'] = [
            '#type' => 'date',
            '#title' => $this->t('Event Date'),
            '#required' => TRUE,
            '#default_value' => $event['event_date'] ?? '',
            '#description' => $this->t('The date when the event takes place.'),
        ];

        $form['actions'] = [
            '#type' => 'actions',
        ];

        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->eventId ? $this->t('Update Event') : $this->t('Create Event'),
            '#button_type' => 'primary',
        ];

        $form['actions']['cancel'] = [
            '#type' => 'link',
            '#title' => $this->t('Cancel'),
            '#url' => \Drupal\Core\Url::fromRoute('event_registration.admin_events'),
            '#attributes' => [
                'class' => ['button'],
            ],
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state): void
    {
        parent::validateForm($form, $form_state);

        // Determine category value.
        $categoryType = $form_state->getValue('category_type');
        $category = '';

        if ($categoryType === 'existing') {
            $category = $form_state->getValue('category_existing');
            if (empty($category)) {
                $form_state->setErrorByName('category_existing', $this->t('Please select a category.'));
            }
        } else {
            $category = $form_state->getValue('category_new');
            if (empty($category)) {
                $form_state->setErrorByName('category_new', $this->t('Please enter a category name.'));
            }
        }

        // Store the resolved category for submit handler.
        $form_state->set('resolved_category', $category);

        // Validate dates.
        $registrationStart = $form_state->getValue('registration_start');
        $registrationEnd = $form_state->getValue('registration_end');
        $eventDate = $form_state->getValue('event_date');

        if ($registrationStart && $registrationEnd) {
            if (strtotime($registrationStart) > strtotime($registrationEnd)) {
                $form_state->setErrorByName(
                    'registration_end',
                    $this->t('Registration end date must be on or after the start date.')
                );
            }
        }

        if ($registrationEnd && $eventDate) {
            if (strtotime($registrationEnd) > strtotime($eventDate)) {
                $form_state->setErrorByName(
                    'event_date',
                    $this->t('Event date must be on or after the registration end date.')
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        $data = [
            'event_name' => $form_state->getValue('event_name'),
            'category' => $form_state->get('resolved_category'),
            'registration_start' => $form_state->getValue('registration_start'),
            'registration_end' => $form_state->getValue('registration_end'),
            'event_date' => $form_state->getValue('event_date'),
        ];

        try {
            if ($this->eventId) {
                $this->eventService->updateEvent($this->eventId, $data);
                $this->messenger()->addStatus($this->t('Event "@name" has been updated.', [
                    '@name' => $data['event_name'],
                ]));
            } else {
                $this->eventService->createEvent($data);
                $this->messenger()->addStatus($this->t('Event "@name" has been created.', [
                    '@name' => $data['event_name'],
                ]));
            }

            $form_state->setRedirect('event_registration.admin_events');
        } catch (\Exception $e) {
            $this->messenger()->addError($this->t('An error occurred while saving the event. Please try again.'));
            \Drupal::logger('event_registration')->error('Event save failed: @message', [
                '@message' => $e->getMessage(),
            ]);
        }
    }

}
