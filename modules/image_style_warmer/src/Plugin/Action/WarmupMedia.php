<?php

namespace Drupal\image_style_warmer\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\image_style_warmer\ImageStylesWarmerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * An action to warmup image styles of media entities.
 *
 * @Action(
 *   id = "image_style_warmer_warmup_media",
 *   label = @Translation("Warmup image styles of media entities"),
 *   type = "media",
 *   confirm = TRUE,
 * )
 */
class WarmupMedia extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * Image Style Warmer.
   *
   * @var \Drupal\image_style_warmer\ImageStylesWarmerInterface
   */
  protected $imageStyleWarmer;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\image_style_warmer\ImageStylesWarmerInterface $image_style_warmer
   *   The Imag Style Warmer service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ImageStylesWarmerInterface $image_style_warmer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->imageStyleWarmer = $image_style_warmer;
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('image_style_warmer.warmer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    foreach ($entity->getFieldDefinitions() as $name => $definition) {
      if ($definition->getType() == 'image'
        && !$entity->get($name)->isEmpty()) {

        foreach($entity->get($name)->referencedEntities() as $file) {
          $this->imageStyleWarmer->warmUp($file);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('edit', $account, $return_as_object);
  }

}
