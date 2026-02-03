<?php

declare(strict_types=1);

namespace Drupal\event_registration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\event_registration\Service\EventService;
use Drupal\event_registration\Service\RegistrationService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for admin pages.
 */
class AdminController extends ControllerBase
{

    /**
     * Constructs an AdminController object.
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
     * Admin overview page.
     *
     * @return array
     *   Render array.
     */
    public function overview(): array
    {
        $events = $this->eventService->getAllEvents();
        $totalRegistrations = $this->registrationService->getTotalCount();
        $registrationCounts = $this->registrationService->getRegistrationCounts();

        $build = [
            '#type' => 'container',
            '#attributes' => ['class' => ['event-registration-admin-overview']],
        ];

        // Summary cards.
        $build['summary'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['admin-summary-cards']],
        ];

        $build['summary']['events_card'] = [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#attributes' => ['class' => ['summary-card']],
            '#value' => '<h3>' . $this->t('Total Events') . '</h3><p class="count">' . count($events) . '</p>',
        ];

        $build['summary']['registrations_card'] = [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#attributes' => ['class' => ['summary-card']],
            '#value' => '<h3>' . $this->t('Total Registrations') . '</h3><p class="count">' . $totalRegistrations . '</p>',
        ];

        // Quick links.
        $build['links'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['admin-quick-links']],
        ];

        $build['links']['manage_events'] = [
            '#type' => 'link',
            '#title' => $this->t('Manage Events'),
            '#url' => Url::fromRoute('event_registration.admin_events'),
            '#attributes' => ['class' => ['button', 'button--primary']],
        ];

        $build['links']['add_event'] = [
            '#type' => 'link',
            '#title' => $this->t('Add New Event'),
            '#url' => Url::fromRoute('event_registration.admin_event_add'),
            '#attributes' => ['class' => ['button']],
        ];

        $build['links']['dashboard'] = [
            '#type' => 'link',
            '#title' => $this->t('View Dashboard'),
            '#url' => Url::fromRoute('event_registration.admin_dashboard'),
            '#attributes' => ['class' => ['button']],
        ];

        $build['links']['settings'] = [
            '#type' => 'link',
            '#title' => $this->t('Settings'),
            '#url' => Url::fromRoute('event_registration.settings'),
            '#attributes' => ['class' => ['button']],
        ];

        $build['#attached']['library'][] = 'event_registration/admin';

        return $build;
    }

    /**
     * Event list page.
     *
     * @return array
     *   Render array.
     */
    public function eventList(): array
    {
        $events = $this->eventService->getAllEvents();
        $registrationCounts = $this->registrationService->getRegistrationCounts();

        $build = [
            '#type' => 'container',
            '#attributes' => ['class' => ['event-list-wrapper']],
        ];

        // Add event button.
        $build['actions'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['event-list-actions']],
        ];

        $build['actions']['add'] = [
            '#type' => 'link',
            '#title' => $this->t('Add New Event'),
            '#url' => Url::fromRoute('event_registration.admin_event_add'),
            '#attributes' => ['class' => ['button', 'button--primary']],
        ];

        if (empty($events)) {
            $build['empty'] = [
                '#type' => 'markup',
                '#markup' => '<p>' . $this->t('No events have been created yet.') . '</p>',
            ];
            return $build;
        }

        // Build table.
        $header = [
            $this->t('Event Name'),
            $this->t('Category'),
            $this->t('Registration Period'),
            $this->t('Event Date'),
            $this->t('Registrations'),
            $this->t('Status'),
            $this->t('Operations'),
        ];

        $rows = [];
        $today = date('Y-m-d');

        foreach ($events as $event) {
            $count = $registrationCounts[$event['id']] ?? 0;

            // Determine status.
            $status = $this->t('Upcoming');
            $statusClass = 'status-upcoming';

            if ($today < $event['registration_start']) {
                $status = $this->t('Not Open');
                $statusClass = 'status-not-open';
            } elseif ($today >= $event['registration_start'] && $today <= $event['registration_end']) {
                $status = $this->t('Open');
                $statusClass = 'status-open';
            } elseif ($today > $event['event_date']) {
                $status = $this->t('Completed');
                $statusClass = 'status-completed';
            } else {
                $status = $this->t('Closed');
                $statusClass = 'status-closed';
            }

            $operations = [
                '#type' => 'operations',
                '#links' => [
                    'edit' => [
                        'title' => $this->t('Edit'),
                        'url' => Url::fromRoute('event_registration.admin_event_edit', ['event_id' => $event['id']]),
                    ],
                    'delete' => [
                        'title' => $this->t('Delete'),
                        'url' => Url::fromRoute('event_registration.admin_event_delete', ['event_id' => $event['id']]),
                    ],
                ],
            ];

            $rows[] = [
                'data' => [
                    $event['event_name'],
                    $event['category'],
                    date('M j, Y', strtotime($event['registration_start'])) . ' - ' .
                    date('M j, Y', strtotime($event['registration_end'])),
                    date('M j, Y', strtotime($event['event_date'])),
                    $count,
                    [
                        'data' => [
                            '#type' => 'html_tag',
                            '#tag' => 'span',
                            '#value' => $status,
                            '#attributes' => ['class' => ['event-status', $statusClass]],
                        ],
                    ],
                    ['data' => $operations],
                ],
            ];
        }

        $build['table'] = [
            '#type' => 'table',
            '#header' => $header,
            '#rows' => $rows,
            '#empty' => $this->t('No events found.'),
            '#attributes' => ['class' => ['event-list-table']],
        ];

        $build['#attached']['library'][] = 'event_registration/admin';

        return $build;
    }

}
