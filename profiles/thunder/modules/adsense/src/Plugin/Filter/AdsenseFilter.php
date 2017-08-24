<?php

namespace Drupal\adsense\Plugin\Filter;

use Drupal\adsense\AdBlockInterface;
use Drupal\adsense\AdsenseAdBase;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter for AdSense input tags.
 *
 * @Filter(
 *   id = "filter_adsense",
 *   title = @Translation("AdSense tag"),
 *   description = @Translation("Substitutes an AdSense special tag with an ad. Add this below 'Limit allowed HTML tags'."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class AdsenseFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $patterns = [
      'block'   => '/\[adsense:block:([^\]]+)\]/x',
      'oldtag'  => '/\[adsense:([^:]+):(\d*):(\d*):?(\w*)\]/x',
      'tag'     => '/\[adsense:([^:]+):([^:\]]+)\]/x',
    ];
    $modified = FALSE;

    foreach ($patterns as $mode => $pattern) {
      if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
          /** @var AdSenseAdBase $ad */
          $ad = '';
          switch ($mode) {
            case 'block':
              // adsense:block:name.
              // Get the block with the same machine name as the tag.
              $module_blocks = \Drupal::entityTypeManager()
                ->getStorage('block')
                ->loadByProperties(['id' => $match[1]]);

              /** @var \Drupal\block\Entity\Block $block */
              foreach ($module_blocks as $block) {
                if ($block->getPlugin() instanceof AdBlockInterface) {
                  $ad = $block->getPlugin()->createAd();
                }
              }
              break;

            case 'oldtag':
              // adsense:format:group:channel:slot.
              $ad = AdsenseAdBase::createAd([
                'format' => $match[1],
                'group' => $match[2],
                'channel' => $match[3],
                'slot' => $match[4],
              ]);
              break;

            case 'tag':
              // adsense:format:slot.
              $ad = AdsenseAdBase::createAd([
                'format' => $match[1],
                'slot' => $match[2],
              ]);
              break;
          }
          // Replace the first occurrence of the tag, in case we have the same
          // tag more than once.
          if (isset($ad)) {
            $modified = TRUE;
            $ad_array = $ad->display();
            $ad_text = \Drupal::service('renderer')->render($ad_array);

            $text = preg_replace('/\\' . $match[0] . '/', $ad_text, $text);
          }
        }
      }
    }

    $result = new FilterProcessResult($text);

    if ($modified) {
      $result->addAttachments(['library' => ['adsense/adsense.css']]);
      $config = \Drupal::config('adsense.settings');
      if ($config->get('adsense_unblock_ads')) {
        $result->addAttachments(['library' => ['adsense/adsense.unblock']]);
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return $this->t('
        <p>Use tags to define AdSense ads. Examples:</p>
        <ul>
          <li><code>[adsense:<em>format</em>:<em>slot</em>]</code></li>
          <li><code>[adsense:<em>format</em>:<em>[group]</em>:<em>[channel]</em><em>[:slot]</em>]</code></li>
          <li><code>[adsense:block:<em>location</em>]</code></li>
        </ul>');
    }
    else {
      return $this->t('Use the special tag [adsense:<em>format</em>:<em>slot</em>] to display Google AdSense ads.');
    }
  }

}
