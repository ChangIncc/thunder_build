<?php

namespace Drupal\riddle_marketplace\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures riddle_marketplace settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'riddle_marketplace_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = $this->config('riddle_marketplace.settings');

    $form['token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Riddle token'),
      '#description' => $this->t('Register a new account at <a href=":riddle" target="_blank">riddle.com</a> and get a token from the Account->Plugins page (you may need to reset to get the first token). To get a free riddle basic account use this voucher "THUNDER_3eX4_freebasic".',
        [':riddle' => 'http://www.riddle.com']),
      '#default_value' => $settings->get('riddle_marketplace.token'),
    ];

    $form['fetch_unpublished'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Fetch unpublished riddles'),
      '#default_value' => $settings->get('riddle_marketplace.fetch_unpublished'),
      '#description' => $this->t('If checked, all riddles will be fetched from the API, not only published ones'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $values = $form_state->getValues();
    $config = $this->configFactory()->getEditable('riddle_marketplace.settings');
    $config->set('riddle_marketplace.token', $values['token'])
      ->set('riddle_marketplace.fetch_unpublished', $values['fetch_unpublished'])
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'riddle_marketplace.settings',
    ];
  }

}
