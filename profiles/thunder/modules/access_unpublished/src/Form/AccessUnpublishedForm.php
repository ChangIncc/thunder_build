<?php

namespace Drupal\access_unpublished\Form;

use Drupal\access_unpublished\Entity\AccessToken;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Alter the entity form to add access unpublished elements.
 */
class AccessUnpublishedForm implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Access unpublished config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Data formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * AccessUnpublishedForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   Date formatter service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory, DateFormatterInterface $dateFormatter) {
    $this->entityTypeManager = $entityTypeManager;
    $this->config = $configFactory->get('access_unpublished.settings');
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('date.formatter')
    );
  }

  /**
   * Alter the entity form to add access unpublished elements.
   */
  public function formAlter(&$form, FormStateInterface $form_state) {

    if (!$form_state->getFormObject() instanceof EntityForm) {
      return;
    }

    /** @var \Drupal\Core\Entity\Entity $entity */
    $entity = $form_state->getFormObject()->getEntity();

    if ($entity instanceof EntityPublishedInterface && !$entity->isPublished() && !$entity->isNew()) {

      $storage = $this->entityTypeManager->getStorage('access_token');

      $query = $storage->getQuery();
      $tokens = $query->condition('entity_type', $entity->getEntityType()->id())
        ->condition('entity_id', $entity->id())
        ->execute();

      $form['#attached']['library'][] = 'access_unpublished/drupal.access_unpublished.admin';

      // Create the group for the fields.
      $form['access_unpublished_settings'] = [
        '#type' => 'details',
        '#title' => $this->t('Temporary unpublished access'),
        '#open' => !empty($tokens),
        '#weight' => 35,
        '#attributes' => [
          'class' => ['access-unpublished-form'],
        ],
        '#optional' => FALSE,
        '#group' => 'advanced',
      ];

      if ($tokens) {

        /** @var \Drupal\access_unpublished\Entity\AccessToken[] $tokens */
        $tokens = $storage->loadMultiple($tokens);

        $form['access_unpublished_settings']['token_table'] = [
          '#type' => 'table',
          '#header' => [
            $this->t('Valid'),
            $this->t('Link'),
            $this->t('Expire date'),
            $this->t('Operations'),
          ],
        ];

        $tokenKey = $this->config->get('hash_key');

        foreach ($tokens as $id => $token) {

          $form['access_unpublished_settings']['token_table'][$id]['expired'] = [
            '#type' => 'checkbox',
            '#default_value' => !$token->isExpired(),
            '#disabled' => TRUE,
          ];

          $url = Url::fromRoute('entity.' . $entity->getEntityType()->id() . '.canonical',
            [
              $entity->getEntityType()->id() => $entity->id(),
              $tokenKey => $token->get('value')->value,
            ],
            [
              'absolute' => TRUE,
            ]
          )->toString();

          $form['access_unpublished_settings']['token_table'][$id]['link'] = [
            '#type' => 'button',
            '#value' => $this->t('Copy to clipboard'),
            '#attributes' => [
              'data-clipboard-text' => $url,
              'class' => ['clipboard-button'],
            ],
          ];
          $form['access_unpublished_settings']['token_table'][$id]['expire_date'] = [
            '#plain_text' => $token->get('expire')->value > 0 ? $this->dateFormatter->format($token->get('expire')->value, 'short') : $this->t('Unlimited'),
          ];

          if ($token->isExpired()) {
            $form['access_unpublished_settings']['token_table'][$id]['operation'] = [
              '#type' => 'submit',
              '#value' => $this->t('Renew'),
              '#submit' => [__CLASS__ . '::renewToken'],
              '#name' => 'op-' . $token->id(),
              '#token_id' => $token->id(),
            ];
          }
          else {
            $form['access_unpublished_settings']['token_table'][$id]['operation'] = [
              '#type' => 'submit',
              '#value' => $this->t('Delete'),
              '#submit' => [__CLASS__ . '::deleteToken'],
              '#name' => 'op-' . $token->id(),
              '#token_id' => $token->id(),

            ];
          }
        }
      }

      $form['access_unpublished_settings']['duration'] = [
        '#type' => 'select',
        '#title' => t('Lifetime'),
        '#options' => [
          86400 => $this->t('@days Days', ['@days' => 1]),
          172800 => $this->t('@days Days', ['@days' => 2]),
          345600 => $this->t('@days Days', ['@days' => 4]),
          604800 => $this->t('@days Days', ['@days' => 7]),
          1209600 => $this->t('@days Days', ['@days' => 14]),
          -1 => $this->t('Unlimited'),
        ],
        '#default_value' => $this->config->get('duration'),
      ];

      $form['access_unpublished_settings']['generate_token'] = [
        '#type' => 'submit',
        '#value' => $this->t('Generate token'),
        '#submit' => [__CLASS__ . '::generateToken'],
      ];
    }
  }

  /**
   * Submit callback to generate a token.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function generateToken(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\Entity $entity */
    $entity = $form_state->getFormObject()->getEntity();
    \Drupal::entityTypeManager()->getStorage('access_token')->create(
      [
        'entity_type' => $entity->getEntityType()->id(),
        'entity_id' => $entity->id(),
        'expire' => $form_state->getValue('duration') > 0 ? REQUEST_TIME + $form_state->getValue('duration') : -1,
      ]
    )->save();

    $form_state->setRebuild();
  }

  /**
   * Submit callback to generate a token.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function deleteToken(array &$form, FormStateInterface $form_state) {

    $triggeringElement = $form_state->getTriggeringElement();

    $id = $triggeringElement['#token_id'];

    /** @var \Drupal\access_unpublished\Entity\AccessToken $token */
    $token = \Drupal::entityTypeManager()->getStorage('access_token')->load($id);

    if ($token) {
      $token->delete();
    }
    $form_state->setRebuild();

  }

  /**
   * Renews a AccessToken.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function renewToken(array &$form, FormStateInterface $form_state) {

    $triggeringElement = $form_state->getTriggeringElement();

    $id = $triggeringElement['#token_id'];

    /** @var \Drupal\access_unpublished\Entity\AccessToken $token */
    $token = \Drupal::entityTypeManager()->getStorage('access_token')->load($id);

    if ($token) {
      $token->set('expire', AccessToken::defaultExpiration());
      $token->save();
    }

    $form_state->setRebuild();

  }

}
