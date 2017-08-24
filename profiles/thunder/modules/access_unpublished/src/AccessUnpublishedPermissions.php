<?php

namespace Drupal\access_unpublished;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Calculates the permissions for access_unpublished.
 *
 * @package Drupal\access_unpublished
 */
class AccessUnpublishedPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new AccessUnpublishedPermissions instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * Permissions callback.
   *
   * @return array
   *   Returns permissions for all nodes.
   */
  public function permissions() {

    $permissions = [];

    $definitions = \Drupal::entityTypeManager()->getDefinitions();

    foreach ($definitions as $definition) {

      if (in_array('Drupal\Core\Entity\EntityPublishedInterface', class_implements($definition->getClass()))) {

        $bundles = $this->entityTypeManager->getStorage($definition->getBundleEntityType())
          ->loadMultiple();

        foreach ($bundles as $bundle) {

          $permission = "access_unpublished " . $definition->id() . " " . $bundle->id();

          $permissions[$permission] = [
            'title' => $this->t('Access unpublished %bundle @type', [
              '%bundle' => strtolower($bundle->label()),
              '@type' => $definition->getPluralLabel(),
            ]),
          ];
        }
      }
    }
    return $permissions;
  }

}
