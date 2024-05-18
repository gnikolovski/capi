<?php

namespace Drupal\capi\Service;

use Drupal\commerce\Context;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_order\PriceCalculatorInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use FacebookAds\Object\ServerSide\ActionSource;
use FacebookAds\Object\ServerSide\Content;
use FacebookAds\Object\ServerSide\CustomData;
use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\UserData;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the DataBuilderService class.
 */
class DataBuilderService {

  /**
   * Constructs a new DataBuilderService object.
   *
   * @param \Drupal\commerce_store\CurrentStoreInterface $currentStore
   *   The current store.
   * @param \Drupal\commerce_order\PriceCalculatorInterface $priceCalculator
   *   The price calculator.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(
    protected CurrentStoreInterface $currentStore,
    protected PriceCalculatorInterface $priceCalculator,
    protected ConfigFactoryInterface $configFactory,
    protected AccountInterface $currentUser,
    protected TimeInterface $time,
    protected ModuleHandlerInterface $moduleHandler,
    protected RequestStack $requestStack,
  ) {}

  /**
   * Gets the event.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param array $additional_info
   *   The additional params.
   *
   * @return \FacebookAds\Object\ServerSide\Event|null
   *   The event.
   */
  public function getEvent(EntityInterface $entity, array $additional_info = []): ?Event {
    if ($entity instanceof ProductVariationInterface) {
      return $this->getViewContentEvent($entity, $additional_info);
    }

    if ($entity instanceof OrderItemInterface) {
      return $this->getAddToCartEvent($entity, $additional_info);
    }

    return NULL;
  }

  /**
   * Gets the "ViewContent" event.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $product_variation
   *   The product variation.
   * @param array $additional_info
   *   The additional params.
   *
   * @return \FacebookAds\Object\ServerSide\Event
   *   The "ViewContent" event.
   */
  protected function getViewContentEvent(ProductVariationInterface $product_variation, array $additional_info = []): Event {
    $request = $this->requestStack->getCurrentRequest();
    $source_url = $request->getUri();

    if (!empty($additional_info['source_url'])) {
      $source_url = $additional_info['source_url'];
    }

    $user_data = $this->getUserData($source_url);

    $custom_data = new CustomData();
    $custom_data->setCurrency($product_variation->getPrice()->getCurrencyCode());
    $custom_data->setValue($this->getCalculatedPrice($product_variation));
    $custom_data->setContentIds([$product_variation->getSku()]);
    $custom_data->setContentType('product');
    $custom_data->setContentName($product_variation->getTitle());

    $event = new Event();
    $event->setEventName('ViewContent');
    $event->setEventTime($this->time->getRequestTime());
    $event->setEventSourceUrl($source_url);

    // Allow other modules to alter the user_data object.
    $this->moduleHandler->alter('capi_view_content_user_data', $user_data, $product_variation);

    // Allow other modules to alter the custom data object.
    $this->moduleHandler->alter('capi_view_content_custom_data', $custom_data, $product_variation);

    $event->setUserData($user_data);
    $event->setCustomData($custom_data);
    $event->setActionSource(ActionSource::WEBSITE);

    // Allow other modules to alter the event object.
    $this->moduleHandler->alter('capi_view_content_event', $event, $product_variation);

    return $event;
  }

  /**
   * Gets the "AddToCart" event.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   * @param array $additional_info
   *   The additional params.
   *
   * @return \FacebookAds\Object\ServerSide\Event
   *   The "ViewContent" event.
   */
  protected function getAddToCartEvent(OrderItemInterface $order_item, array $additional_info = []): Event {
    $request = $this->requestStack->getCurrentRequest();
    $source_url = $request->getUri();

    if (!empty($additional_info['source_url'])) {
      $source_url = $additional_info['source_url'];
    }

    $user_data = $this->getUserData($source_url);

    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $product_variation */
    $product_variation = $order_item->getPurchasedEntity();

    $contents = new Content([
      'product_id' => $product_variation->getSku(),
      'quantity' => $additional_info['quantity'],
    ]);

    $custom_data = new CustomData();
    $custom_data->setCurrency($order_item->getTotalPrice()->getCurrencyCode());
    $custom_data->setValue($this->getCalculatedPrice($product_variation));
    $custom_data->setContentIds([$product_variation->getSku()]);
    $custom_data->setContentType('product');
    $custom_data->setContentName($product_variation->getTitle());
    $custom_data->setContents([$contents]);

    $event = new Event();
    $event->setEventName('AddToCart');
    $event->setEventTime($this->time->getRequestTime());
    $event->setEventSourceUrl($source_url);

    // Allow other modules to alter the user_data object.
    $this->moduleHandler->alter('capi_add_to_cart_user_data', $user_data, $order_item);

    // Allow other modules to alter the custom data object.
    $this->moduleHandler->alter('capi_add_to_cart_custom_data', $custom_data, $order_item);

    $event->setUserData($user_data);
    $event->setCustomData($custom_data);
    $event->setActionSource(ActionSource::WEBSITE);

    // Allow other modules to alter the event object.
    $this->moduleHandler->alter('capi_add_to_cart_event', $event, $order_item);

    return $event;
  }

  /**
   * Gets the user data.
   *
   * @return \FacebookAds\Object\ServerSide\UserData
   *   The user data.
   */
  public function getUserData(string $source_url = NULL): UserData {
    $request = $this->requestStack->getCurrentRequest();

    $user_data = new UserData();
    $user_data->setClientIpAddress($request->getClientIp());
    $user_data->setClientUserAgent($request->headers->get('User-Agent'));

    $fbp = $request->cookies->get('_fbp');
    if (!empty($fbp)) {
      $user_data->setFbp($fbp);
    }

    $fbc = $request->cookies->get('_fbc');
    if (!empty($fbc)) {
      $user_data->setFbc($fbc);
    }
    elseif (!empty($source_url)) {
      parse_str($source_url, $query_params);
      $fbclid = $query_params['fbclid'] ?? NULL;
      if ($fbclid !== NULL) {
        $fbc = 'fb.1.' . $this->time->getRequestTime() . '.' . $fbclid;
        $user_data->setFbc($fbc);
      }
    }

    if ($this->currentUser->isAuthenticated()) {
      $external_id = strval($this->currentUser->id());
      $user_data->setExternalId($external_id);
      $user_data->setEmail($this->currentUser->getEmail());
    }

    return $user_data;
  }

  /**
   * Gets the calculated price.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $product_variation
   *   The product variation.
   *
   * @return float
   *   The calculated price including promotion adjustment.
   */
  protected function getCalculatedPrice(ProductVariationInterface $product_variation): float {
    $context = new Context($this->currentUser, $this->currentStore->getStore());
    $adjustment_types = $this->configFactory->get('capi.settings')->get('adjustment_types');
    $adjustment_types = $adjustment_types ? array_filter($adjustment_types) : [];

    $calculated_price = $this->priceCalculator
      ->calculate($product_variation, '1', $context, $adjustment_types)
      ->getCalculatedPrice()
      ->getNumber();

    return floatval($calculated_price);
  }

}
