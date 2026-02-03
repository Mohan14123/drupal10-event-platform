<?php

declare(strict_types=1);

namespace Drupal\event_registration\Service;

use Drupal\event_registration\Repository\EventRepository;
use Drupal\event_registration\Repository\RegistrationRepository;

/**
 * Service for registration business logic.
 */
class RegistrationService
{

    /**
     * Constructs a RegistrationService object.
     *
     * @param \Drupal\event_registration\Repository\RegistrationRepository $registrationRepository
     *   The registration repository.
     * @param \Drupal\event_registration\Repository\EventRepository $eventRepository
     *   The event repository.
     * @param \Drupal\event_registration\Service\ValidationService $validationService
     *   The validation service.
     * @param \Drupal\event_registration\Service\EmailService $emailService
     *   The email service.
     */
    public function __construct(
        protected RegistrationRepository $registrationRepository,
        protected EventRepository $eventRepository,
        protected ValidationService $validationService,
        protected EmailService $emailService,
    ) {
    }

    /**
     * Processes a new registration.
     *
     * @param array $data
     *   The registration data.
     *
     * @return array
     *   Result array with 'success' boolean and either 'id' or 'errors'.
     */
    public function processRegistration(array $data): array
    {
        // Validate the registration data.
        $errors = $this->validationService->validateRegistration($data);

        if (!empty($errors)) {
            return [
                'success' => FALSE,
                'errors' => $errors,
            ];
        }

        // Normalize email.
        $data['email'] = strtolower(trim($data['email']));

        // Create the registration.
        try {
            $registrationId = $this->registrationRepository->create([
                'full_name' => trim($data['full_name']),
                'email' => $data['email'],
                'college_name' => trim($data['college_name']),
                'department' => trim($data['department']),
                'event_id' => (int) $data['event_id'],
            ]);

            // Get event details for email.
            $event = $this->eventRepository->findById((int) $data['event_id']);

            // Prepare registration data for emails.
            $registrationData = [
                'id' => $registrationId,
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'college_name' => $data['college_name'],
                'department' => $data['department'],
                'event_name' => $event['event_name'] ?? '',
                'category' => $event['category'] ?? '',
                'event_date' => $event['event_date'] ?? '',
            ];

            // Send confirmation emails.
            $this->emailService->sendUserConfirmation($registrationData);
            $this->emailService->sendAdminNotification($registrationData);

            return [
                'success' => TRUE,
                'id' => $registrationId,
                'message' => t('Registration successful! A confirmation email has been sent to @email.', [
                    '@email' => $data['email'],
                ]),
            ];
        } catch (\Exception $e) {
            \Drupal::logger('event_registration')->error('Registration failed: @message', [
                '@message' => $e->getMessage(),
            ]);

            return [
                'success' => FALSE,
                'errors' => ['general' => t('An error occurred while processing your registration. Please try again.')],
            ];
        }
    }

    /**
     * Gets a registration by ID.
     *
     * @param int $id
     *   The registration ID.
     *
     * @return array|null
     *   The registration data or NULL.
     */
    public function getRegistration(int $id): ?array
    {
        return $this->registrationRepository->findById($id);
    }

    /**
     * Gets registrations for an event.
     *
     * @param int $eventId
     *   The event ID.
     *
     * @return array
     *   An array of registrations.
     */
    public function getRegistrationsByEvent(int $eventId): array
    {
        return $this->registrationRepository->findByEventId($eventId);
    }

    /**
     * Gets registrations with filters.
     *
     * @param array $filters
     *   Optional filters.
     *
     * @return array
     *   An array of registrations.
     */
    public function getRegistrations(array $filters = []): array
    {
        if (empty($filters)) {
            return $this->registrationRepository->findAllWithEvents();
        }

        return $this->registrationRepository->findByFilters($filters);
    }

    /**
     * Gets registration count for an event.
     *
     * @param int $eventId
     *   The event ID.
     *
     * @return int
     *   The count.
     */
    public function getRegistrationCount(int $eventId): int
    {
        return $this->registrationRepository->countByEventId($eventId);
    }

    /**
     * Gets registration counts grouped by event.
     *
     * @return array
     *   An array of counts keyed by event ID.
     */
    public function getRegistrationCounts(): array
    {
        return $this->registrationRepository->getCountsByEvent();
    }

    /**
     * Gets total registration count with filters.
     *
     * @param array $filters
     *   Optional filters.
     *
     * @return int
     *   The total count.
     */
    public function getTotalCount(array $filters = []): int
    {
        return $this->registrationRepository->getTotalCount($filters);
    }

    /**
     * Deletes registrations for an event.
     *
     * @param int $eventId
     *   The event ID.
     *
     * @return int
     *   The number of deleted registrations.
     */
    public function deleteRegistrationsByEvent(int $eventId): int
    {
        return $this->registrationRepository->deleteByEventId($eventId);
    }

    /**
     * Exports registrations to CSV format.
     *
     * @param array $filters
     *   Optional filters.
     *
     * @return string
     *   The CSV content.
     */
    public function exportToCsv(array $filters = []): string
    {
        $registrations = $this->getRegistrations($filters);

        $output = fopen('php://temp', 'r+');

        // Write header row.
        fputcsv($output, [
            'ID',
            'Full Name',
            'Email',
            'College Name',
            'Department',
            'Event Name',
            'Category',
            'Event Date',
            'Registration Date',
        ]);

        // Write data rows.
        foreach ($registrations as $registration) {
            fputcsv($output, [
                $registration['id'],
                $registration['full_name'],
                $registration['email'],
                $registration['college_name'],
                $registration['department'],
                $registration['event_name'] ?? '',
                $registration['category'] ?? '',
                isset($registration['event_date']) ? date('Y-m-d', strtotime($registration['event_date'])) : '',
                date('Y-m-d H:i:s', $registration['created']),
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

}
