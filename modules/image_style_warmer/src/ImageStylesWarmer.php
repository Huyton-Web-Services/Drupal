<?php

namespace Drupal\image_style_warmer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DestructableInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Queue\QueueFactory;
use Drupal\file\FileInterface;

/**
 * Defines an images styles warmer.
 */
class ImageStylesWarmer implements ImageStylesWarmerInterface, DestructableInterface {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The file entity storage.
   *
   * @var \Drupal\file\FileStorageInterface
   */
  protected $file;

  /**
   * The file entity to warmup.
   *
   * @var array
   *   Array of \Drupal\file\FileInterface objects to warmup.
   */
  protected $warmupFiles = [];

  /**
   * The image factory.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * The image style entity storage.
   *
   * @var \Drupal\image\ImageStyleStorageInterface
   */
  protected $imageStyles;

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * Constructs a ImageStylesWarmer object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Image\ImageFactory $image_factory
   *   The image factory.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, ImageFactory $image_factory, QueueFactory $queue_factory)
  {
    $this->config = $config_factory->get('image_style_warmer.settings');
    $this->file = $entity_type_manager->getStorage('file');
    $this->imageFactory = $image_factory;
    $this->imageStyles = $entity_type_manager->getStorage('image_style');
    $this->queueFactory = $queue_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function warmUp(FileInterface $file) {

    // Add file for later initial warmup via destructor.
    $this->warmupFiles[] = $file;

    // Add file to queue for later warmup via cron.
    $queueImageStyles = $this->config->get('queue_image_styles');
    if (!empty($queueImageStyles)) {
        $this->addQueue($file, array_keys($queueImageStyles));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function doWarmUp(FileInterface $file, array $image_styles) {
    if (empty($image_styles) || !$this->validateImage($file)) {
      return;
    }

    /* @var \Drupal\Core\Image\Image $image */
    /* @var \Drupal\image\Entity\ImageStyle $style */

    // Create image derivatives if they not already exists.
    $styles = $this->imageStyles->loadMultiple($image_styles);
    $image_uri = $file->getFileUri();
    foreach ($styles as $style) {
      $derivative_uri = $style->buildUri($image_uri);
      if (!file_exists($derivative_uri)) {
        $style->createDerivative($image_uri, $derivative_uri);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function initialWarmUp(FileInterface $file) {
    $initialImageStyles = $this->config->get('initial_image_styles');
    if (!empty($initialImageStyles)) {
      $this->doWarmUp($file, array_keys($initialImageStyles));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function queueWarmUp(FileInterface $file) {
    $queueImageStyles = $this->config->get('queue_image_styles');
    if (!empty($queueImageStyles)) {
      $this->doWarmUp($file, array_keys($queueImageStyles));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addQueue(FileInterface $file, array $image_styles) {
    if (!empty($image_styles) && $this->validateImage($file)) {
      $queue = $this->queueFactory->get('image_style_warmer_pregenerator');
      $data = ['file_id' => $file->id(), 'image_styles' => $image_styles];
      $queue->createItem($data);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateImage(FileInterface $file) {
    if (!$file->isPermanent()) {
      return FALSE;
    }
    return empty(file_validate_extensions($file, implode(' ', $this->imageFactory->getSupportedExtensions())));
  }

  /**
   * {@inheritdoc}
   */
  public function destruct() {
    if (!empty($this->warmupFiles)) {
      $initialImageStyles = $this->config->get('initial_image_styles');
      if (!empty($initialImageStyles)) {
        foreach ($this->warmupFiles as $warmup_file) {
          $this->doWarmUp($warmup_file, array_keys($initialImageStyles));
        }
      }
    }
  }

}
