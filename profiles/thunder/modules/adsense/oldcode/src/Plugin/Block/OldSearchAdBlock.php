<?php

namespace Drupal\adsense_oldcode\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\adsense\AdBlockInterface;
use Drupal\adsense_oldcode\Plugin\AdsenseAd\OldSearchAd;

/**
 * Provides an AdSense Custom Search ad block.
 *
 * @Block(
 *   id = "adsense_oldsearch_ad_block",
 *   admin_label = @Translation("Old search"),
 *   category = @Translation("Adsense")
 * )
 */
class OldSearchAdBlock extends BlockBase implements AdBlockInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'ad_channel' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function createAd() {
    return new OldSearchAd(['channel' => $this->configuration['ad_channel']]);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->createAd()->display();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    // Hide block title by default.
    $form['label_display']['#default_value'] = FALSE;

    $config = \Drupal::config('adsense_search.settings');

    $channel_list = [];
    for ($channel = 1; $channel <= ADSENSE_OLDCODE_MAX_CHANNELS; $channel++) {
      $title = $config->get('adsense_ad_channel_' . $channel);
      if (!empty($title)) {
        $channel_list[$channel] = $title;
      }
    }

    $form['ad_channel'] = [
      '#type' => 'select',
      '#title' => $this->t('Channel'),
      '#default_value' => $this->configuration['ad_channel'],
      '#options' => $channel_list,
      '#empty_value' => '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['ad_channel'] = $form_state->getValue('ad_channel');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    /*return Cache::PERMANENT;*/
    return 0;
  }

}
