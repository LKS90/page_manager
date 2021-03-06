<?php

/**
 * @file
 * Contains \Drupal\page_manager\Entity\PageListBuilder.
 */

namespace Drupal\page_manager\Entity;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\page_manager\PageInterface;

/**
 * Provides a list builder for page entities.
 */
class PageListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Machine name');
    $header['path'] = $this->t('Path');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var $entity \Drupal\page_manager\PageInterface */
    $row['label'] = $this->getLabel($entity);
    $row['id'] = $entity->id();
    $row['path'] = $this->getPath($entity);

    return $row + parent::buildRow($entity);
  }

  /**
   * Gets the displayable path of a page entity.
   *
   * @param \Drupal\page_manager\PageInterface $entity
   *   The page entity.
   *
   * @return array|string
   *   The value of the path.
   */
  protected function getPath(PageInterface $entity) {
    // If the page is enabled and not dynamic, show the path as a link,
    // otherwise as plain text.
    $path = $entity->getPath();
    if ($entity->status() && strpos($path, '%') === FALSE) {
      return array(
        'data' => array(
          '#type' => 'link',
          // @todo Update once https://www.drupal.org/node/2351379 is in.
          '#url' => Url::fromUri('base://' . trim($path, '/')),
          '#title' => $path,
        ),
      );
    }
    else {
      return $path;
    }
  }

}
