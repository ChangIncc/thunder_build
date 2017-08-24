<?php

namespace Drupal\adsense_oldcode\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\adsense\AdBlockInterface;
use Drupal\adsense_oldcode\Plugin\AdsenseAd\OldCodeAd;

/**
 * Provides an AdSense oldcode ad block.
 *
 * @Block(
 *   id = "adsense_oldcode_ad_block",
 *   admin_label = @Translation("Old code ad"),
 *   category = @Translation("Adsense")
 * )
 */
class OldCodeAdBlock extends BlockBase implements AdBlockInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'ad_format' => '250x250',
      'ad_style' => '1',
      'ad_channel' => '',
      'ad_align' => 'center',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function createAd() {
    return new OldCodeAd([
      'format' => $this->configuration['ad_format'],
      'style' => $this->configuration['ad_style'],
      'channel' => $this->configuration['ad_channel'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $classes = [];
    switch ($this->configuration['ad_align']) {
      case 'left':
        $classes[] = 'text-align-left';
        break;

      case 'center':
        $classes[] = 'text-align-center';
        break;

      case 'right':
        $classes[] = 'text-align-right';
        break;
    }
    return $this->createAd()->display($classes);
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    // Hide block title by default.
    $form['label_display']['#default_value'] = FALSE;

    $config = \Drupal::config('adsense_oldcode.settings');

    $ad_list = [];
    foreach (OldCodeAd::adsenseAdFormats() as $format => $data) {
      $ad_list[$format] = $format . ' : ' . $data['desc'];
    }

    $style_list = [];
    for ($style = 1; $style <= ADSENSE_OLDCODE_MAX_GROUPS; $style++) {
      $title = $config->get('adsense_group_title_' . $style);
      $style_list[$style] = empty($title) ? $this->t('Style @style', ['@style' => $style]) : $title;
    }

    $channel_list = [];
    for ($channel = 1; $channel <= ADSENSE_OLDCODE_MAX_CHANNELS; $channel++) {
      $title = $config->get('adsense_ad_channel_' . $channel);
      if (!empty($title)) {
        $channel_list[$channel] = $title;
      }
    }

    $form['ad_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Ad format'),
      '#default_value' => $this->configuration['ad_format'],
      '#options' => $ad_list,
      '#description' => $this->t('Select the ad dimensions you want for this block.'),
      '#required' => TRUE,
    ];

    $form['ad_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Style'),
      '#default_value' => $this->configuration['ad_style'],
      '#options' => $style_list,
    ];

    $form['ad_channel'] = [
      '#type' => 'select',
      '#title' => $this->t('Channel'),
      '#default_value' => $this->configuration['ad_channel'],
      '#options' => $channel_list,
      '#empty_value' => '',
    ];

    $form['ad_align'] = [
      '#type' => 'select',
      '#title' => $this->t('Ad alignment'),
      '#default_value' => $this->configuration['ad_align'],
      '#options' => [
        '' => $this->t('None'),
        'left' => $this->t('Left'),
        'center' => $this->t('Centered'),
        'right' => $this->t('Right'),
      ],
      '#description' => $this->t('Select the horizontal alignment of the ad within the block.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['ad_format'] = $form_state->getValue('ad_format');
    $this->configuration['ad_style'] = $form_state->getValue('ad_style');
    $this->configuration['ad_channel'] = $form_state->getValue('ad_channel');
    $this->configuration['ad_align'] = $form_state->getValue('ad_align');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    /* return Cache::PERMANENT;*/
    return 0;
  }

}
