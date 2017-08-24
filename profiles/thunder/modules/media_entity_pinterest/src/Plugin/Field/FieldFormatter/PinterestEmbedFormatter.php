<?php

namespace Drupal\media_entity_pinterest\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\media_entity_pinterest\Plugin\MediaEntity\Type\Pinterest;

/**
 * Plugin implementation of the 'pinterest_embed' formatter.
 *
 * @FieldFormatter(
 *   id = "pinterest_embed",
 *   label = @Translation("Pinterest embed"),
 *   field_types = {
 *     "link", "string", "string_long"
 *   }
 * )
 */
class PinterestEmbedFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = array();

    foreach ($items as $delta => $item) {
      $matches = [];
      foreach (Pinterest::$validationRegexp as $pattern => $key) {
        if (preg_match($pattern, $this->getEmbedCode($item), $item_matches)) {
          $matches[] = $item_matches;
        }
      }

      if (!empty($matches)) {
        $matches = reset($matches);
      }

      // PIN_URL_RE matched.
      if (!empty($matches['id'])) {
        $element[$delta] = [
          '#theme' => 'media_entity_pinterest_pin',
          '#path' => 'https://' . $matches[2] . 'pinterest.' . $matches[3] . $matches[4] . '/pin/' . $matches['id'],
          '#attributes' => [
            'class' => [],
            'data-conversation' => 'none',
            'lang' => $langcode,
          ],
        ];
      }

      // BOARD_URL_RE matched.
      if (!empty($matches['username']) && !empty($matches['slug'])) {
        $element[$delta] = [
          '#theme' => 'media_entity_pinterest_board',
          '#path' => 'https://' . $matches[2] . 'pinterest.' . $matches[3] . $matches[4] . '/' . $matches['username'] . '/' . $matches['slug'],
          '#attributes' => [
            'class' => [],
            'data-conversation' => 'none',
            'lang' => $langcode,
          ],
        ];

      }

      // USER_URL_RE matched.
      if (!empty($matches['username']) && empty($matches['slug'])) {
        $element[$delta] = [
          '#theme' => 'media_entity_pinterest_profile',
          '#path' => 'https://' . $matches[2] . 'pinterest.' . $matches[3] . $matches[4] . '/' . $matches['username'],
          '#attributes' => [
            'class' => [],
            'data-conversation' => 'none',
            'lang' => $langcode,
          ],
        ];

      }
    }

    if (!empty($element)) {
      $element['#attached'] = [
        'library' => [
          'media_entity_pinterest/integration',
        ],
      ];
    }

    return $element;
  }

  /**
   * Extracts the embed code from a field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   *
   * @return string|null
   *   The embed code, or NULL if the field type is not supported.
   */
  protected function getEmbedCode(FieldItemInterface $item) {
    switch ($item->getFieldDefinition()->getType()) {
      case 'link':
        return $item->uri;

      case 'string':
      case 'string_long':
        return $item->value;

      default:
        break;
    }
  }

  // @TODO: Provide settings form to configure field formatters.
}
