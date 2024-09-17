<?php

namespace Drupal\image_style_warmer\Commands;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Commands\DrushCommands;

/**
 * Image Style Warmer Drush Commands.
 */
class ImageStyleWarmerCommands extends DrushCommands {

  use StringTranslationTrait;

  /**
   * Logger Channel Factory Service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  private $loggerChannelFactory;

  /**
   * File Entity Storage interface.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $fileEntityStorage;

  /**
   * Constructs a new ImageStyleWarmerCommands object.
   */
  public function __construct(LoggerChannelFactoryInterface $loggerChannelFactory, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct();
    $this->loggerChannelFactory = $loggerChannelFactory;
    $this->fileEntityStorage = $entityTypeManager->getStorage('file');
  }

  /**
   * Warms up all the existing images by using the default configuration.
   *
   * @command image-style-warmer:warm-up
   * @aliases isw:wu
   */
  public function warmUp() {
    $this->logger()->notice("Loading files.");
    $this->loggerChannelFactory->get('image_style_warmer')->info('Image styles warmer loading files.');

    $files = $this->fileEntityStorage->getQuery()->accessCheck(FALSE)->execute();
    if (!empty($files)) {
      $count = count($files);
      $numOperations = 0;
      $operations = [];

      $this->loggerChannelFactory->get('image_style_warmer')->info('Image styles warmer batch operations start');
      foreach ($files as $fid) {
        $operations[] = [
          '\Drupal\image_style_warmer\BatchService::warmUpFileProcess',
          [$fid, $count],
        ];
        $numOperations++;
      }

      $batch = [
        'title' => $this->t('Warming up image styles for @num file(s)', ['@num' => $numOperations]),
        'operations' => $operations,
        'finished' => '\Drupal\image_style_warmer\BatchService::warmUpFileFinished',
      ];
      batch_set($batch);
      drush_backend_batch_process();

      $this->logger()->notice("Batch operations end.");
      $this->loggerChannelFactory->get('image_style_warmer')->info('Image styles warmer batch operations end.');
    }
    else {
      $this->logger()->notice("No files found.");
      $this->logger()->warning('No files found to warm up.');
      $this->loggerChannelFactory->get('image_style_warmer')->info('No files found to warm up.');
    }
  }

}
