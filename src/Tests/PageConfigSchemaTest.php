<?php

/**
 * @file
 * Contains \Drupal\page_manager\Tests\PageConfigSchemaTest.
 */

namespace Drupal\page_manager\Tests;

use Drupal\config\Tests\ConfigSchemaTestBase;
use Drupal\page_manager\Entity\Page;

/**
 * Ensures that page entities have valid config schema.
 */
class PageConfigSchemaTest extends ConfigSchemaTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('page_manager', 'node');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Page config schema',
      'description' => 'Ensures that page entities have valid config schema.',
      'group' => 'Page Manager',
    );
  }

  /**
   * Tests whether the page entity config schema is valid.
   */
  function testValidPageConfigSchema() {
    $id = strtolower($this->randomName());
    /** @var $page \Drupal\page_manager\PageInterface */
    $page = Page::create(array(
      'id' => $id,
      'label' => $this->randomName(),
      'path' => '/node/{node}',
    ));

    // Add an access condition.
    $page->addAccessCondition(array(
      'id' => 'node_type',
      'bundles' => array(
        'article' => 'article',
      ),
      'negate' => TRUE,
      'context_assignments' => array(
        'node' => 'node',
      ),
    ));

    // Add a block page variant.
    $page_variant_id = $page->addPageVariant(array(
      'id' => 'block_page',
      'label' => 'Block page',
    ));
    $page_variant = $page->getPageVariant($page_variant_id);

    // Add a selection condition.
    $page_variant->addSelectionCondition(array(
      'id' => 'node_type',
      'bundles' => array(
        'page' => 'page',
      ),
      'context_assignments' => array(
        'node' => 'node',
      ),
    ));

    $page->save();

    $config = \Drupal::config("page_manager.page.$id");
    $this->assertEqual($config->get('id'), $id);
    $this->assertConfigSchema(\Drupal::service('config.typed'), $config->getName(), $config->get());
  }

}