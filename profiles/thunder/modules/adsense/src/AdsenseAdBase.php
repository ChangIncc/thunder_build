<?php

namespace Drupal\adsense;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class AdsenseAdBase.
 */
abstract class AdsenseAdBase extends PluginBase implements AdsenseAdInterface {
  use StringTranslationTrait;

  /**
   * Ad type.
   *
   * @var int
   */
  protected $type;

  /**
   * {@inheritdoc}
   */
  public static function createAd(array $args) {
    $is_search = (!empty($args['format']) && ($args['format'] == 'Search Box'));
    $needs_slot = !empty($args['slot']);

    // Search for the AdsenseAd plugins.
    /** @var AdsenseAdManager $manager */
    $manager = \Drupal::service('plugin.manager.adsensead');
    $plugins = $manager->getDefinitions();

    foreach ($plugins as $plugin) {
      if (($plugin['isSearch'] == $is_search) && ($plugin['needsSlot'] == $needs_slot)) {
        // Return an ad created by the compatible plugin.
        return $manager->createInstance($plugin['id'], $args);
      }
    }
    return NULL;
  }

  /**
   * Display ad HTML.
   *
   * @param array $classes
   *   Set of classes to add to the ad HTML.
   *
   * @return array
   *   render array with ad or placeholder depending on current configuration.
   */
  public function display(array $classes = []) {
    $account = \Drupal::currentUser();
    $config = \Drupal::config('adsense.settings');
    $libraries = ['adsense/adsense.css'];
    $text = '';

    if ($this->isDisabled($text)) {
    }
    elseif (!$this->canInsertAnother()) {
      $text = 'ad limit reached for type.';
    }
    elseif ($config->get('adsense_test_mode') || $account->hasPermission('show adsense placeholders')) {
      // Show ad placeholder.
      $content = $this->getAdPlaceholder();
      return [
        '#theme' => 'adsense_ad',
        '#content' => $content['#content'],
        '#width' => isset($content['#width']) ? $content['#width'] : NULL,
        '#height' => isset($content['#height']) ? $content['#height'] : NULL,
        '#format' => $content['#format'],
        '#classes' => array_merge(['adsense-placeholder'], $classes),
        '#attached' => ['library' => $libraries],
      ];
    }
    else {
      // Display ad-block disabling request.
      if ($config->get('adsense_unblock_ads')) {
        $libraries[] = 'adsense/adsense.unblock';
      }
      $content = $this->getAdContent();

      // Show ad.
      return [
        '#theme' => 'adsense_ad',
        '#content' => $content,
        '#width' => isset($content['#width']) ? $content['#width'] : NULL,
        '#height' => isset($content['#height']) ? $content['#height'] : NULL,
        '#format' => $content['#format'],
        '#classes' => $classes,
        '#attached' => ['library' => $libraries],
      ];
    }

    return [
      '#theme' => 'adsense_comment',
      '#comment' => 'adsense: ' . $text,
    ];
  }

  /**
   * Check if ads display is disabled.
   *
   * @param string $text
   *   Reason for the ad display being disabled.
   *
   * @return bool
   *   TRUE if ads are disabled.
   */
  public static function isDisabled(&$text = '') {
    $account = \Drupal::currentUser();
    $config = \Drupal::config('adsense.settings');

    if (!$config->get('adsense_basic_id')) {
      $text = 'no publisher id configured.';
    }
    elseif ($config->get('adsense_disable')) {
      $text = 'adsense disabled.';
    }
    elseif (($account->id() != 1) && ($account->hasPermission('hide adsense'))) {
      $text = 'disabled for current user.';
    }
    else {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Check if another ad of this type can be inserted.
   *
   * @return bool
   *   TRUE if ad can be inserted.
   */
  public function canInsertAnother() {
    // Because of #1627846, it's better to always return TRUE.
    return TRUE;

    // @codingStandardsIgnoreStart
//    static $num_ads = [
//      ADSENSE_TYPE_AD      => 0,
//      ADSENSE_TYPE_LINK    => 0,
//      ADSENSE_TYPE_SEARCH  => 0,
//      ADSENSE_TYPE_MATCHED => 0,
//    ];
//
//    $max_ads = [
//      ADSENSE_TYPE_AD      => 3,
//      ADSENSE_TYPE_LINK    => 3,
//      ADSENSE_TYPE_SEARCH  => 2,
//      ADSENSE_TYPE_MATCHED => PHP_INT_MAX,
//    ];
//
//    if ($num_ads[$this->type] < $max_ads[$this->type]) {
//      $num_ads[$this->type]++;
//      return TRUE;
//    }
//
//    return FALSE;
    // @codingStandardsIgnoreEnd
  }

  /**
   * List of available languages.
   *
   * @return array
   *   array of language options with the key used by Google and description.
   */
  public static function adsenseLanguages() {
    return [
      'ar'    => t('Arabic'),
      'bg'    => t('Bulgarian'),
      'ca'    => t('Catalan'),
      'zh-Hans' => t('Chinese Simplified'),
      'zh-TW' => t('Chinese Traditional'),
      'hr'    => t('Croatian'),
      'cs'    => t('Czech'),
      'da'    => t('Danish'),
      'nl'    => t('Dutch'),
      'en'    => t('English'),
      'et'    => t('Estonian'),
      'fi'    => t('Finnish'),
      'fr'    => t('French'),
      'de'    => t('German'),
      'el'    => t('Greek'),
      'iw'    => t('Hebrew'),
      'hi'    => t('Hindi'),
      'hu'    => t('Hungarian'),
      'is'    => t('Icelandic'),
      'in'    => t('Indonesian'),
      'it'    => t('Italian'),
      'ja'    => t('Japanese'),
      'ko'    => t('Korean'),
      'lv'    => t('Latvian'),
      'lt'    => t('Lithuanian'),
      'no'    => t('Norwegian'),
      'pl'    => t('Polish'),
      'pt'    => t('Portuguese'),
      'ro'    => t('Romanian'),
      'ru'    => t('Russian'),
      'sr'    => t('Serbian'),
      'sk'    => t('Slovak'),
      'sl'    => t('Slovenian'),
      'es'    => t('Spanish'),
      'sv'    => t('Swedish'),
      'th'    => t('Thai'),
      'tr'    => t('Turkish'),
      'uk'    => t('Ukrainian'),
      'vi'    => t('Vietnamese'),
    ];
  }

}
