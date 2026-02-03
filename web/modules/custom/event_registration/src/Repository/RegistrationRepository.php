<?php

declare(strict_types=1);

namespace Drupal\event_registration\Repository;

use Drupal\Core\Database\Connection;

/**
 * Repository for registration data access.
 */
class RegistrationRepository
{

    /**
     * The database table name.
     */
    protected const TABLE = 'event_registration_registrations';

    /**
     * The events table name.
     */
    protected const EVENTS_TABLE = 'event_registration_events';

    /**
     * Constructs a RegistrationRepository object.
     *
     * @param \Drupal\Core\Database\Connection $database
     *   The database connection.
     */
    public function __construct(
        protected Connection $database,
    ) {
    }

    /**
     * Creates a new registration.
     *
     * @param array $data
     *   The registration data.
     *
     * @return int
     *   The ID of the created registration.
     */
    public function create(array $data): int
    {
        $data['created'] = \Drupal::time()->getRequestTime();

        return (int) $this->database->insert(self::TABLE)
            ->fields($data)
            ->execute();
    }

    /**
     * Finds a registration by ID.
     *
     * @param int $id
     *   The registration ID.
     *
     * @return array|null
     *   The registration data or NULL if not found.
     */
    public function findById(int $id): ?array
    {
        $result = $this->database->select(self::TABLE, 'r')
            ->fields('r')
            ->condition('id', $id)
            ->execute()
            ->fetchAssoc();

        return $result ?: NULL;
    }

    /**
     * Finds registrations by event ID.
     *
     * @param int $eventId
     *   The event ID.
     *
     * @return array
     *   An array of registrations.
     */
    public function findByEventId(int $eventId): array
    {
        return $this->database->select(self::TABLE, 'r')
            ->fields('r')
            ->condition('event_id', $eventId)
            ->orderBy('created', 'DESC')
            ->execute()
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Finds registrations with optional filters.
     *
     * @param array $filters
     *   Optional filters: event_id, event_date, category.
     *
     * @return array
     *   An array of registrations with event data.
     */
    public function findByFilters(array $filters = []): array
    {
        $query = $this->database->select(self::TABLE, 'r')
            ->fields('r')
            ->fields('e', ['event_name', 'category', 'event_date']);

        $query->join(self::EVENTS_TABLE, 'e', 'r.event_id = e.id');

        if (!empty($filters['event_id'])) {
            $query->condition('r.event_id', $filters['event_id']);
        }

        if (!empty($filters['event_date'])) {
            $query->condition('e.event_date', $filters['event_date']);
        }

        if (!empty($filters['category'])) {
            $query->condition('e.category', $filters['category']);
        }

        $query->orderBy('r.created', 'DESC');

        return $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Gets all registrations with event data.
     *
     * @return array
     *   An array of registrations with event information.
     */
    public function findAllWithEvents(): array
    {
        $query = $this->database->select(self::TABLE, 'r')
            ->fields('r')
            ->fields('e', ['event_name', 'category', 'event_date']);

        $query->join(self::EVENTS_TABLE, 'e', 'r.event_id = e.id');
        $query->orderBy('r.created', 'DESC');

        return $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Checks if a registration exists for email and event combination.
     *
     * @param string $email
     *   The email address.
     * @param int $eventId
     *   The event ID.
     *
     * @return bool
     *   TRUE if a duplicate exists, FALSE otherwise.
     */
    public function existsByEmailAndEvent(string $email, int $eventId): bool
    {
        $result = $this->database->select(self::TABLE, 'r')
            ->fields('r', ['id'])
            ->condition('email', strtolower($email))
            ->condition('event_id', $eventId)
            ->execute()
            ->fetchField();

        return (bool) $result;
    }

    /**
     * Counts registrations for an event.
     *
     * @param int $eventId
     *   The event ID.
     *
     * @return int
     *   The count of registrations.
     */
    public function countByEventId(int $eventId): int
    {
        return (int) $this->database->select(self::TABLE, 'r')
            ->condition('event_id', $eventId)
            ->countQuery()
            ->execute()
            ->fetchField();
    }

    /**
     * Gets registration counts grouped by event.
     *
     * @return array
     *   An array of counts keyed by event ID.
     */
    public function getCountsByEvent(): array
    {
        $query = $this->database->select(self::TABLE, 'r');
        $query->addField('r', 'event_id');
        $query->addExpression('COUNT(*)', 'count');
        $query->groupBy('r.event_id');

        return $query->execute()->fetchAllKeyed();
    }

    /**
     * Gets total registration count with optional filters.
     *
     * @param array $filters
     *   Optional filters.
     *
     * @return int
     *   The total count.
     */
    public function getTotalCount(array $filters = []): int
    {
        $query = $this->database->select(self::TABLE, 'r');

        if (!empty($filters['event_id'])) {
            $query->condition('r.event_id', $filters['event_id']);
        }

        if (!empty($filters['event_date']) || !empty($filters['category'])) {
            $query->join(self::EVENTS_TABLE, 'e', 'r.event_id = e.id');

            if (!empty($filters['event_date'])) {
                $query->condition('e.event_date', $filters['event_date']);
            }

            if (!empty($filters['category'])) {
                $query->condition('e.category', $filters['category']);
            }
        }

        return (int) $query->countQuery()->execute()->fetchField();
    }

    /**
     * Deletes registrations by event ID.
     *
     * @param int $eventId
     *   The event ID.
     *
     * @return int
     *   The number of deleted rows.
     */
    public function deleteByEventId(int $eventId): int
    {
        return (int) $this->database->delete(self::TABLE)
            ->condition('event_id', $eventId)
            ->execute();
    }

}
