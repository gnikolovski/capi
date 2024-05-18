<?php

namespace Drupal\capi\Form;

use Drupal\commerce_order\AdjustmentTypeManager;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\Role;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the SettingsForm class.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The adjustment type manager.
   *
   * @var \Drupal\commerce_order\AdjustmentTypeManager
   */
  protected AdjustmentTypeManager $adjustmentTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->adjustmentTypeManager = $container->get('plugin.manager.commerce_adjustment_type');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'capi_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['capi.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('capi.settings');

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Meta Conversions API'),
      '#default_value' => $config->get('enabled'),
    ];

    $form['pixel_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Meta Pixel ID'),
      '#description' => $this->t('Your Meta Pixel ID.'),
      '#default_value' => $config->get('pixel_id'),
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Meta access token'),
      '#description' => $this->t('Your Meta access token.'),
      '#default_value' => $config->get('access_token'),
      '#required' => TRUE,
      '#maxlength' => 512,
      '#states' => [
        'visible' => [
          ':input[name="enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['product_price_calculation'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Product price calculation'),
      '#states' => [
        'visible' => [
          ':input[name="enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['product_price_calculation']['adjustment_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Adjustments'),
      '#options' => [],
      '#default_value' => $config->get('adjustment_types') ?? [],
    ];
    foreach ($this->adjustmentTypeManager->getDefinitions() as $plugin_id => $definition) {
      if ($plugin_id !== 'custom') {
        $label = $this->t('Apply @label to the calculated price', ['@label' => $definition['plural_label']]);
        $form['product_price_calculation']['adjustment_types']['#options'][$plugin_id] = $label;
      }
    }

    $form['insertion_conditions'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Insertion conditions'),
      '#states' => [
        'visible' => [
          ':input[name="enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['insertion_conditions']['role_toggle'] = [
      '#type' => 'radios',
      '#title' => $this->t('Insert the Meta Pixel for specific roles'),
      '#options' => [
        'exclude_listed' => t('All roles except the selected roles'),
        'include_listed' => t('Only the selected roles'),
      ],
      '#default_value' => $config->get('role_toggle') ?? 'exclude_listed',
    ];

    $form['insertion_conditions']['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Selected roles'),
      '#options' => $options = array_map(function ($role) {
        return $role->label();
      }, Role::loadMultiple()),
      '#default_value' => $config->get('roles') ?? [],
    ];

    $form['advanced'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Advanced'),
      '#states' => [
        'visible' => [
          ':input[name="enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['advanced']['test_events'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Test events'),
      '#description' => $this->t('Check that your events are set up correctly.'),
      '#default_value' => $config->get('test_events'),
    ];

    $form['advanced']['test_event_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Test event code'),
      '#description' => $this->t("Navigate to the Events Manager, then to the 'Test events' tab, and under 'Confirm your server’s events are set up correctly', copy your 'Test event code'."),
      '#default_value' => $config->get('test_event_code'),
      '#states' => [
        'visible' => [
          ':input[name="test_events"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['advanced']['log_events'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log events'),
      '#description' => $this->t('Log pushed event names and data, and eventually response data if available into the database.'),
      '#default_value' => $config->get('log_events'),
    ];

    $form['advanced']['push_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Push type'),
      '#description' => $this->t("Async push is recommended because it won't block the execution of code while waiting for a request to complete. The downside is that you won't be able to see the response data."),
      '#options' => [
        'regular' => $this->t('Regular push (response data will be available in the log)'),
        'async_push' => $this->t('Async push (response data will not be available in the log)'),
        'async_push_with_await' => $this->t('Async push with await (response data will be available in the log)'),
      ],
      '#default_value' => $config->get('push_type'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $form_state->cleanValues();

    $config = $this->config('capi.settings');
    $config->setData($form_state->getValues());
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
