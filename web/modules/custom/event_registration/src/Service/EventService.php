<?php

declare(strict_types=1);

namespace Drupal\event_registration\Service;

use Drupal\event_registration\Repository\EventRepository;

/**
 * Service for event business logic.
 */
class EventService
{

    /**
     * Constructs an EventService object.
     *
     * @param \Drupal\event_registration\Repository\EventRepository $eventRepository
     *   The event repository.
     */
    public function __construct(
        protected EventRepository $eventRepository,
    ) {
    }

    /**
     * Creates a new event.
     *
     * @param array $data
     *   The event data.
     *
     * @return int
     *   The created event ID.
     */
    public function createEvent(array $data): int
    {
        return $this->eventRepository->create($data);
    }

    /**
     * Updates an existing event.
     *
     * @param int $id
     *   The event ID.
     * @param array $data
     *   The event data.
     *
     * @return bool
     *   TRUE if successful, FALSE otherwise.
     */
    public function updateEvent(int $id, array $data): bool
    {
        return $this->eventRepository->update($id, $data) > 0;
    }

    /**
     * Deletes an event.
     *
     * @param int $id
     *   The event ID.
     *
     * @return bool
     *   TRUE if successful, FALSE otherwise.
     */
    public function deleteEvent(int $id): bool
    {
        return $this->eventRepository->delete($id) > 0;
    }

    /**
     * Gets an event by ID.
     *
     * @param int $id
     *   The event ID.
     *
     * @return array|null
     *   The event data or NULL if not found.
     */
    public function getEvent(int $id): ?array
    {
        return $this->eventRepository->findById($id);
    }

    /**
     * Gets all events.
     *
     * @return array
     *   An array of events.
     */
    public function getAllEvents(): array
    {
        return $this->eventRepository->findAll();
    }

    /**
     * Gets available categories for public registration.
     *
     * @return array
     *   An array of category options.
     */
    public function getAvailableCategories(): array
    {
        $categories = $this->eventRepository->getCategories();
        return array_combine($categories, $categories);
    }

    /**
     * Gets all categories for admin.
     *
     * @return array
     *   An array of category options.
     */
    public function getAllCategories(): array
    {
        $categories = $this->eventRepository->getAllCategories();
        return array_combine($categories, $categories);
    }

    /**
     * Gets available event dates for a category.
     *
     * @param string $category
     *   The category.
     *
     * @return array
     *   An array of date options.
     */
    public function getAvailableDates(string $category): array
    {
        $dates = $this->eventRepository->getEventDates($category);
        $options = [];

        foreach ($dates as $date) {
            $formatted = date('F j, Y', strtotime($date));
            $options[$date] = $formatted;
        }

        return $options;
    }

    /**
     * Gets all event dates for admin.
     *
     * @param string|null $category
     *   Optional category filter.
     *
     * @return array
     *   An array of date options.
     */
    public function getAllDates(?string $category = NULL): array
    {
        $dates = $this->eventRepository->getAllEventDates($category);
        $options = [];

        foreach ($dates as $date) {
            $formatted = date('F j, Y', strtotime($date));
            $options[$date] = $formatted;
        }

        return $options;
    }

    /**
     * Gets available events for category and date.
     *
     * @param string $category
     *   The category.
     * @param string $eventDate
     *   The event date.
     *
     * @return array
     *   An array of event options (id => name).
     */
    public function getAvailableEvents(string $category, string $eventDate): array
    {
        return $this->eventRepository->getEventsByCategoryAndDate($category, $eventDate);
    }

    /**
     * Gets all event names for admin.
     *
     * @return array
     *   An array of event names keyed by ID.
     */
    public function getAllEventNames(): array
    {
        return $this->eventRepository->getEventNames();
    }

    /**
     * Checks if registration is open for an event.
     *
     * @param int $eventId
     *   The event ID.
     *
     * @return bool
     *   TRUE if registration is open.
     */
    public function isRegistrationOpen(int $eventId): bool
    {
        return $this->eventRepository->isRegistrationOpen($eventId);
    }

    /**
     * Validates event data.
     *
     * @param array $data
     *   The event data.
     *
     * @return array
     *   An array of validation errors.
     */
    public function validateEventData(array $data): array
    {
        $errors = [];

        if (empty($data['event_name'])) {
            $errors['event_name'] = t('Event name is required.');
        }

        if (empty($data['category'])) {
            $errors['category'] = t('Category is required.');
        }

        if (empty($data['registration_start'])) {
            $errors['registration_start'] = t('Registration start date is required.');
        }

        if (empty($data['registration_end'])) {
            $errors['registration_end'] = t('Registration end date is required.');
        }

        if (empty($data['event_date'])) {
            $errors['event_date'] = t('Event date is required.');
        }

        // Validate date order.
        if (!empty($data['registration_start']) && !empty($data['registration_end'])) {
            if (strtotime($data['registration_start']) > strtotime($data['registration_end'])) {
                $errors['registration_end'] = t('Registration end date must be after start date.');
            }
        }

        if (!empty($data['registration_end']) && !empty($data['event_date'])) {
            if (strtotime($data['registration_end']) > strtotime($data['event_date'])) {
                $errors['event_date'] = t('Event date must be after registration end date.');
            }
        }

        return $errors;
    }

}
