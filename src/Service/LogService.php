<?php

namespace Drupal\capi\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use FacebookAds\Object\ServerSide\Event;

/**
 * Defines the LogService class.
 */
class LogService {

  /**
   * Constructs a new LogService object.
   *
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   */
  public function __construct(
    protected AccountInterface $currentUser,
    protected Connection $database,
    protected TimeInterface $time,
    protected LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Inserts a log entry for provided event.
   *
   * @param \FacebookAds\Object\ServerSide\Event $event
   *   The event.
   * @param array $response_data
   *   The response data.
   */
  public function insert(Event $event, array $response_data = []): void {
    if ($this->database->schema()->tableExists('capi_log')) {
      /** @var \FacebookAds\Object\ServerSide\UserData $user_data */
      $user_data = $event->getUserData();

      $values = [
        'uid' => $this->currentUser->id(),
        'event_name' => $event->getEventName(),
        'ip_address' => $user_data->getClientIpAddress(),
        'user_agent' => $user_data->getClientUserAgent(),
        'source_url' => $event->getEventSourceUrl(),
        'fbp' => $user_data->getFbp(),
        'fbc' => $user_data->getFbc(),
        'user_data' => json_encode((array) $event->getUserData()),
        'custom_data' => json_encode((array) $event->getCustomData()),
        'event_data' => json_encode((array) $event),
        'response_data' => json_encode($response_data),
        'created' => $this->time->getRequestTime(),
      ];

      try {
        $this->database
          ->insert('capi_log')
          ->fields(array_keys($values))
          ->values($values)
          ->execute();
      }
      catch (\Exception $e) {
        $this->loggerFactory->get('capi')->error('An error occurred while inserting the event log record: @error_message', [
          '@error_message' => $e->getMessage(),
        ]);
      }
    }
  }

}
