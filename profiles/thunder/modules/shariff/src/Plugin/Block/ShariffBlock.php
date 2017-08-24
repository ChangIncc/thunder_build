<?php

namespace Drupal\shariff\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\Cache;

/**
 * Provides a 'shariff' block.
 *
 * @Block(
 *   id = "shariff_block",
 *   admin_label = @Translation("Shariff share buttons"),
 *   category = @Translation("Blocks"),
 * )
 */
class ShariffBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $shariff_settings = array();
    foreach ($config as $setting => $value) {
      // Only set shariff settings as variable.
      if (substr($setting, 0, strlen('shariff')) === 'shariff') {
        $shariff_settings[$setting] = $value;
      }
    }
    // Set variable when block should overwrite default settings.
    $blocksettings = (isset($config['shariff_default_settings']) && $config['shariff_default_settings']) ? NULL : $shariff_settings;
    $block = array(
      '#theme' => 'block_shariff',
      '#blocksettings' => $blocksettings,
      '#attached' => array(
        'library' => array(
          'shariff/shariff',
        ),
      ),
    );
    return $block;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    // Retrieve existing configuration for this block.
    $config = $this->getConfiguration();

    $form['shariff_default_settings'] = array(
      '#title' => $this->t('Use Shariff default settings'),
      '#description' => $this->t('When set default Shariff settings are used. Uncheck to overwrite settings here.'),
      '#type' => 'checkbox',
      '#default_value' => isset($config['shariff_default_settings']) ? $config['shariff_default_settings'] : TRUE,
    );

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
        'threema' => $this->t('Threema'),
        'mail' => $this->t('E-Mail'),
        'info' => $this->t('Info Button'),
      ),
      '#default_value' => isset($config['shariff_services']) ? $config['shariff_services'] : \Drupal::config('shariff.settings')->get('shariff_services'),
      '#states' => array(
        // Only show this field when the 'shariff_default_settings' checkbox
        // is enabled.
        'visible' => array(
          ':input[name="settings[shariff_default_settings]"]' => array('checked' => FALSE),
        ),
      ),
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
      '#default_value' => isset($config['shariff_theme']) ? $config['shariff_theme'] : \Drupal::config('shariff.settings')->get('shariff_theme'),
      '#states' => array(
        // Only show this field when the 'shariff_default_settings' checkbox
        // is enabled.
        'visible' => array(
          ':input[name="settings[shariff_default_settings]"]' => array('checked' => FALSE),
        ),
      ),
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
      '#default_value' => isset($config['shariff_css']) ? $config['shariff_css'] : \Drupal::config('shariff.settings')->get('shariff_css'),
      '#states' => array(
        // Only show this field when the 'shariff_default_settings' checkbox
        // is enabled.
        'visible' => array(
          ':input[name="settings[shariff_default_settings]"]' => array('checked' => FALSE),
        ),
      ),
    );

    $form['shariff_orientation'] = array(
      '#title' => $this->t('Orientation'),
      '#description' => $this->t('Vertical will stack the buttons vertically. Default is horizontally.'),
      '#type' => 'radios',
      '#options' => array(
        'vertical' => $this->t('Vertical'),
        'horizontal' => $this->t('Horizontal'),
      ),
      '#default_value' => isset($config['shariff_orientation']) ? $config['shariff_orientation'] : \Drupal::config('shariff.settings')->get('shariff_orientation'),
      '#states' => array(
        // Only show this field when the 'shariff_default_settings' checkbox
        // is enabled.
        'visible' => array(
          ':input[name="settings[shariff_default_settings]"]' => array('checked' => FALSE),
        ),
      ),
    );

    $form['shariff_twitter_via'] = array(
      '#title' => $this->t('Twitter Via User'),
      '#description' => $this->t('Screen name of the Twitter user to attribute the Tweets to.'),
      '#type' => 'textfield',
      '#default_value' => isset($config['shariff_twitter_via']) ? $config['shariff_twitter_via'] : \Drupal::config('shariff.settings')->get('shariff_twitter_via'),
      '#states' => array(
        // Only show this field when the 'shariff_default_settings' checkbox
        // is enabled.
        'visible' => array(
          ':input[name="settings[shariff_default_settings]"]' => array('checked' => FALSE),
        ),
      ),
    );

    $form['shariff_mail_url'] = array(
      '#title' => $this->t('Mail link'),
      '#description' => $this->t('The url target used for the mail service button. Leave it as "mailto:" to let the user
 choose an email address.'),
      '#type' => 'textfield',
      '#default_value' => isset($config['shariff_mail_url']) ? $config['shariff_mail_url'] : \Drupal::config('shariff.settings')->get('shariff_mail_url'),
      '#states' => array(
        // Only show this field when the 'shariff_default_settings' checkbox
        // is enabled.
        'visible' => array(
          ':input[name="settings[shariff_default_settings]"]' => array('checked' => FALSE),
        ),
      ),
    );

    $form['shariff_mail_subject'] = array(
      '#title' => $this->t('Mail subject'),
      '#description' => $this->t("If a mailto: link is provided in Mail link above, then this value is used as the mail subject.
 Left empty the page's current (canonical) URL or og:url is used."),
      '#type' => 'textfield',
      '#default_value' => isset($config['shariff_mail_subject']) ? $config['shariff_mail_subject'] : \Drupal::config('shariff.settings')->get('shariff_mail_subject'),
      '#states' => array(
        // Only show this field when the 'shariff_default_settings' checkbox
        // is enabled.
        'visible' => array(
          ':input[name="settings[shariff_default_settings]"]' => array('checked' => FALSE),
        ),
      ),
    );

    $form['shariff_mail_body'] = array(
      '#title' => $this->t('Mail body'),
      '#description' => $this->t("If a mailto: link is provided in Mail link above, then this value is used as the mail body.
 Left empty the page title is used."),
      '#type' => 'textarea',
      '#default_value' => isset($config['shariff_mail_body']) ? $config['shariff_mail_body'] : \Drupal::config('shariff.settings')->get('shariff_mail_body'),
      '#states' => array(
        // Only show this field when the 'shariff_default_settings' checkbox
        // is enabled.
        'visible' => array(
          ':input[name="settings[shariff_default_settings]"]' => array('checked' => FALSE),
        ),
      ),
    );

    $form['shariff_referrer_track'] = array(
      '#title' => $this->t('Referrer track code'),
      '#description' => $this->t('A string that will be appended to the share url. Disabled when empty.'),
      '#type' => 'textfield',
      '#default_value' => isset($config['shariff_referrer_track']) ? $config['shariff_referrer_track'] : \Drupal::config('shariff.settings')->get('shariff_referrer_track'),
      '#states' => array(
        // Only show this field when the 'shariff_default_settings' checkbox
        // is enabled.
        'visible' => array(
          ':input[name="settings[shariff_default_settings]"]' => array('checked' => FALSE),
        ),
      ),
    );

    $form['shariff_backend_url'] = array(
      '#title' => $this->t('Backend URL'),
      '#description' => $this->t('The path to your Shariff backend. Leaving the value blank disables the backend feature and no counts will occur.'),
      '#type' => 'textfield',
      '#default_value' => isset($config['shariff_backend_url']) ? $config['shariff_backend_url'] : \Drupal::config('shariff.settings')->get('shariff_backend_url'),
      '#states' => array(
        // Only show this field when the 'shariff_default_settings' checkbox
        // is enabled.
        'visible' => array(
          ':input[name="settings[shariff_default_settings]"]' => array('checked' => FALSE),
        ),
      ),
    );

    $form['shariff_flattr_category'] = array(
      '#title' => $this->t('Flattr category'),
      '#description' => $this->t('Category to be used for Flattr.'),
      '#type' => 'textfield',
      '#default_value' => isset($config['shariff_flattr_category']) ? $config['shariff_flattr_category'] : \Drupal::config('shariff.settings')->get('shariff_flattr_category'),
      '#states' => array(
        // Only show this field when the 'shariff_default_settings' checkbox
        // is enabled.
        'visible' => array(
          ':input[name="settings[shariff_default_settings]"]' => array('checked' => FALSE),
        ),
      ),
    );

    $form['shariff_flattr_user'] = array(
      '#title' => $this->t('Flattr user'),
      '#description' => $this->t('User that receives Flattr donation.'),
      '#type' => 'textfield',
      '#default_value' => isset($config['shariff_flattr_user']) ? $config['shariff_flattr_user'] : \Drupal::config('shariff.settings')->get('shariff_flattr_user'),
      '#states' => array(
        // Only show this field when the 'shariff_default_settings' checkbox
        // is enabled.
        'visible' => array(
          ':input[name="settings[shariff_default_settings]"]' => array('checked' => FALSE),
        ),
      ),
    );

    $form['shariff_media_url'] = array(
      '#title' => $this->t('Media url'),
      '#description' => $this->t('Media url to be shared (Pinterest).'),
      '#type' => 'textfield',
      '#default_value' => isset($config['shariff_media_url']) ? $config['shariff_media_url'] : \Drupal::config('shariff.settings')->get('shariff_media_url'),
      '#states' => array(
        // Only show this field when the 'shariff_default_settings' checkbox
        // is enabled.
        'visible' => array(
          ':input[name="settings[shariff_default_settings]"]' => array('checked' => FALSE),
        ),
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Save our custom settings when the form is submitted.
    $values = $form_state->getValues();
    $this->setConfigurationValue('shariff_default_settings', $form_state->getValue('shariff_default_settings'));
    // Only save values when default settings should be overwritten.
    if (!$form_state->getValue('shariff_default_settings')) {
      foreach ($values as $setting => $value) {
        $this->setConfigurationValue($setting, $form_state->getValue($setting));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {

    $backend_url = $form_state->getValue('shariff_backend_url');
    if ($backend_url && !(UrlHelper::isValid($backend_url, TRUE))) {
      drupal_set_message('Please enter a valid Backend URL.', 'error');
      // TODO: Get rid of drupal_set_message() when
      // https://www.drupal.org/node/2537732 is fixed.
      // setErrorByName is not working for now.
      $form_state->setErrorByName('shariff_backend_url', t('Please enter a valid URL.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // The shariff block must be cached per URL, as the URL will be shared.
    return Cache::mergeContexts(parent::getCacheContexts(), ['url']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // The block output is dependent on the shariff settings form.
    return Cache::mergeTags(parent::getCacheTags(), ['config:shariff.settings']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // When displaying share counts the block needs to be updated regularly.
    // For now we set this value to 1 hour. Todo: Make it configurable.
    return 3600;
  }

}
