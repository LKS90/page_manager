<?php

/**
 * @file
 * Contains \Drupal\page_manager\Plugin\VariantBase.
 */

namespace Drupal\page_manager\Plugin;

use Drupal\Component\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Condition\ConditionAccessResolverTrait;
use Drupal\Core\Condition\ConditionPluginBag;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\PluginDependencyTrait;
use Drupal\page_manager\PageExecutable;

/**
 * Provides a base class for DisplayVariant plugins.
 */
abstract class VariantBase extends PluginBase implements VariantInterface {

  use PluginDependencyTrait;

  use ConditionAccessResolverTrait;

  /**
   * The page executable.
   *
   * @var \Drupal\page_manager\PageExecutable
   */
  protected $executable;

  /**
   * The plugin bag that holds the block plugins.
   *
   * @var \Drupal\page_manager\Plugin\BlockPluginBag
   */
  protected $blockPluginBag;

  /**
   * The plugin bag that holds the selection condition plugins.
   *
   * @var \Drupal\Component\Plugin\PluginBag
   */
  protected $selectionConditionBag;

  /**
   * An array of collected contexts.
   *
   * This is only used on runtime, and is not stored.
   *
   * @var \Drupal\Component\Plugin\Context\ContextInterface[]
   */
  protected $contexts = array();

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function init(PageExecutable $executable) {
    $this->executable = $executable;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function getBlockBag() {
    if (!$this->blockPluginBag) {
      $this->blockPluginBag = new BlockPluginBag(\Drupal::service('plugin.manager.block'), $this->configuration['blocks']);
    }
    return $this->blockPluginBag;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->configuration['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function adminLabel() {
    return $this->pluginDefinition['admin_label'];
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->configuration['uuid'];
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return (int) $this->configuration['weight'];
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->configuration['weight'] = (int) $weight;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return array(
      'id' => $this->getPluginId(),
      'blocks' => $this->getBlockBag()->getConfiguration(),
      'selection_conditions' => $this->getSelectionConditions()->getConfiguration(),
    ) + $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'label' => '',
      'uuid' => '',
      'weight' => 0,
      'blocks' => array(),
      'selection_conditions' => array(),
      'selection_logic' => 'and',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    foreach ($this->getBlockBag() as $instance) {
      $this->calculatePluginDependencies($instance);
    }
    foreach ($this->getSelectionConditions() as $instance) {
      $this->calculatePluginDependencies($instance);
    }
    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('The label for this display variant.'),
      '#default_value' => $this->label(),
      '#maxlength' => '255',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, array &$form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, array &$form_state) {
    $this->configuration['label'] = $form_state['values']['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getBlock($block_id) {
    return $this->getBlockBag()->get($block_id);
  }

  /**
   * {@inheritdoc}
   */
  public function addBlock(array $configuration) {
    $configuration['uuid'] = $this->uuidGenerator()->generate();
    $this->getBlockBag()->addInstanceId($configuration['uuid'], $configuration);
    return $configuration['uuid'];
  }

  /**
   * {@inheritdoc}
   */
  public function removeBlock($block_id) {
    $this->getBlockBag()->removeInstanceId($block_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function updateBlock($block_id, array $configuration) {
    $existing_configuration = $this->getBlock($block_id)->getConfiguration();
    $this->getBlockBag()->setInstanceConfiguration($block_id, $configuration + $existing_configuration);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRegionAssignment($block_id) {
    $configuration = $this->getBlock($block_id)->getConfiguration();
    return isset($configuration['region']) ? $configuration['region'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getRegionAssignments() {
    // Build an array of the region names in the right order.
    $empty = array_fill_keys(array_keys($this->getRegionNames()), array());
    $full = $this->getBlockBag()->getAllByRegion();
    // Merge it with the actual values to maintain the ordering.
    return array_intersect_key(array_merge($empty, $full), $empty);
  }

  /**
   * {@inheritdoc}
   */
  public function getRegionName($region) {
    $regions = $this->getRegionNames();
    return isset($regions[$region]) ? $regions[$region] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockCount() {
    return count($this->configuration['blocks']);
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectionConditions() {
    if (!$this->selectionConditionBag) {
      $this->selectionConditionBag = new ConditionPluginBag(\Drupal::service('plugin.manager.condition'), $this->configuration['selection_conditions']);
    }
    return $this->selectionConditionBag;
  }

  /**
   * {@inheritdoc}
   */
  public function addSelectionCondition(array $configuration) {
    $configuration['uuid'] = $this->uuidGenerator()->generate();
    $this->getSelectionConditions()->addInstanceId($configuration['uuid'], $configuration);
    return $configuration['uuid'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectionCondition($condition_id) {
    return $this->getSelectionConditions()->get($condition_id);
  }

  /**
   * {@inheritdoc}
   */
  public function removeSelectionCondition($condition_id) {
    $this->getSelectionConditions()->removeInstanceId($condition_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectionLogic() {
    return $this->configuration['selection_logic'];
  }

  /**
   * {@inheritdoc}
   */
  public function getContexts() {
    return $this->contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function setContexts(array $contexts) {
    $this->contexts = $contexts;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function access() {
    $conditions = $this->getSelectionConditions();
    $contexts = $this->getContexts();
    foreach ($conditions as $condition) {
      if ($condition instanceof ContextAwarePluginInterface) {
        $this->contextHandler()->applyContextMapping($condition, $contexts);
      }
    }
    return $this->resolveConditions($conditions, $this->getSelectionLogic(), $contexts);
  }

  /**
   * Renders the page title and replaces tokens.
   *
   * @param string $page_title
   *   The page title that should be rendered.
   *
   * @return string
   *   The page title after replacing any tokens.
   */
  protected function renderPageTitle($page_title) {
    $data = $this->getContextAsTokenData();
    return \Drupal::token()->replace($page_title, $data);
  }

  /**
   * Returns available context as token data.
   *
   * @return array
   *   An array with token data values keyed by token type.
   */
  protected function getContextAsTokenData() {
    $data = array();
    foreach ($this->executable->getContexts() as $context) {
      if (strpos($context->getContextDefinition()['type'], 'entity:') === 0) {
        $token_type = substr($context->getContextDefinition()['type'], 7);
        if ($token_type == 'taxonomy_term') {
          $token_type = 'term';
        }
        $data[$token_type] = $context->getContextValue();
      }
    }
    return $data;
  }

  /**
   * Returns the UUID generator.
   *
   * @return \Drupal\Component\Uuid\UuidInterface
   */
  protected function uuidGenerator() {
    return \Drupal::service('uuid');
  }

  /**
   * Wraps the context handler.
   *
   * @return \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected function contextHandler() {
    return \Drupal::service('context.handler');
  }

}
