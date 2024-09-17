<?php

namespace Drupal\Tests\image_style_warmer\Functional;

use Drupal\Core\Database\Database;
use Drupal\Core\Queue\DatabaseQueue;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Functional tests to check general function of Image Style Warmer.
 *
 * @group image_style_warmer
 */
class ImageStyleWarmerGeneralTest extends ImageStyleWarmerTestBase {

  use CronRunTrait;

  /**
   * Weight of Image Style Warmer module.
   */
  const MODULE_WEIGHT = 10;

  /**
   * Test file.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $testFile;

  /**
   * Test queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $testQueue;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create queue of image style warmer pregenerator.
    $this->testQueue = new DatabaseQueue('image_style_warmer_pregenerator', Database::getConnection());
  }

  /**
   * Test Image Style Warmer module weight after install.
   */
  public function testImageStyleWarmerModuleWeight() {
    $extension_config = $this->container->get('config.factory')->get('core.extension');
    $module_weight = $extension_config->get('module.image_style_warmer');
    $this->assertEquals(self::MODULE_WEIGHT, $module_weight, 'Module weight is not correct after install.');
  }

  /**
   * Test Image Style Warmer initial warming for temporary file.
   */
  public function testImageStyleWarmerUploadTemporaryImageFile() {
    $this->prepareImageStyleWarmerTests();

    $this->assertFalse(file_exists($this->testInitialStyle->buildUri($this->testFile->getFileUri())), 'Generated file does not exist.');
    $this->assertFalse(file_exists($this->testQueueStyle->buildUri($this->testFile->getFileUri())), 'Generated file does not exist.');
  }

  /**
   * Test Image Style Warmer initial warming for permanent file.
   */
  public function testImageStyleWarmerUploadPermanentImageFile() {
    $this->prepareImageStyleWarmerTests(TRUE);

    $this->container->get('image_style_warmer.warmer')->doWarmUp($this->testFile, [$this->testInitialStyle->id()]);

    $this->assertTrue($this->testFile->isPermanent(), 'Image file is not permanent');
    $this->assertTrue(file_exists($this->testFile->getFileUri()), 'Original test image file does not exist.');
    $this->assertTrue(file_exists($this->testInitialStyle->buildUri($this->testFile->getFileUri())), 'Initial image style for permanent image file should exist.');
    $this->assertFalse(file_exists($this->testQueueStyle->buildUri($this->testFile->getFileUri())), 'Queue image style for permanent image file should not exist.');
  }

  /**
   * Test Image Style Warmer queue warming for temporary file.
   */
  public function testImageStyleWarmerQueueTemporaryImageFile() {
    $this->prepareImageStyleWarmerTests();

    $this->assertSame(0, $this->testQueue->numberOfItems(), 'Image Style Warmer Pregenerator queue should be empty.');
    $this->assertFalse(file_exists($this->testInitialStyle->buildUri($this->testFile->getFileUri())), 'Generated file does not exist.');
    $this->assertFalse(file_exists($this->testQueueStyle->buildUri($this->testFile->getFileUri())), 'Generated file does not exist.');
  }

  /**
   * Test Image Style Warmer queue warming for permanent file.
   */
  public function testImageStyleWarmerQueuePermanentImageFile() {
    $this->prepareImageStyleWarmerTests(TRUE);

    $this->container->get('image_style_warmer.warmer')->doWarmUp($this->testFile, [$this->testInitialStyle->id()]);
    $this->container->get('image_style_warmer.warmer')->addQueue($this->testFile, [$this->testQueueStyle->id()]);

    $this->assertSame(1, $this->testQueue->numberOfItems(), 'Image Style Warmer Pregenerator queue should not be empty.');
    $this->assertTrue(file_exists($this->testInitialStyle->buildUri($this->testFile->getFileUri())), 'Initial image style for permanent image file should exist.');
    $this->assertFalse(file_exists($this->testQueueStyle->buildUri($this->testFile->getFileUri())), 'Queue image style for permanent image file should not exist.');

    $this->cronRun();
    $this->assertSame(0, $this->testQueue->numberOfItems(), 'Image Style Warmer Pregenerator queue should be empty.');
    $this->assertTrue(file_exists($this->testQueueStyle->buildUri($this->testFile->getFileUri())), 'Queue image style for permanent image file should exist.');
  }

}
