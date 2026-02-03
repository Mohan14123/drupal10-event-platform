<?php

declare(strict_types=1);

namespace Drupal\event_registration\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;

/**
 * Service for sending email notifications.
 */
class EmailService
{

    /**
     * The module name.
     */
    protected const MODULE = 'event_registration';

    /**
     * Constructs an EmailService object.
     *
     * @param \Drupal\Core\Mail\MailManagerInterface $mailManager
     *   The mail manager.
     * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
     *   The config factory.
     * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
     *   The language manager.
     * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
     *   The logger factory.
     */
    public function __construct(
        protected MailManagerInterface $mailManager,
        protected ConfigFactoryInterface $configFactory,
        protected LanguageManagerInterface $languageManager,
        protected LoggerChannelFactoryInterface $loggerFactory,
    ) {
    }

    /**
     * Sends a confirmation email to the user.
     *
     * @param array $registration
     *   The registration data.
     *
     * @return bool
     *   TRUE if sent successfully, FALSE otherwise.
     */
    public function sendUserConfirmation(array $registration): bool
    {
        $config = $this->configFactory->get('event_registration.settings');

        if (!$config->get('enable_user_notifications')) {
            return TRUE;
        }

        $to = $registration['email'];
        $langcode = $this->languageManager->getDefaultLanguage()->getId();

        $params = [
            'registration' => $registration,
            'subject' => $this->replaceTokens(
                $config->get('email_subject_user') ?: 'Registration Confirmation - [event:name]',
                $registration
            ),
        ];

        $result = $this->mailManager->mail(
            self::MODULE,
            'user_confirmation',
            $to,
            $langcode,
            $params,
            NULL,
            TRUE
        );

        if (!$result['result']) {
            $this->loggerFactory->get(self::MODULE)->error(
                'Failed to send user confirmation email to @email',
                ['@email' => $to]
            );
        }

        return (bool) $result['result'];
    }

    /**
     * Sends a notification email to the admin.
     *
     * @param array $registration
     *   The registration data.
     *
     * @return bool
     *   TRUE if sent successfully, FALSE otherwise.
     */
    public function sendAdminNotification(array $registration): bool
    {
        $config = $this->configFactory->get('event_registration.settings');

        if (!$config->get('enable_admin_notifications')) {
            return TRUE;
        }

        $adminEmail = $config->get('admin_email');

        if (empty($adminEmail)) {
            $this->loggerFactory->get(self::MODULE)->warning(
                'Admin email not configured, skipping admin notification.'
            );
            return FALSE;
        }

        $langcode = $this->languageManager->getDefaultLanguage()->getId();

        $params = [
            'registration' => $registration,
            'subject' => $this->replaceTokens(
                $config->get('email_subject_admin') ?: 'New Registration - [event:name]',
                $registration
            ),
        ];

        $result = $this->mailManager->mail(
            self::MODULE,
            'admin_notification',
            $adminEmail,
            $langcode,
            $params,
            NULL,
            TRUE
        );

        if (!$result['result']) {
            $this->loggerFactory->get(self::MODULE)->error(
                'Failed to send admin notification email to @email',
                ['@email' => $adminEmail]
            );
        }

        return (bool) $result['result'];
    }

    /**
     * Replaces tokens in a string.
     *
     * @param string $text
     *   The text with tokens.
     * @param array $registration
     *   The registration data.
     *
     * @return string
     *   The text with tokens replaced.
     */
    protected function replaceTokens(string $text, array $registration): string
    {
        $replacements = [
            '[event:name]' => $registration['event_name'] ?? '',
            '[event:category]' => $registration['category'] ?? '',
            '[event:date]' => isset($registration['event_date'])
                ? date('F j, Y', strtotime($registration['event_date']))
                : '',
            '[user:name]' => $registration['full_name'] ?? '',
            '[user:email]' => $registration['email'] ?? '',
            '[user:college]' => $registration['college_name'] ?? '',
            '[user:department]' => $registration['department'] ?? '',
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $text
        );
    }

    /**
     * Builds the user confirmation email body.
     *
     * @param array $registration
     *   The registration data.
     *
     * @return string
     *   The email body.
     */
    public function buildUserConfirmationBody(array $registration): string
    {
        $eventDate = isset($registration['event_date'])
            ? date('F j, Y', strtotime($registration['event_date']))
            : 'TBD';

        $body = [];
        $body[] = t('Dear @name,', ['@name' => $registration['full_name']]);
        $body[] = '';
        $body[] = t('Thank you for registering for our event. Your registration has been confirmed.');
        $body[] = '';
        $body[] = t('Registration Details:');
        $body[] = '-----------------------------------';
        $body[] = t('Event: @event', ['@event' => $registration['event_name']]);
        $body[] = t('Category: @category', ['@category' => $registration['category']]);
        $body[] = t('Date: @date', ['@date' => $eventDate]);
        $body[] = '';
        $body[] = t('Participant Information:');
        $body[] = '-----------------------------------';
        $body[] = t('Name: @name', ['@name' => $registration['full_name']]);
        $body[] = t('Email: @email', ['@email' => $registration['email']]);
        $body[] = t('College: @college', ['@college' => $registration['college_name']]);
        $body[] = t('Department: @department', ['@department' => $registration['department']]);
        $body[] = '';
        $body[] = t('If you have any questions, please contact us.');
        $body[] = '';
        $body[] = t('Best regards,');
        $body[] = t('Event Registration Team');

        return implode("\n", $body);
    }

    /**
     * Builds the admin notification email body.
     *
     * @param array $registration
     *   The registration data.
     *
     * @return string
     *   The email body.
     */
    public function buildAdminNotificationBody(array $registration): string
    {
        $eventDate = isset($registration['event_date'])
            ? date('F j, Y', strtotime($registration['event_date']))
            : 'TBD';

        $body = [];
        $body[] = t('A new registration has been received.');
        $body[] = '';
        $body[] = t('Event Details:');
        $body[] = '-----------------------------------';
        $body[] = t('Event: @event', ['@event' => $registration['event_name']]);
        $body[] = t('Category: @category', ['@category' => $registration['category']]);
        $body[] = t('Date: @date', ['@date' => $eventDate]);
        $body[] = '';
        $body[] = t('Participant Information:');
        $body[] = '-----------------------------------';
        $body[] = t('Name: @name', ['@name' => $registration['full_name']]);
        $body[] = t('Email: @email', ['@email' => $registration['email']]);
        $body[] = t('College: @college', ['@college' => $registration['college_name']]);
        $body[] = t('Department: @department', ['@department' => $registration['department']]);
        $body[] = '';
        $body[] = t('Registration ID: @id', ['@id' => $registration['id'] ?? 'N/A']);
        $body[] = t('Registered at: @time', ['@time' => date('F j, Y g:i A')]);

        return implode("\n", $body);
    }

}
