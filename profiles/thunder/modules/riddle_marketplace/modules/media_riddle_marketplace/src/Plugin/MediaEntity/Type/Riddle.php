<?php

namespace Drupal\media_riddle_marketplace\Plugin\MediaEntity\Type;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media_entity\MediaInterface;
use Drupal\media_entity\MediaTypeBase;
use Drupal\riddle_marketplace\RiddleFeedServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides media type plugin for Riddle.
 *
 * @MediaType(
 *   id = "riddle_marketplace",
 *   label = @Translation("Riddle Marketplace"),
 *   description = @Translation("Provides business logic and metadata for riddle marketplace.")
 * )
 */
class Riddle extends MediaTypeBase {

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Riddle feed service.
   *
   * @var \Drupal\riddle_marketplace\RiddleFeedServiceInterface
   */
  protected $riddleFeed;

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Entity field manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   * @param \Drupal\riddle_marketplace\RiddleFeedServiceInterface $riddleFeed
   *   Riddle feed service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ConfigFactoryInterface $config_factory, RiddleFeedServiceInterface $riddleFeed) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $config_factory->get('media_entity.settings'));
    $this->configFactory = $config_factory;
    $this->riddleFeed = $riddleFeed;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('config.factory'),
      $container->get('riddle_marketplace.feed')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function providedFields() {
    return [
      'id' => $this->t('Riddle id'),
      'status' => $this->t('Publishing status'),
      'thumbnail' => $this->t('Link to the thumbnail'),
      'thumbnail_local' => $this->t("Copies thumbnail locally and return it's URI"),
      'thumbnail_local_uri' => $this->t('Returns local URI of the thumbnail'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = [];
    $bundle = $form_state->getFormObject()->getEntity();
    $allowed_field_types = ['string', 'string_long'];
    foreach ($this->entityFieldManager->getFieldDefinitions('media', $bundle->id()) as $field_name => $field) {
      if (in_array($field->getType(), $allowed_field_types) && !$field->getFieldStorageDefinition()->isBaseField()) {
        $options[$field_name] = $field->getLabel();
      }
    }

    $form['source_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Field with source information'),
      '#description' => $this->t('Field on media entity that stores riddle embed code. You can create a bundle without selecting a value for this dropdown initially. This dropdown can be populated after adding fields to the bundle.'),
      '#default_value' => empty($this->configuration['source_field']) ? NULL : $this->configuration['source_field'],
      '#options' => $options,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getField(MediaInterface $media, $name) {

    $code = NULL;
    if (isset($this->configuration['source_field'])) {
      $source_field = $this->configuration['source_field'];
      if ($media->hasField($source_field)) {
        $property_name = $media->{$source_field}->first()->mainPropertyName();
        $code = $media->{$source_field}->{$property_name};
      }
    }

    if (!$code) {
      return FALSE;
    }

    if ($name == 'id') {
      return $code;
    }

    $riddleFeed = $this->riddleFeed->getFeed();
    $riddle = array_filter($riddleFeed, function ($entry) use ($code) {
      return $entry['uid'] == $code;
    });

    $riddle = current($riddle);

    // If we have auth settings return the other fields.
    if ($riddle) {
      switch ($name) {
        case 'title':
          if (isset($riddle['title'])) {
            return $riddle['title'];
          }
          return FALSE;

        case 'status':
          if (isset($riddle['status'])) {
            return $riddle['status'];
          }
          return FALSE;

        case 'thumbnail':
          if (isset($riddle['image'])) {
            return $riddle['image'];
          }
          return FALSE;

        case 'thumbnail_local':
          $local_uri = $this->getField($media, 'thumbnail_local_uri');
          if ($local_uri) {
            if (file_exists($local_uri)) {
              return $local_uri;
            }
            else {

              $directory = dirname($local_uri);
              file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);

              $image_url = $this->getField($media, 'thumbnail');

              return file_unmanaged_save_data(file_get_contents($image_url), $local_uri, FILE_EXISTS_REPLACE);
            }
          }
          return FALSE;

        case 'thumbnail_local_uri':
          if (isset($riddle['image'])) {
            return $this->configFactory->get('media_riddle_marketplace.settings')
              ->get('local_images') . '/' . $code . '.' . pathinfo(parse_url($riddle['image'], PHP_URL_PATH), PATHINFO_EXTENSION);
          }
          return FALSE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultThumbnail() {
    return $this->config->get('icon_base') . '/riddle.png';
  }

  /**
   * {@inheritdoc}
   */
  public function thumbnail(MediaInterface $media) {
    if ($local_image = $this->getField($media, 'thumbnail_local')) {
      return $local_image;
    }

    return $this->getDefaultThumbnail();
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultName(MediaInterface $media) {

    if ($title = $this->getField($media, 'title')) {
      return $title;
    }

    return parent::getDefaultName($media);
  }

}
