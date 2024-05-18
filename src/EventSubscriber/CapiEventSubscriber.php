<?php

namespace Drupal\capi\EventSubscriber;

use Drupal\capi\Service\DataBuilderService;
use Drupal\capi\Service\PixelBuilderService;
use Drupal\capi\Service\PushService;
use Drupal\commerce_cart\Event\CartEvents;
use Drupal\commerce_cart\Event\CartOrderItemAddEvent;
use Drupal\commerce_cart\Event\CartOrderItemUpdateEvent;
use Drupal\commerce_price\Calculator;
use Drupal\commerce_product\Event\ProductEvents;
use Drupal\commerce_product\Event\ProductVariationAjaxChangeEvent;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Defines the CapiEventSubscriber class.
 */
class CapiEventSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a new CapiEventSubscriber object.
   *
   * @param \Drupal\capi\Service\DataBuilderService $dataBuilderService
   *   The data build service.
   * @param \Drupal\capi\Service\PixelBuilderService $pixelBuilderService
   *   The Pixel builder service.
   * @param \Drupal\capi\Service\PushService $pushService
   *   The push service.
   */
  public function __construct(
    protected DataBuilderService $dataBuilderService,
    protected PixelBuilderService $pixelBuilderService,
    protected PushService $pushService,
  ) {}

  /**
   * {@inheritdoc}
   *
   * @todo Once Commerce issue is resolved, use constant for checkout init.
   *
   * @see https://www.drupal.org/project/commerce/issues/3104564
   */
  public static function getSubscribedEvents() {
    return [
      ProductEvents::PRODUCT_VARIATION_AJAX_CHANGE => ['handleProductVariationChange'],
      CartEvents::CART_ORDER_ITEM_ADD => ['handleOrderItemAdd'],
      CartEvents::CART_ORDER_ITEM_UPDATE => ['handleOrderItemUpdate'],
    ];
  }

  /**
   * Handles product variation ajax change event and send "ViewContent" event.
   *
   * @param \Drupal\commerce_product\Event\ProductVariationAjaxChangeEvent $ajax_change_event
   *   The product variation ajax change event.
   */
  public function handleProductVariationChange(ProductVariationAjaxChangeEvent $ajax_change_event): void {
    if ($this->pixelBuilderService->isPixelEnabled()) {
      $product_variation = $ajax_change_event->getProductVariation();
      $product_id = $product_variation->getProductId();
      $source_url = Url::fromRoute('entity.commerce_product.canonical', ['commerce_product' => $product_id], [
        'absolute' => TRUE,
        'query' => [
          'v' => $product_variation->id(),
        ],
      ]);
      $additional_info = ['source_url' => $source_url->toString()];
      $event = $this->dataBuilderService->getEvent($product_variation, $additional_info);
      $this->pushService->push($event);
    }
  }

  /**
   * Handles order item add event.
   *
   * @param \Drupal\commerce_cart\Event\CartOrderItemAddEvent $event
   *   The order item add event.
   */
  public function handleOrderItemAdd(CartOrderItemAddEvent $event): void {
    if ($this->pixelBuilderService->isPixelEnabled()) {
      $order_item = $event->getOrderItem();
      $quantity = $event->getQuantity();
      $additional_info = [
        'quantity' => $quantity,
      ];
      $event = $this->dataBuilderService->getEvent($order_item, $additional_info);
      $this->pushService->push($event);
    }
  }

  /**
   * Handles the order item update event.
   *
   * Sends AddToCart event if user adds more quantity to existing order item.
   *
   * @param \Drupal\commerce_cart\Event\CartOrderItemUpdateEvent $event
   *   The order item update event.
   */
  public function handleOrderItemUpdate(CartOrderItemUpdateEvent $event): void {
    if ($this->pixelBuilderService->isPixelEnabled()) {
      $order_item = $event->getOrderItem();
      $order_item_quantity = $order_item->getQuantity();

      $original_order_item = $event->getOriginalOrderItem();
      $original_order_item_quantity = $original_order_item->getQuantity();

      $quantity = Calculator::subtract($order_item_quantity, $original_order_item_quantity);

      if (Calculator::compare($quantity, '0') === 1) {
        $additional_info = [
          'quantity' => $quantity,
        ];
        $event = $this->dataBuilderService->getEvent($order_item, $additional_info);
        $this->pushService->push($event);
      }
    }
  }

}
