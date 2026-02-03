<?php

declare(strict_types=1);

namespace Drupal\event_registration\Service;

use Drupal\event_registration\Repository\EventRepository;
use Drupal\event_registration\Repository\RegistrationRepository;

/**
 * Service for registration validation.
 */
class ValidationService
{

    /**
     * Pattern for valid text fields (no special characters).
     */
    protected const TEXT_PATTERN = '/^[a-zA-Z0-9\s\.\,\-\']+$/u';

    /**
     * Constructs a ValidationService object.
     *
     * @param \Drupal\event_registration\Repository\RegistrationRepository $registrationRepository
     *   The registration repository.
     * @param \Drupal\event_registration\Repository\EventRepository $eventRepository
     *   The event repository.
     */
    public function __construct(
        protected RegistrationRepository $registrationRepository,
        protected EventRepository $eventRepository,
    ) {
    }

    /**
     * Validates a complete registration.
     *
     * @param array $data
     *   The registration data.
     *
     * @return array
     *   An array of validation errors.
     */
    public function validateRegistration(array $data): array
    {
        $errors = [];

        // Validate required fields.
        if (empty($data['full_name'])) {
            $errors['full_name'] = t('Full name is required.');
        } elseif (!$this->validateTextField($data['full_name'])) {
            $errors['full_name'] = t('Full name contains invalid characters. Only letters, numbers, spaces, and basic punctuation are allowed.');
        }

        if (empty($data['email'])) {
            $errors['email'] = t('Email address is required.');
        } elseif (!$this->validateEmail($data['email'])) {
            $errors['email'] = t('Please enter a valid email address.');
        }

        if (empty($data['college_name'])) {
            $errors['college_name'] = t('College name is required.');
        } elseif (!$this->validateTextField($data['college_name'])) {
            $errors['college_name'] = t('College name contains invalid characters. Only letters, numbers, spaces, and basic punctuation are allowed.');
        }

        if (empty($data['department'])) {
            $errors['department'] = t('Department is required.');
        } elseif (!$this->validateTextField($data['department'])) {
            $errors['department'] = t('Department contains invalid characters. Only letters, numbers, spaces, and basic punctuation are allowed.');
        }

        if (empty($data['event_id'])) {
            $errors['event_id'] = t('Please select an event.');
        } else {
            // Validate registration window.
            if (!$this->validateRegistrationWindow((int) $data['event_id'])) {
                $errors['event_id'] = t('Registration for this event is currently closed.');
            }

            // Check for duplicate registration.
            if (!empty($data['email']) && $this->isDuplicateRegistration($data['email'], (int) $data['event_id'])) {
                $errors['email'] = t('You have already registered for this event with this email address.');
            }
        }

        return $errors;
    }

    /**
     * Validates email format.
     *
     * @param string $email
     *   The email to validate.
     *
     * @return bool
     *   TRUE if valid, FALSE otherwise.
     */
    public function validateEmail(string $email): bool
    {
        return (bool) filter_var(trim($email), FILTER_VALIDATE_EMAIL);
    }

    /**
     * Validates a text field for special characters.
     *
     * @param string $value
     *   The value to validate.
     *
     * @return bool
     *   TRUE if valid, FALSE otherwise.
     */
    public function validateTextField(string $value): bool
    {
        $trimmed = trim($value);

        if (empty($trimmed)) {
            return FALSE;
        }

        // Allow Unicode letters, numbers, spaces, and basic punctuation.
        // This pattern allows letters from any language.
        return (bool) preg_match('/^[\p{L}\p{N}\s\.\,\-\'\"]+$/u', $trimmed);
    }

    /**
     * Checks if registration is a duplicate.
     *
     * @param string $email
     *   The email address.
     * @param int $eventId
     *   The event ID.
     *
     * @return bool
     *   TRUE if duplicate exists, FALSE otherwise.
     */
    public function isDuplicateRegistration(string $email, int $eventId): bool
    {
        return $this->registrationRepository->existsByEmailAndEvent(
            strtolower(trim($email)),
            $eventId
        );
    }

    /**
     * Validates if registration window is open.
     *
     * @param int $eventId
     *   The event ID.
     *
     * @return bool
     *   TRUE if registration is open, FALSE otherwise.
     */
    public function validateRegistrationWindow(int $eventId): bool
    {
        return $this->eventRepository->isRegistrationOpen($eventId);
    }

    /**
     * Sanitizes input text.
     *
     * @param string $value
     *   The value to sanitize.
     *
     * @return string
     *   The sanitized value.
     */
    public function sanitizeText(string $value): string
    {
        // Remove any HTML tags.
        $value = strip_tags($value);

        // Trim whitespace.
        $value = trim($value);

        // Normalize whitespace.
        $value = preg_replace('/\s+/', ' ', $value);

        return $value;
    }

    /**
     * Validates a date format.
     *
     * @param string $date
     *   The date string.
     * @param string $format
     *   The expected format.
     *
     * @return bool
     *   TRUE if valid, FALSE otherwise.
     */
    public function validateDateFormat(string $date, string $format = 'Y-m-d'): bool
    {
        $dateTime = \DateTime::createFromFormat($format, $date);
        return $dateTime && $dateTime->format($format) === $date;
    }

}
