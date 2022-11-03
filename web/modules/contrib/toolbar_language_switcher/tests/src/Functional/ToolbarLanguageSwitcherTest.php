<?php

namespace Drupal\Tests\toolbar_language_switcher\Functional;

use Drupal\Core\Url;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Base test class for toolbar_language_switcher tests.
 *
 * @group toolbar_language_switcher
 */
class ToolbarLanguageSwitcherTest extends BrowserTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'toolbar_language_switcher',
  ];

  /**
   * A test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser([
      'access toolbar',
      'use toolbar_language_switcher',
    ]);
  }

  /**
   * Test single language project setup.
   */
  public function testSingleLanguageProjectSetup() {
    $front_page = Url::fromRoute('<front>');
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->user);
    $this->drupalGet($front_page);
    $assert_session->pageTextNotContains('Switch to English');
    $assert_session->pageTextNotContains('English');
  }

  /**
   * Test two languages project setup.
   */
  public function testTwoLanguagesProjectSetup() {
    $languages = [
      'xx' => 'Lolspeak1',
    ];
    $this->installLanguages($languages);
    $front_page = Url::fromRoute('<front>');
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->user);
    $this->drupalGet($front_page);
    $assert_session->linkExists('Switch to Lolspeak1');
    $this->clickLink('Switch to Lolspeak1');
    $assert_session->linkExists('Switch to English');
    $this->clickLink('Switch to English');
    $assert_session->linkExists('Switch to Lolspeak1');
  }

  /**
   * Test multiple languages project setup.
   */
  public function testMultipleLanguagesProjectSetup() {
    $languages = [
      'xx' => 'Lolspeak1',
      'zz' => 'Lolspeak2',
    ];
    $this->installLanguages($languages);
    $front_page = Url::fromRoute('<front>');
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->user);
    $this->drupalGet($front_page);
    foreach ($languages as $title) {
      $assert_session->linkExists($title);
    }
  }

  /**
   * Test use use toolbar_language_switcher permission.
   */
  public function testNotRenderedForTheForbiddenUser() {
    $languages = [
      'xx' => 'Lolspeak1',
      'zz' => 'Lolspeak2',
    ];
    $this->installLanguages($languages);
    $front_page = Url::fromRoute('<front>');
    $assert_session = $this->assertSession();

    // Check permission access. Create user without required permissions.
    $this->drupalLogin($this->drupalCreateUser(['access toolbar']));
    $this->drupalGet($front_page);
    foreach ($languages as $title) {
      $assert_session->linkNotExists($title);
    }
  }

  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function installLanguages($languages) {
    foreach ($languages as $langcode) {
      $language = ConfigurableLanguage::createFromLangcode($langcode);
      $language->save();
    }
  }

}
