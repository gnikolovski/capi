<?php

/**
 * @file
 * Hooks specific to the Meta Conversions API module.
 */

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use FacebookAds\Object\ServerSide\CustomData;
use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\UserData;

/**
 * Alters the user data object for the "ViewContent" event.
 *
 * @param \FacebookAds\Object\ServerSide\UserData $user_data
 *   The user data.
 * @param \Drupal\commerce_product\Entity\ProductVariationInterface $product_variation
 *   The product variation.
 */
function hook_capi_view_content_user_data_alter(UserData $user_data, ProductVariationInterface $product_variation): void {
  $user_data->setFirstName('Goran');
}

/**
 * Alters the custom data object for the "ViewContent" event.
 *
 * @param \FacebookAds\Object\ServerSide\CustomData $custom_data
 *   The custom data.
 * @param \Drupal\commerce_product\Entity\ProductVariationInterface $product_variation
 *   The product variation.
 */
function hook_capi_view_content_custom_data_alter(CustomData $custom_data, ProductVariationInterface $product_variation): void {
  $custom_data->setContentName('My product 1');
}

/**
 * Alters the event data for the "ViewContent" event.
 *
 * @param \FacebookAds\Object\ServerSide\Event $event
 *   The event.
 * @param \Drupal\commerce_product\Entity\ProductVariationInterface $product_variation
 *   The product variation.
 */
function hook_capi_view_content_event_alter(Event $event, ProductVariationInterface $product_variation): void {
  $event->setEventId('Test 123');
}

/**
 * Alters the user data object for the "AddToCart" event.
 *
 * @param \FacebookAds\Object\ServerSide\UserData $user_data
 *   The user data.
 * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
 *   The order item.
 */
function hook_capi_add_to_cart_user_data_alter(UserData $user_data, OrderItemInterface $order_item): void {
  $user_data->setPhone('555-666');
}

/**
 * Alters the custom data object for the "AddToCart" event.
 *
 * @param \FacebookAds\Object\ServerSide\CustomData $custom_data
 *   The custom data.
 * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
 *   The order item.
 */
function hook_capi_add_to_cart_custom_data_alter(CustomData $custom_data, OrderItemInterface $order_item): void {
  $custom_data->setContentType('my_type');
}

/**
 * Alters the event data for the "AddToCart" event.
 *
 * @param \FacebookAds\Object\ServerSide\Event $event
 *   The event.
 * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
 *   The order item.
 */
function hook_capi_add_to_cart_event_alter(Event $event, OrderItemInterface $order_item): void {
  $event->setEventId('Test 789');
}
