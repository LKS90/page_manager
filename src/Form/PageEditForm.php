<?php

/**
 * @file
 * Contains \Drupal\page_manager\Form\PageEditForm.
 */

namespace Drupal\page_manager\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Provides a form for editing a page entity.
 */
class PageEditForm extends PageFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $form = parent::form($form, $form_state);
    $attributes = array(
      'class' => array('use-ajax'),
      'data-accepts' => 'application/vnd.drupal-modal',
      'data-dialog-options' => Json::encode(array(
        'width' => 'auto',
      )),
    );

    $form['page_variant_section'] = array(
      '#type' => 'details',
      '#title' => $this->t('Page Variants'),
      '#open' => TRUE,
    );
    $form['page_variant_section']['add_new_page'] = array(
      '#type' => 'link',
      '#title' => $this->t('Add new page variant'),
      '#route_name' => 'page_manager.page_variant_select',
      '#route_parameters' => array(
        'page' => $this->entity->id(),
      ),
      '#attributes' => $attributes,
      '#attached' => array(
        'library' => array(
          'core/drupal.ajax',
        ),
      ),
    );
    $form['page_variant_section']['page_variants'] = array(
      '#type' => 'table',
      '#header' => array(
        $this->t('Label'),
        $this->t('Plugin'),
        $this->t('Regions'),
        $this->t('Number of blocks'),
        $this->t('Weight'),
        $this->t('Operations'),
      ),
      '#empty' => $this->t('There are no page variants.'),
      '#tabledrag' => array(array(
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'page-variant-weight',
      )),
    );
    foreach ($this->entity->getPageVariants() as $page_variant_id => $page_variant) {
      $row = array(
        '#attributes' => array(
          'class' => array('draggable'),
        ),
      );
      $row['label']['#markup'] = $page_variant->label();
      $row['id']['#markup'] = $page_variant->adminLabel();
      $row['regions'] = array('data' => array(
        '#theme' => 'item_list',
        '#items' => $page_variant->getRegionNames(),
      ));
      $row['count']['#markup'] = $page_variant->getBlockCount();
      $row['weight'] = array(
        '#type' => 'weight',
        '#default_value' => $page_variant->getWeight(),
        '#title' => t('Weight for @page_variant page variant', array('@page_variant' => $page_variant->label())),
        '#title_display' => 'invisible',
        '#attributes' => array(
          'class' => array('page-variant-weight'),
        ),
      );
      $operations = array();
      $operations['edit'] = array(
        'title' => $this->t('Edit'),
        'route_name' => 'page_manager.page_variant_edit',
        'route_parameters' => array(
          'page' => $this->entity->id(),
          'page_variant_id' => $page_variant_id,
        ),
      );
      $operations['delete'] = array(
        'title' => $this->t('Delete'),
        'route_name' => 'page_manager.page_variant_delete',
        'route_parameters' => array(
          'page' => $this->entity->id(),
          'page_variant_id' => $page_variant_id,
        ),
      );
      $row['operations'] = array(
        '#type' => 'operations',
        '#links' => $operations,
      );
      $form['page_variant_section']['page_variants'][$page_variant_id] = $row;
    }

    $form['access_section_section'] = array(
      '#type' => 'details',
      '#title' => $this->t('Access Conditions'),
      '#open' => TRUE,
    );
    $form['access_section_section']['add'] = array(
      '#type' => 'link',
      '#title' => $this->t('Add new access condition'),
      '#route_name' => 'page_manager.access_condition_select',
      '#route_parameters' => array(
        'page' => $this->entity->id(),
      ),
      '#attributes' => $attributes,
      '#attached' => array(
        'library' => array(
          'core/drupal.ajax',
        ),
      ),
    );
    $form['access_section_section']['access_section'] = array(
      '#type' => 'table',
      '#header' => array(
        $this->t('Label'),
        $this->t('Description'),
        $this->t('Operations'),
      ),
      '#empty' => $this->t('There are no access conditions.'),
    );

    $form['access_section_section']['access_logic'] = array(
      '#type' => 'radios',
      '#options' => array(
        'and' => $this->t('All conditions must pass'),
        'or' => $this->t('Only one condition must pass'),
      ),
      '#default_value' => $this->entity->getAccessLogic(),
    );

    $access_conditions = $this->entity->getAccessConditions();
    $form['access_section_section']['access'] = array(
      '#tree' => TRUE,
    );
    foreach ($access_conditions as $access_id => $access_condition) {
      $row = array();
      $row['label']['#markup'] = $access_condition->getPluginDefinition()['label'];
      $row['description']['#markup'] = $access_condition->summary();
      $operations = array();
      $operations['edit'] = array(
        'title' => $this->t('Edit'),
        'route_name' => 'page_manager.access_condition_edit',
        'route_parameters' => array(
          'page' => $this->entity->id(),
          'condition_id' => $access_id,
        ),
        'attributes' => $attributes,
      );
      $operations['delete'] = array(
        'title' => $this->t('Delete'),
        'route_name' => 'page_manager.access_condition_delete',
        'route_parameters' => array(
          'page' => $this->entity->id(),
          'condition_id' => $access_id,
        ),
        'attributes' => $attributes,
      );
      $row['operations'] = array(
        '#type' => 'operations',
        '#links' => $operations,
      );
      $form['access_section_section']['access_section'][$access_id] = $row;
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    if (!empty($form_state['values']['page_variants'])) {
      foreach ($form_state['values']['page_variants'] as $page_variant_id => $data) {
        if ($page_variant = $this->entity->getPageVariant($page_variant_id)) {
          $page_variant->setWeight($data['weight']);
        }
      }
    }
    parent::save($form, $form_state);
    drupal_set_message($this->t('The %label page has been updated.', array('%label' => $this->entity->label())));
    $form_state['redirect_route'] = new Url('page_manager.page_list');
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, array &$form_state) {
    $keys_to_skip = array_keys($this->entity->getPluginBags());
    foreach ($form_state['values'] as $key => $value) {
      if (!in_array($key, $keys_to_skip)) {
        $entity->set($key, $value);
      }
    }
  }

}