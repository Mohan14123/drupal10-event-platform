<?php

declare(strict_types=1);

namespace Drupal\event_registration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for configuring event registration settings.
 */
class SettingsForm extends ConfigFormBase
{

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames(): array
    {
        return ['event_registration.settings'];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'event_registration_settings_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $config = $this->config('event_registration.settings');

        $form['email_settings'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Email Settings'),
        ];

        $form['email_settings']['admin_email'] = [
            '#type' => 'email',
            '#title' => $this->t('Admin Notification Email'),
            '#default_value' => $config->get('admin_email'),
            '#description' => $this->t('Email address to receive registration notifications.'),
            '#required' => TRUE,
        ];

        $form['email_settings']['enable_user_notifications'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Enable user confirmation emails'),
            '#default_value' => $config->get('enable_user_notifications') ?? TRUE,
            '#description' => $this->t('Send confirmation emails to users after registration.'),
        ];

        $form['email_settings']['enable_admin_notifications'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Enable admin notification emails'),
            '#default_value' => $config->get('enable_admin_notifications') ?? TRUE,
            '#description' => $this->t('Send notification emails to admin when new registrations are received.'),
        ];

        $form['email_templates'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Email Templates'),
        ];

        $form['email_templates']['email_subject_user'] = [
            '#type' => 'textfield',
            '#title' => $this->t('User Confirmation Email Subject'),
            '#default_value' => $config->get('email_subject_user') ?? 'Registration Confirmation - [event:name]',
            '#description' => $this->t('Available tokens: [event:name], [event:category], [event:date], [user:name]'),
            '#maxlength' => 255,
        ];

        $form['email_templates']['email_subject_admin'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Admin Notification Email Subject'),
            '#default_value' => $config->get('email_subject_admin') ?? 'New Registration - [event:name]',
            '#description' => $this->t('Available tokens: [event:name], [event:category], [event:date], [user:name]'),
            '#maxlength' => 255,
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        $this->config('event_registration.settings')
            ->set('admin_email', $form_state->getValue('admin_email'))
            ->set('enable_user_notifications', (bool) $form_state->getValue('enable_user_notifications'))
            ->set('enable_admin_notifications', (bool) $form_state->getValue('enable_admin_notifications'))
            ->set('email_subject_user', $form_state->getValue('email_subject_user'))
            ->set('email_subject_admin', $form_state->getValue('email_subject_admin'))
            ->save();

        parent::submitForm($form, $form_state);
    }

}
