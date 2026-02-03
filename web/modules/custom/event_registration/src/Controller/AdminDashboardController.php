<?php

declare(strict_types=1);

namespace Drupal\event_registration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\event_registration\Service\EventService;
use Drupal\event_registration\Service\RegistrationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for the admin dashboard.
 */
class AdminDashboardController extends ControllerBase
{

    /**
     * Constructs an AdminDashboardController object.
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
     * Dashboard page.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The request object.
     *
     * @return array
     *   Render array.
     */
    public function dashboard(Request $request): array
    {
        // Get filter values from query parameters.
        $eventDate = $request->query->get('event_date', '');
        $eventId = $request->query->get('event_id', '');

        $filters = [];
        if (!empty($eventDate)) {
            $filters['event_date'] = $eventDate;
        }
        if (!empty($eventId)) {
            $filters['event_id'] = (int) $eventId;
        }

        // Get data.
        $registrations = $this->registrationService->getRegistrations($filters);
        $totalCount = count($registrations);
        $eventDates = $this->eventService->getAllDates();
        $eventNames = $this->eventService->getAllEventNames();

        $build = [
            '#type' => 'container',
            '#attributes' => ['class' => ['dashboard-wrapper']],
        ];

        // Summary section.
        $build['summary'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['dashboard-summary']],
        ];

        $build['summary']['count'] = [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#attributes' => ['class' => ['participant-count']],
            '#value' => '<h2>' . $this->t('Participants: @count', ['@count' => $totalCount]) . '</h2>',
        ];

        // Filter form.
        $build['filters'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['dashboard-filters']],
        ];

        $build['filters']['form'] = [
            '#type' => 'html_tag',
            '#tag' => 'form',
            '#attributes' => [
                'method' => 'get',
                'class' => ['filter-form'],
            ],
        ];

        // Event date filter.
        $dateOptions = '<option value="">' . $this->t('- All Dates -') . '</option>';
        foreach ($eventDates as $date => $label) {
            $selected = ($date === $eventDate) ? ' selected' : '';
            $dateOptions .= '<option value="' . $date . '"' . $selected . '>' . $label . '</option>';
        }

        $build['filters']['form']['event_date'] = [
            '#type' => 'html_tag',
            '#tag' => 'select',
            '#attributes' => [
                'name' => 'event_date',
                'class' => ['filter-select'],
            ],
            '#value' => $dateOptions,
        ];

        // Event name filter.
        $eventOptions = '<option value="">' . $this->t('- All Events -') . '</option>';
        foreach ($eventNames as $id => $name) {
            $selected = ((string) $id === $eventId) ? ' selected' : '';
            $eventOptions .= '<option value="' . $id . '"' . $selected . '>' . htmlspecialchars($name) . '</option>';
        }

        $build['filters']['form']['event_id'] = [
            '#type' => 'html_tag',
            '#tag' => 'select',
            '#attributes' => [
                'name' => 'event_id',
                'class' => ['filter-select'],
            ],
            '#value' => $eventOptions,
        ];

        $build['filters']['form']['submit'] = [
            '#type' => 'html_tag',
            '#tag' => 'button',
            '#attributes' => [
                'type' => 'submit',
                'class' => ['button', 'button--primary'],
            ],
            '#value' => $this->t('Filter'),
        ];

        $build['filters']['form']['reset'] = [
            '#type' => 'link',
            '#title' => $this->t('Reset'),
            '#url' => Url::fromRoute('event_registration.admin_dashboard'),
            '#attributes' => ['class' => ['button']],
        ];

        // Export button.
        $exportUrl = Url::fromRoute('event_registration.export_csv', [], [
            'query' => $filters,
        ]);

        $build['filters']['export'] = [
            '#type' => 'link',
            '#title' => $this->t('Export CSV'),
            '#url' => $exportUrl,
            '#attributes' => ['class' => ['button', 'export-button']],
        ];

        // Registrations table.
        if (empty($registrations)) {
            $build['table'] = [
                '#type' => 'markup',
                '#markup' => '<p class="no-results">' . $this->t('No registrations found.') . '</p>',
            ];
        } else {
            $header = [
                $this->t('ID'),
                $this->t('Name'),
                $this->t('Email'),
                $this->t('College'),
                $this->t('Department'),
                $this->t('Event'),
                $this->t('Category'),
                $this->t('Event Date'),
                $this->t('Registered'),
            ];

            $rows = [];
            foreach ($registrations as $registration) {
                $rows[] = [
                    $registration['id'],
                    htmlspecialchars($registration['full_name']),
                    htmlspecialchars($registration['email']),
                    htmlspecialchars($registration['college_name']),
                    htmlspecialchars($registration['department']),
                    htmlspecialchars($registration['event_name'] ?? ''),
                    htmlspecialchars($registration['category'] ?? ''),
                    isset($registration['event_date']) ? date('M j, Y', strtotime($registration['event_date'])) : '',
                    date('M j, Y g:i A', $registration['created']),
                ];
            }

            $build['table'] = [
                '#type' => 'table',
                '#header' => $header,
                '#rows' => $rows,
                '#attributes' => ['class' => ['registrations-table']],
            ];
        }

        $build['#attached']['library'][] = 'event_registration/admin-dashboard';

        return $build;
    }

    /**
     * AJAX filter callback.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The request object.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON response with filtered data.
     */
    public function ajaxFilter(Request $request): JsonResponse
    {
        $eventDate = $request->query->get('event_date', '');
        $eventId = $request->query->get('event_id', '');

        $filters = [];
        if (!empty($eventDate)) {
            $filters['event_date'] = $eventDate;
        }
        if (!empty($eventId)) {
            $filters['event_id'] = (int) $eventId;
        }

        $registrations = $this->registrationService->getRegistrations($filters);

        return new JsonResponse([
            'success' => TRUE,
            'count' => count($registrations),
            'data' => $registrations,
        ]);
    }

    /**
     * Export registrations to CSV.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The request object.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *   CSV download response.
     */
    public function exportCsv(Request $request): Response
    {
        $eventDate = $request->query->get('event_date', '');
        $eventId = $request->query->get('event_id', '');

        $filters = [];
        if (!empty($eventDate)) {
            $filters['event_date'] = $eventDate;
        }
        if (!empty($eventId)) {
            $filters['event_id'] = (int) $eventId;
        }

        $csv = $this->registrationService->exportToCsv($filters);

        $filename = 'event-registrations-' . date('Y-m-d-His') . '.csv';

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }

}
