<?php

declare(strict_types=1);

namespace Drupal\event_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\event_registration\Service\EventService;
use Drupal\event_registration\Service\RegistrationService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Public event registration form.
 */
class RegistrationForm extends FormBase
{

    /**
     * Constructs a RegistrationForm object.
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
        return 'event_registration_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form['#prefix'] = '<div id="registration-form-wrapper">';
        $form['#suffix'] = '</div>';

        // Get available categories.
        $categories = $this->eventService->getAvailableCategories();

        if (empty($categories)) {
            $form['no_events'] = [
                '#type' => 'markup',
                '#markup' => '<div class="messages messages--warning">' .
                    $this->t('There are currently no events open for registration.') .
                    '</div>',
            ];
            return $form;
        }

        // Category selection.
        $form['category'] = [
            '#type' => 'select',
            '#title' => $this->t('Category'),
            '#options' => ['' => $this->t('- Select Category -')] + $categories,
            '#required' => TRUE,
            '#ajax' => [
                'callback' => '::updateDateOptions',
                'wrapper' => 'event-date-wrapper',
                'event' => 'change',
            ],
        ];

        // Build date options based on selected category.
        $selectedCategory = $form_state->getValue('category');
        $dateOptions = [];

        if (!empty($selectedCategory)) {
            $dateOptions = $this->eventService->getAvailableDates($selectedCategory);
        }

        // Event date selection.
        $form['event_date'] = [
            '#type' => 'select',
            '#title' => $this->t('Event Date'),
            '#options' => ['' => $this->t('- Select Date -')] + $dateOptions,
            '#required' => TRUE,
            '#prefix' => '<div id="event-date-wrapper">',
            '#suffix' => '</div>',
            '#validated' => TRUE,
            '#ajax' => [
                'callback' => '::updateEventOptions',
                'wrapper' => 'event-name-wrapper',
                'event' => 'change',
            ],
        ];

        // Build event options based on selected category and date.
        $selectedDate = $form_state->getValue('event_date');
        $eventOptions = [];

        if (!empty($selectedCategory) && !empty($selectedDate)) {
            $eventOptions = $this->eventService->getAvailableEvents($selectedCategory, $selectedDate);
        }

        // Event selection.
        $form['event_id'] = [
            '#type' => 'select',
            '#title' => $this->t('Event Name'),
            '#options' => ['' => $this->t('- Select Event -')] + $eventOptions,
            '#required' => TRUE,
            '#prefix' => '<div id="event-name-wrapper">',
            '#suffix' => '</div>',
            '#validated' => TRUE,
        ];

        // Personal information fieldset.
        $form['personal_info'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Personal Information'),
        ];

        $form['personal_info']['full_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Full Name'),
            '#required' => TRUE,
            '#maxlength' => 255,
            '#attributes' => [
                'placeholder' => $this->t('Enter your full name'),
            ],
        ];

        $form['personal_info']['email'] = [
            '#type' => 'email',
            '#title' => $this->t('Email Address'),
            '#required' => TRUE,
            '#maxlength' => 255,
            '#attributes' => [
                'placeholder' => $this->t('Enter your email address'),
            ],
        ];

        $form['personal_info']['college_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('College/Institution Name'),
            '#required' => TRUE,
            '#maxlength' => 255,
            '#attributes' => [
                'placeholder' => $this->t('Enter your college or institution name'),
            ],
        ];

        $form['personal_info']['department'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Department'),
            '#required' => TRUE,
            '#maxlength' => 255,
            '#attributes' => [
                'placeholder' => $this->t('Enter your department'),
            ],
        ];

        $form['actions'] = [
            '#type' => 'actions',
        ];

        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Register'),
            '#button_type' => 'primary',
        ];

        $form['#attached']['library'][] = 'event_registration/registration-form';

        return $form;
    }

    /**
     * AJAX callback to update date options.
     *
     * @param array $form
     *   The form array.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The form state.
     *
     * @return \Drupal\Core\Ajax\AjaxResponse
     *   The AJAX response.
     */
    public function updateDateOptions(array &$form, FormStateInterface $form_state): AjaxResponse
    {
        $response = new AjaxResponse();

        // Reset event_date and event_id values.
        $form['event_date']['#value'] = '';
        $form['event_id']['#value'] = '';
        $form['event_id']['#options'] = ['' => $this->t('- Select Event -')];

        $response->addCommand(new ReplaceCommand('#event-date-wrapper', $form['event_date']));
        $response->addCommand(new ReplaceCommand('#event-name-wrapper', $form['event_id']));

        return $response;
    }

    /**
     * AJAX callback to update event options.
     *
     * @param array $form
     *   The form array.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The form state.
     *
     * @return \Drupal\Core\Ajax\AjaxResponse
     *   The AJAX response.
     */
    public function updateEventOptions(array &$form, FormStateInterface $form_state): AjaxResponse
    {
        $response = new AjaxResponse();

        // Reset event_id value.
        $form['event_id']['#value'] = '';

        $response->addCommand(new ReplaceCommand('#event-name-wrapper', $form['event_id']));

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state): void
    {
        parent::validateForm($form, $form_state);

        // Additional validation is handled by RegistrationService.
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        $data = [
            'full_name' => $form_state->getValue('full_name'),
            'email' => $form_state->getValue('email'),
            'college_name' => $form_state->getValue('college_name'),
            'department' => $form_state->getValue('department'),
            'event_id' => $form_state->getValue('event_id'),
        ];

        $result = $this->registrationService->processRegistration($data);

        if ($result['success']) {
            $this->messenger()->addStatus($result['message']);
            $form_state->setRedirect('event_registration.register');
        } else {
            foreach ($result['errors'] as $field => $message) {
                if ($field === 'general') {
                    $this->messenger()->addError($message);
                } else {
                    $form_state->setErrorByName($field, $message);
                }
            }
        }
    }

}
