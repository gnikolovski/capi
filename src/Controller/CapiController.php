<?php

namespace Drupal\capi\Controller;

use Drupal\capi\Service\DataBuilderService;
use Drupal\capi\Service\PixelBuilderService;
use Drupal\capi\Service\PushService;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the CapiController class.
 */
class CapiController extends ControllerBase {

  /**
   * The Pixel builder service.
   *
   * @var \Drupal\capi\Service\PixelBuilderService
   */
  protected PixelBuilderService $pixelBuilderService;

  /**
   * The push service.
   *
   * @var \Drupal\capi\Service\PushService
   */
  protected PushService $pushService;

  /**
   * The data builder service.
   *
   * @var \Drupal\capi\Service\DataBuilderService
   */
  protected DataBuilderService $dataBuilderService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->pixelBuilderService = $container->get('capi.pixel_builder_service');
    $instance->pushService = $container->get('capi.push_service');
    $instance->dataBuilderService = $container->get('capi.data_builder_service');
    return $instance;
  }

  /**
   * Responds to the "ViewContent" event sent from JavaScript.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function viewContent(Request $request): JsonResponse {
    if ($this->pixelBuilderService->isPixelEnabled() === FALSE) {
      $message = $this->t('The request was not sent to Meta because Pixel is not enabled.');
      $this->getLogger('capi')->warning($message);
      return new JsonResponse(['message' => $message], 400);
    }

    $content = $request->getContent();
    $data = json_decode($content, TRUE);
    $product_variation_id = $data['product_variation_id'] ?? NULL;
    if (is_numeric($product_variation_id) === FALSE) {
      $message = $this->t('The request was not sent to Meta because the product_variation_id parameter is not numeric.');
      $this->getLogger('capi')->error($message);
      return new JsonResponse(['message' => $message], 400);
    }

    $product_variation = $this->entityTypeManager()
      ->getStorage('commerce_product_variation')
      ->load($product_variation_id);
    if (!$product_variation instanceof ProductVariationInterface) {
      $message = $this->t('The request was not sent to Meta because the product variation cannot be found.');
      $this->getLogger('capi')->error($message);
      return new JsonResponse(['message' => $message], 400);
    }

    $source_url = $data['source_url'] ?? NULL;
    $additional_info = [];
    if ($source_url !== NULL) {
      $additional_info = ['source_url' => $source_url];
    }
    $event = $this->dataBuilderService->getEvent($product_variation, $additional_info);
    $result = $this->pushService->push($event);

    if ($result === TRUE) {
      return new JsonResponse(['message' => 'The request has been sent to Meta.'], 200);
    }

    return new JsonResponse(['message' => 'The request failed to be sent to Meta.'], 400);
  }

}
