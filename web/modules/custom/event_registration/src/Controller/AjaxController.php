<?php

declare(strict_types=1);

namespace Drupal\event_registration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\event_registration\Service\EventService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for AJAX endpoints.
 */
class AjaxController extends ControllerBase
{

    /**
     * Constructs an AjaxController object.
     *
     * @param \Drupal\event_registration\Service\EventService $eventService
     *   The event service.
     */
    public function __construct(
        protected EventService $eventService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('event_registration.event_service'),
        );
    }

    /**
     * Returns available dates for a category.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The request object.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON response with dates.
     */
    public function getDates(Request $request): JsonResponse
    {
        $category = $request->query->get('category');

        if (empty($category)) {
            return new JsonResponse([
                'success' => FALSE,
                'message' => 'Category is required.',
                'data' => [],
            ]);
        }

        $dates = $this->eventService->getAvailableDates($category);

        return new JsonResponse([
            'success' => TRUE,
            'data' => $dates,
        ]);
    }

    /**
     * Returns available events for a category and date.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The request object.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON response with events.
     */
    public function getEvents(Request $request): JsonResponse
    {
        $category = $request->query->get('category');
        $eventDate = $request->query->get('event_date');

        if (empty($category) || empty($eventDate)) {
            return new JsonResponse([
                'success' => FALSE,
                'message' => 'Category and event date are required.',
                'data' => [],
            ]);
        }

        $events = $this->eventService->getAvailableEvents($category, $eventDate);

        return new JsonResponse([
            'success' => TRUE,
            'data' => $events,
        ]);
    }

}
