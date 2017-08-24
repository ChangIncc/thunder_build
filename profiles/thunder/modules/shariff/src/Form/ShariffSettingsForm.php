<?php

namespace Drupal\shariff\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Component\Utility\UrlHelper;

/**
 * Provides a settings form for the Shariff sharing buttons.
 */
class ShariffSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shariff_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('shariff.settings');

    foreach (Element::children($form) as $variable) {

      $value = $form_state->getValue($form[$variable]['#parents']);

      if ($variable == 'shariff_services') {
        $value = array_filter($value);
      }

      $config->set($variable, $value);
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $backend_url = $form_state->getValue('shariff_backend_url');
    if ($backend_url && !(UrlHelper::isValid($backend_url, TRUE))) {
      $form_state->setErrorByName('shariff_backend_url', $this->t('Please enter a valid URL.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['shariff.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $settings = _shariff_get_settings();

    $form['shariff_services'] = array(
      '#title' => $this->t('Activated services'),
      '#description' => $this->t('Please define for which services a sharing button should be included.'),
      '#type' => 'checkboxes',
      '#options' => array(
        'twitter' => $this->t('Twitter'),
        'facebook' => $this->t('Facebook'),
        'googleplus' => $this->t('Google+'),
        'linkedin' => $this->t('LinkedIn'),
        'pinterest' => $this->t('Pinterest'),
        'xing' => $this->t('Xing'),
        'whatsapp' => $this->t('WhatsApp'),
        'addthis' => $this->t('AddThis'),
        'tumblr' => $this->t('Tumblr'),
        'flattr' => $this->t('Flattr'),
        'diaspora' => $this->t('Diaspora'),
        'reddit' => $this->t('reddit'),
        'stumbleupon' => $this->t('StumbleUpon'),
        'weibo' => $this->t('Weibo'),
        'tencent-weibo' => $this->t('Tencent-Weibo'),
        'qzone' => $this->t('Qzone'),
        'threema' => $this->t('Threema'),
        'mail' => $this->t('E-Mail'),
        'info' => $this->t('Info Button'),
      ),
      '#default_value' => $settings['services'],
    );

    $form['shariff_theme'] = array(
      '#title' => $this->t('Theme'),
      '#description' => $this->t('Please choose a layout option.'),
      '#type' => 'radios',
      '#options' => array(
        'colored' => $this->t('Colored'),
        'grey' => $this->t('Grey'),
        'white' => $this->t('White'),
      ),
      '#default_value' => $settings['shariff_theme'],
    );

    $form['shariff_css'] = array(
      '#title' => $this->t('CSS'),
      '#description' => $this->t('Please choose a CSS variant. Font Awesome is used to display the services icons.'),
      '#type' => 'radios',
      '#options' => array(
        'complete' => $this->t('Complete (Contains also Font Awesome)'),
        'min' => $this->t('Minimal (If Font Awesome is already included in your site)'),
        'naked' => $this->t('None (Without any CSS)'),
      ),
      '#default_value' => \Drupal::config('shariff.settings')->get('shariff_css'),
    );

    $form['shariff_orientation'] = array(
      '#title' => $this->t('Orientation'),
      '#description' => $this->t('Vertical will stack the buttons vertically. Default is horizontally.'),
      '#type' => 'radios',
      '#options' => array(
        'vertical' => $this->t('Vertical'),
        'horizontal' => $this->t('Horizontal'),
      ),
      '#default_value' => $settings['orientation'],
    );

    $form['shariff_twitter_via'] = array(
      '#title' => $this->t('Twitter Via User'),
      '#description' => $this->t('Screen name of the Twitter user to attribute the Tweets to.'),
      '#type' => 'textfield',
      '#default_value' => $settings['twitter_via'],
    );

    $form['shariff_mail_url'] = array(
      '#title' => $this->t('Mail link'),
      '#description' => $this->t('The url target used for the mail service button. Leave it as "mailto:" to let the user
 choose an email address.'),
      '#type' => 'textfield',
      '#default_value' => $settings['mail_url'] ? $settings['mail_url'] : 'mailto:',
    );

    $form['shariff_mail_subject'] = array(
      '#title' => $this->t('Mail subject'),
      '#description' => $this->t("If a mailto: link is provided in Mail link above, then this value is used as the mail subject.
 Left empty the page's current (canonical) URL or og:url is used."),
      '#type' => 'textfield',
      '#default_value' => $settings['mail_subject'],
    );

    $form['shariff_mail_body'] = array(
      '#title' => $this->t('Mail body'),
      '#description' => $this->t("If a mailto: link is provided in Mail link above, then this value is used as the mail body.
 Left empty the page title is used."),
      '#type' => 'textarea',
      '#default_value' => $settings['mail_body'],
    );

    $form['shariff_referrer_track'] = array(
      '#title' => $this->t('Referrer track code'),
      '#description' => $this->t('A string that will be appended to the share url. Disabled when empty.'),
      '#type' => 'textfield',
      '#default_value' => $settings['referrer_track'],
    );

    $form['shariff_backend_url'] = array(
      '#title' => $this->t('Backend URL'),
      '#description' => $this->t('The path to your Shariff backend. Leaving the value blank disables the backend feature and no counts will occur.'),
      '#type' => 'textfield',
      '#default_value' => $settings['backend_url'],
    );

    $form['shariff_flattr_category'] = array(
      '#title' => $this->t('Flattr category'),
      '#description' => $this->t('Category to be used for Flattr.'),
      '#type' => 'textfield',
      '#default_value' => $settings['flattr_category'],
    );

    $form['shariff_flattr_user'] = array(
      '#title' => $this->t('Flattr user'),
      '#description' => $this->t('User that receives Flattr donation.'),
      '#type' => 'textfield',
      '#default_value' => $settings['flattr_user'],
    );

    $form['shariff_media_url'] = array(
      '#title' => $this->t('Media url'),
      '#description' => $this->t('Media url to be shared (Pinterest).'),
      '#type' => 'textfield',
      '#default_value' => $settings['media_url'],
    );

    return parent::buildForm($form, $form_state);
  }

}
