<?php

namespace Drupal\Tests\image_style_warmer\Functional;

use Drush\TestTraits\DrushTestTrait;

/**
 * Tests the Drush commands provided by Image Style Warmer.
 *
 * @group image_style_warmer
 */
class ImageStyleWarmerDrushTest extends ImageStyleWarmerTestBase {

  use DrushTestTrait;

  /**
   * Test file.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $testFile;

  /**
   * Tests the Scheduler Drush messages.
   */
  public function testDrushWarmUpMessages() {
    // Run the plain command using the full image-style-warmer:warm-up command
    // name, and check that all the output messages are shown.
    $this->drush('image-style-warmer:warm-up');
    $messages = $this->getErrorOutput();
    $this->assertStringContainsString('No files found', $messages, 'No files found message not found', TRUE);
  }

  /**
   * Tests Image Style Warmer warm-up via Drush command.
   */
  public function testDrushWarmUp() {
    $this->prepareImageStyleWarmerTests(TRUE);

    // Run Image Style Warmer's drush cron command and check that the expected
    // messages are found.
    $this->drush('image-style-warmer:warm-up');
    $messages = $this->getErrorOutput();
    $this->assertStringContainsString('Warming up styles for file 1 (1/1)', $messages, 'Warming up styles for 1 file message not found', TRUE);
    $this->assertStringContainsString('1 files warmed up', $messages, '1 files warmed up message not found', TRUE);
    $this->assertStringContainsString('Batch operations end', $messages, 'Batch operations end message not found', TRUE);
    $this->assertTrue(file_exists($this->testInitialStyle->buildUri($this->testFile->getFileUri())), 'Initial image style for permanent image file should exist.');
    $this->assertFalse(file_exists($this->testQueueStyle->buildUri($this->testFile->getFileUri())), 'Queue image style for permanent image file should not exist.');
  }

}
