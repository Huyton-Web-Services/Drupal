<?php

namespace Drupal\Tests\image_style_warmer\Functional;

/**
 * Functional test to check settings form of Image Style Warmer.
 *
 * @group image_style_warmer
 */
class ImageStyleWarmerSettingsFormTest extends ImageStyleWarmerTestBase {

  /**
   * Anonymous users don't have access to image_style_warmer settings page.
   */
  public function testAnonymousPermissionDenied() {
    $this->drupalLogout();
    $this->drupalGet('admin/config/development/performance/image-style-warmer');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Authenticated non-admin users don't have access to image_style_warmer settings page.
   */
  public function testAuthenticatedPermissionDenied() {
    $this->drupalLogin($this->createUser());
    $this->drupalGet('admin/config/development/performance/image-style-warmer');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Test Image Style Warmer settings page.
   */
  public function testSettingsPage() {

    // The admin user can access image_style_warmer settings page.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/development/performance/image-style-warmer');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains(t('Image styles'));
    $this->assertSession()->pageTextContains(t('Configure image styles which will be created initially or via queue worker by Image Style Warmer.'));
    $this->assertSession()->pageTextContains(t('Initial image styles'));
    $this->assertSession()->pageTextContains(t('Select image styles which will be created initial for an image.'));
    $this->assertSession()->pageTextContains(t('Queue image styles'));
    $this->assertSession()->pageTextContains(t('Select image styles which will be created via queue worker.'));
    $this->assertSession()->buttonExists(t('Save configuration'));

    // Can save settings with a selected initial and queue image style.
    $settings = [
      'initial_image_styles[test_initial]' => 'test_initial',
      'queue_image_styles[test_queue]' => 'test_queue',
    ];
    $this->submitForm($settings, t('Save configuration'));
    $this->assertSession()->pageTextContains(t('The configuration options have been saved.'));
    $this->assertSession()->checkboxChecked('initial_image_styles[test_initial]');
    $this->assertSession()->checkboxChecked('queue_image_styles[test_queue]');

    $this->assertIsArray($this->config('image_style_warmer.settings')->get('initial_image_styles'));
    $this->assertArrayHasKey('test_initial', $this->config('image_style_warmer.settings')->get('initial_image_styles'));
    $this->assertIsArray($this->config('image_style_warmer.settings')->get('queue_image_styles'));
    $this->assertArrayHasKey('test_queue', $this->config('image_style_warmer.settings')->get('queue_image_styles'));
  }

}
