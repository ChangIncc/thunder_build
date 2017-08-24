<?php

namespace Drupal\access_unpublished\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\access_unpublished\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'access_unpublished.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('access_unpublished.settings');

    $form['hash_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hash key'),
      '#default_value' => $config->get('hash_key'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['duration'] = [
      '#type' => 'select',
      '#title' => $this->t('Lifetime'),
      '#description' => $this->t('Default lifetime of the generated access tokens.'),
      '#options' => [
        86400 => $this->t('@days Days', ['@days' => 1]),
        172800 => $this->t('@days Days', ['@days' => 2]),
        345600 => $this->t('@days Days', ['@days' => 4]),
        604800 => $this->t('@days Days', ['@days' => 7]),
        1209600 => $this->t('@days Days', ['@days' => 14]),
        -1 => $this->t('Unlimited'),
      ],
      '#default_value' => $config->get('duration'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('access_unpublished.settings')
      ->set('hash_key', $form_state->getValue('hash_key'))
      ->set('duration', $form_state->getValue('duration'))
      ->save();
  }

}
