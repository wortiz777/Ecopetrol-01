<?php

namespace Drupal\Tests\tmgmt_content\Kernel;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\pathauto\Entity\PathautoPattern;
use Drupal\Tests\Traits\Core\PathAliasTestTrait;

/**
 * Tests for the path/pathauto integration.
 *
 * @group tmgmt
 */
class ContentEntityPathTest extends ContentEntityTestBase {

  use PathAliasTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'path', 'path_alias'];

  /**
   * The entity type used for the tests.
   *
   * @var string
   */
  protected $entityTypeId = 'node';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('path_alias');

    $type = NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ]);
    $type->save();
    $this->container->get('content_translation.manager')->setEnabled('node', 'page', TRUE);
  }

  /**
   * Tests the path integration.
   */
  public function testNoPathauto() {

    // Create a node with a path.
    $values = [
      'langcode' => 'en',
      'type' => 'page',
      'uid' => 1,
      'title' => 'Test node',
      'path' => [
        'alias' => '/en-example-path',
      ],
    ];
    $node = Node::create($values);
    $node->save();

    $this->assertPathAliasExists('/en-example-path', 'en', NULL, '');

    $job = tmgmt_job_create('en', 'de');
    $job->translator = 'test_translator';
    $job->save();
    $job_item = tmgmt_job_item_create('content', $this->entityTypeId, $node->id(), ['tjid' => $job->id()]);
    $job_item->save();

    $source_plugin = $this->container->get('plugin.manager.tmgmt.source')->createInstance('content');
    $data = $source_plugin->getData($job_item);

    // Test the expected structure of the metatags field.
    $expected_field_data = [
      '#label' => 'URL alias',
      0 => [
        'alias' => [
          '#label' => 'Path alias',
          '#text' => '/en-example-path',
          '#translate' => TRUE,
        ],
        'pid' => [
          '#label' => 'Path id',
          '#text' => '1',
          '#translate' => FALSE,
        ],
        'langcode' => [
          '#label' => 'Language Code',
          '#text' => 'en',
          '#translate' => FALSE,
        ],
      ],
    ];
    $this->assertEquals($expected_field_data, $data['path']);

    // Now request a translation and save it back.
    $job->requestTranslation();
    $items = $job->getItems();
    $item = reset($items);
    $item->acceptTranslation();

    // Check that the translations were saved correctly.
    $node = Node::load($node->id());
    $translation = $node->getTranslation('de');
    $this->assertEquals('/de-example-path', $translation->get('path')->alias);
    $this->assertEquals('de', $translation->get('path')->langcode);
    $this->assertNotEquals($node->get('path')->pid, $translation->get('path')->pid);

    $this->assertPathAliasExists('/de-example-path', 'de', NULL, '');
  }

  /**
   * Tests the pathauto integration.
   */
  public function testPathauto() {

    $this->installModule('ctools');
    $this->installModule('token');
    $this->installModule('pathauto');
    $this->installConfig(['system', 'pathauto']);

    $pattern = PathautoPattern::create([
      'id' => 'tmgmt_pattern',
      'type' => 'canonical_entities:node',
      'pattern' => '/[node:langcode]-[node:title]',
      'weight' => 0,
    ]);
    $pattern->save();

    // Create a node with a path.
    $values = [
      'langcode' => 'en',
      'type' => 'page',
      'uid' => 1,
      'title' => 'Test node',
    ];
    $node = Node::create($values);
    $node->save();

    $this->assertPathAliasExists('/en-test-node', 'en', NULL, '');

    $job = tmgmt_job_create('en', 'de');
    $job->translator = 'test_translator';
    $job->save();
    $job_item = tmgmt_job_item_create('content', $this->entityTypeId, $node->id(), ['tjid' => $job->id()]);
    $job_item->save();

    $source_plugin = $this->container->get('plugin.manager.tmgmt.source')->createInstance('content');
    $data = $source_plugin->getData($job_item);

    // Test the expected structure of the metatags field.
    $expected_field_data = [
      '#label' => 'URL alias',
      0 => [
        'alias' => [
          '#label' => 'Path alias',
          '#text' => '/en-test-node',
          '#translate' => FALSE,
        ],
        'pid' => [
          '#label' => 'Path id',
          '#text' => '1',
          '#translate' => FALSE,
        ],
        'langcode' => [
          '#label' => 'Language Code',
          '#text' => 'en',
          '#translate' => FALSE,
        ],
      ],
    ];
    $this->assertEquals($expected_field_data, $data['path']);

    // Now request a translation and save it back.
    $job->requestTranslation();
    $items = $job->getItems();
    $item = reset($items);
    $item->acceptTranslation();

    // Check that the translations were saved correctly.
    $node = Node::load($node->id());
    $translation = $node->getTranslation('de');
    $this->assertEquals('/de-dede-ch-test-node', $translation->get('path')->alias);
    $this->assertEquals('de', $translation->get('path')->langcode);
    $this->assertNotEquals($node->get('path')->pid, $translation->get('path')->pid);

    $this->assertPathAliasExists('/de-dede-ch-test-node', 'de', NULL, '');

    // Repeat with a manual alias.
    $values = [
      'langcode' => 'en',
      'type' => 'page',
      'uid' => 1,
      'title' => 'Test node',
      'path' => [
        'alias' => '/en-manual-path',
        'pathauto' => FALSE,
      ],
    ];
    $node = Node::create($values);
    $node->save();

    $this->assertPathAliasExists('/en-manual-path', 'en', NULL, '');

    $job = tmgmt_job_create('en', 'de');
    $job->translator = 'test_translator';
    $job->save();
    $job_item = tmgmt_job_item_create('content', $this->entityTypeId, $node->id(), ['tjid' => $job->id()]);
    $job_item->save();

    $source_plugin = $this->container->get('plugin.manager.tmgmt.source')->createInstance('content');
    $data = $source_plugin->getData($job_item);

    // Test the expected structure of the metatags field.
    $expected_field_data = [
      '#label' => 'URL alias',
      0 => [
        'alias' => [
          '#label' => 'Path alias',
          '#text' => '/en-manual-path',
          '#translate' => TRUE,
        ],
        'pid' => [
          '#label' => 'Path id',
          '#text' => '3',
          '#translate' => FALSE,
        ],
        'langcode' => [
          '#label' => 'Language Code',
          '#text' => 'en',
          '#translate' => FALSE,
        ],
      ],
    ];
    $this->assertEquals($expected_field_data, $data['path']);

    // Now request a translation and save it back.
    $job->requestTranslation();
    $items = $job->getItems();
    $item = reset($items);
    $item->acceptTranslation();

    // Check that the translations were saved correctly.
    $node = Node::load($node->id());
    $translation = $node->getTranslation('de');
    $this->assertEquals('/de-manual-path', $translation->get('path')->alias);
    $this->assertEquals('de', $translation->get('path')->langcode);
    $this->assertNotEquals($node->get('path')->pid, $translation->get('path')->pid);

    $this->assertPathAliasExists('/de-manual-path', 'de', NULL, '');

  }

}
