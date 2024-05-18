<?php

namespace Drupal\capi\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Defines the PixelBuilderService class.
 */
class PixelBuilderService {

  /**
   * Pixel script code.
   */
  const FACEBOOK_PIXEL_CODE_SCRIPT = "!function(f,b,e,v,n,t,s) {if(f.fbq)return;n=f.fbq=function(){n.callMethod? n.callMethod.apply(n,arguments):n.queue.push(arguments)}; if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0'; n.queue=[];t=b.createElement(e);t.async=!0; t.src=v;s=b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t,s)}(window, document,'script', 'https://connect.facebook.net/en_US/fbevents.js'); fbq('init', '{{pixel_id}}'); fbq('track', 'PageView');";

  /**
   * Pixel noscript code.
   */
  const FACEBOOK_PIXEL_CODE_NOSCRIPT = '<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={{pixel_id}}&ev=PageView&noscript=1"/></noscript>';

  /**
   * Constructs a new PixelBuilderService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The  current user.
   * @param \Drupal\Core\Routing\AdminContext $routerAdminContext
   *   The router admin context service.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected AccountProxyInterface $currentUser,
    protected AdminContext $routerAdminContext,
  ) {}

  /**
   * Checks if the Pixel should be enabled.
   *
   * @return bool
   *   Return TRUE if the Pixel should be enabled based on conditions,
   *   otherwise return FALSE.
   */
  public function isPixelEnabled(): bool {
    // The Pixel should not be enabled for admin pages.
    if ($this->routerAdminContext->isAdminRoute()) {
      return FALSE;
    }

    // Check if the Pixel is enabled.
    $enabled = $this->configFactory
      ->get('capi.settings')
      ->get('enabled');
    if ($enabled === FALSE) {
      return FALSE;
    }

    // Check if the Pixel should be enabled based on insertion conditions for
    // roles.
    $role_toggle = $this->configFactory
      ->get('capi.settings')
      ->get('role_toggle');

    $roles = $this->configFactory
      ->get('capi.settings')
      ->get('roles');

    switch ($role_toggle) {
      case 'exclude_listed':
        foreach ($this->currentUser->getRoles() as $role) {
          if (in_array($role, $roles)) {
            return FALSE;
          }
        }
        return TRUE;

      case 'include_listed':
        foreach ($this->currentUser->getRoles() as $role) {
          if (in_array($role, $roles)) {
            return TRUE;
          }
        }
        return FALSE;

      default:
        return FALSE;
    }
  }

  /**
   * Gets the Pixel script code.
   *
   * @return string|null
   *   The Pixel script code.
   */
  public function getPixelScriptCode(): ?string {
    $pixel_id = $this->getPixelId();

    if ($pixel_id !== NULL) {
      return str_replace('{{pixel_id}}', $pixel_id, self::FACEBOOK_PIXEL_CODE_SCRIPT);
    }

    return NULL;
  }

  /**
   * Gets the Pixel noscript code.
   *
   * @return string|null
   *   The Pixel noscript code.
   */
  public function getPixelNoScriptCode(): ?string {
    $pixel_id = $this->getPixelId();

    if ($pixel_id !== NULL) {
      return str_replace('{{pixel_id}}', $pixel_id, self::FACEBOOK_PIXEL_CODE_NOSCRIPT);
    }

    return NULL;
  }

  /**
   * Gets the Pixel ID.
   *
   * @return string|null
   *   The Pixel ID.
   */
  protected function getPixelId(): ?string {
    $pixel_id = $this->configFactory
      ->get('capi.settings')
      ->get('pixel_id');
    return is_string($pixel_id) ? trim($pixel_id) : NULL;
  }

}
