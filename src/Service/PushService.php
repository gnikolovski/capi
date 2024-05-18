<?php

namespace Drupal\capi\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use FacebookAds\Api;
use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\EventRequest;
use FacebookAds\Object\ServerSide\EventRequestAsync;

/**
 * Defines the PushService class.
 */
class PushService {

  use StringTranslationTrait;

  /**
   * Constructs a new PushService object.
   *
   * @param \Drupal\capi\Service\LogService $logService
   *   The  log service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   */
  public function __construct(
    protected LogService $logService,
    protected ConfigFactoryInterface $configFactory,
    protected LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Push event to Meta.
   *
   * @param \FacebookAds\Object\ServerSide\Event|null $event
   *   The event.
   *
   * @return bool
   *   Return TRUE if the event is pushed to Meta; otherwise, return FALSE.
   */
  public function push(?Event $event) {
    if ($event === NULL) {
      $this->loggerFactory->get('capi')->warning('The event is empty. Nothing has been sent to Meta.');
      return FALSE;
    }

    $config = $this->configFactory->get('capi.settings');
    $pixel_id = $config->get('pixel_id');
    $access_token = $config->get('access_token');

    if (empty($pixel_id) || empty($access_token)) {
      $this->loggerFactory->get('capi')->warning('Pixel ID and/or access token are missing. Got Pixel ID: @pixel_id, access token: @access_token.', [
        '@pixel_id' => $pixel_id,
        '@access_token' => $access_token,
      ]);
      return FALSE;
    }

    Api::init(NULL, NULL, $access_token, FALSE);

    $push_type = $config->get('push_type') ?? 'async_push';

    if ($push_type === 'regular') {
      $request = new EventRequest($pixel_id);
    }
    else {
      $request = new EventRequestAsync($pixel_id);
    }

    $events[] = $event;
    $request->setEvents($events);

    if (
      $config->get('test_events') === TRUE &&
      !empty($config->get('test_event_code'))
    ) {
      $request->setTestEventCode($config->get('test_event_code'));
    }

    try {
      $response = $request->execute();

      if ($config->get('log_events') === TRUE) {
        $response_data = [];

        if ($push_type === 'regular') {
          /** @var \FacebookAds\Object\ServerSide\EventResponse $response */
          $response_data = [
            'events_received' => $response->getEventsReceived(),
            'messages' => $response->getMessages(),
            'fbtrace_id' => $response->getFbTraceId(),
            'custom_endpoint_responses' => $response->getCustomEndpointResponses(),
          ];
        }

        if ($push_type === 'async_push_with_await') {
          $promise_response = $response->wait();
          $response_data = $promise_response->getBody()->getContents();
        }

        $this->logService->insert($event, $response_data);
      }

      return TRUE;
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('capi')->error($this->t('Got an error while sending a request: @error', [
        '@error' => $e->getMessage(),
      ]));
      return FALSE;
    }
  }

}
