<?php

declare(strict_types=1);

namespace Drupal\event_registration\Repository;

use Drupal\Core\Database\Connection;

/**
 * Repository for event data access.
 */
class EventRepository {

  /**
   * The database table name.
   */
  protected const TABLE = 'event_registration_events';

  /**
   * Constructs an EventRepository object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(
    protected Connection $database,
  ) {}

  /**
   * Creates a new event.
   *
   * @param array $data
   *   The event data.
   *
   * @return int
   *   The ID of the created event.
   */
  public function create(array $data): int {
    $data['created'] = \Drupal::time()->getRequestTime();
    $data['updated'] = \Drupal::time()->getRequestTime();

    return (int) $this->database->insert(self::TABLE)
      ->fields($data)
      ->execute();
  }

  /**
   * Updates an existing event.
   *
   * @param int $id
   *   The event ID.
   * @param array $data
   *   The event data to update.
   *
   * @return int
   *   The number of affected rows.
   */
  public function update(int $id, array $data): int {
    $data['updated'] = \Drupal::time()->getRequestTime();

    return (int) $this->database->update(self::TABLE)
      ->fields($data)
      ->condition('id', $id)
      ->execute();
  }

  /**
   * Deletes an event.
   *
   * @param int $id
   *   The event ID.
   *
   * @return int
   *   The number of affected rows.
   */
  public function delete(int $id): int {
    return (int) $this->database->delete(self::TABLE)
      ->condition('id', $id)
      ->execute();
  }

  /**
   * Finds an event by ID.
   *
   * @param int $id
   *   The event ID.
   *
   * @return array|null
   *   The event data or NULL if not found.
   */
  public function findById(int $id): ?array {
    $result = $this->database->select(self::TABLE, 'e')
      ->fields('e')
      ->condition('id', $id)
      ->execute()
      ->fetchAssoc();

    return $result ?: NULL;
  }

  /**
   * Finds all events.
   *
   * @return array
   *   An array of events.
   */
  public function findAll(): array {
    return $this->database->select(self::TABLE, 'e')
      ->fields('e')
      ->orderBy('event_date', 'ASC')
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * Finds events by category.
   *
   * @param string $category
   *   The category to filter by.
   *
   * @return array
   *   An array of events.
   */
  public function findByCategory(string $category): array {
    return $this->database->select(self::TABLE, 'e')
      ->fields('e')
      ->condition('category', $category)
      ->orderBy('event_date', 'ASC')
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * Gets all distinct categories.
   *
   * @return array
   *   An array of category names.
   */
  public function getCategories(): array {
    $today = date('Y-m-d');
    
    return $this->database->select(self::TABLE, 'e')
      ->fields('e', ['category'])
      ->condition('registration_start', $today, '<=')
      ->condition('registration_end', $today, '>=')
      ->distinct()
      ->orderBy('category', 'ASC')
      ->execute()
      ->fetchCol();
  }

  /**
   * Gets all distinct categories including inactive events (for admin).
   *
   * @return array
   *   An array of category names.
   */
  public function getAllCategories(): array {
    return $this->database->select(self::TABLE, 'e')
      ->fields('e', ['category'])
      ->distinct()
      ->orderBy('category', 'ASC')
      ->execute()
      ->fetchCol();
  }

  /**
   * Gets event dates for a specific category within registration window.
   *
   * @param string $category
   *   The category to filter by.
   *
   * @return array
   *   An array of event dates.
   */
  public function getEventDates(string $category): array {
    $today = date('Y-m-d');
    
    return $this->database->select(self::TABLE, 'e')
      ->fields('e', ['event_date'])
      ->condition('category', $category)
      ->condition('registration_start', $today, '<=')
      ->condition('registration_end', $today, '>=')
      ->distinct()
      ->orderBy('event_date', 'ASC')
      ->execute()
      ->fetchCol();
  }

  /**
   * Gets all event dates for a category (for admin).
   *
   * @param string $category
   *   The category to filter by.
   *
   * @return array
   *   An array of event dates.
   */
  public function getAllEventDates(?string $category = NULL): array {
    $query = $this->database->select(self::TABLE, 'e')
      ->fields('e', ['event_date'])
      ->distinct()
      ->orderBy('event_date', 'ASC');

    if ($category) {
      $query->condition('category', $category);
    }

    return $query->execute()->fetchCol();
  }

  /**
   * Gets events by category and date within registration window.
   *
   * @param string $category
   *   The category to filter by.
   * @param string $eventDate
   *   The event date to filter by.
   *
   * @return array
   *   An array of events.
   */
  public function getEventsByCategoryAndDate(string $category, string $eventDate): array {
    $today = date('Y-m-d');
    
    return $this->database->select(self::TABLE, 'e')
      ->fields('e', ['id', 'event_name'])
      ->condition('category', $category)
      ->condition('event_date', $eventDate)
      ->condition('registration_start', $today, '<=')
      ->condition('registration_end', $today, '>=')
      ->orderBy('event_name', 'ASC')
      ->execute()
      ->fetchAllKeyed();
  }

  /**
   * Checks if registration is open for an event.
   *
   * @param int $eventId
   *   The event ID.
   *
   * @return bool
   *   TRUE if registration is open, FALSE otherwise.
   */
  public function isRegistrationOpen(int $eventId): bool {
    $today = date('Y-m-d');
    
    $result = $this->database->select(self::TABLE, 'e')
      ->fields('e', ['id'])
      ->condition('id', $eventId)
      ->condition('registration_start', $today, '<=')
      ->condition('registration_end', $today, '>=')
      ->execute()
      ->fetchField();

    return (bool) $result;
  }

  /**
   * Gets all event names keyed by ID.
   *
   * @return array
   *   An array of event names keyed by event ID.
   */
  public function getEventNames(): array {
    return $this->database->select(self::TABLE, 'e')
      ->fields('e', ['id', 'event_name'])
      ->orderBy('event_name', 'ASC')
      ->execute()
      ->fetchAllKeyed();
  }

}
