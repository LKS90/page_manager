<?php

/**
 * @file
 * Contains \Drupal\page_manager\PageExecutable.
 */

namespace Drupal\page_manager\Tests;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Plugin\Context\Context;
use Drupal\page_manager\Event\PageManagerContextEvent;
use Drupal\page_manager\PageExecutable;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the PageExecutable.
 *
 * @coversDefaultClass \Drupal\page_manager\PageExecutable
 *
 * @group Drupal
 * @group PageManager
 */
class PageExecutableTest extends UnitTestCase {

  /**
   * @var \Drupal\page_manager\PageInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $page;

  /**
   * @var \Drupal\page_manager\PageExecutable
   */
  protected $exectuable;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'PageExecutable test',
      'description' => '',
      'group' => 'Page Manager',
    );
  }

  /**
   * {@inheritdoc}
   *
   * @covers ::__construct
   */
  protected function setUp() {
    parent::setUp();
    $this->page = $this->getMock('Drupal\page_manager\PageInterface');
    $this->exectuable = new PageExecutable($this->page);
  }

  /**
   * @covers ::getPage
   */
  public function testGetPage() {
    $this->assertSame($this->page, $this->exectuable->getPage());
  }

  /**
   * @covers ::selectPageVariant
   */
  public function testSelectPageVariant() {
    $event_dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
    $container = new ContainerBuilder();
    $container->set('event_dispatcher', $event_dispatcher);
    \Drupal::setContainer($container);

    $page_variant1 = $this->getMock('Drupal\page_manager\Plugin\PageVariantInterface');
    $page_variant1->expects($this->once())
      ->method('access')
      ->will($this->returnValue(FALSE));
    $page_variant1->expects($this->never())
      ->method('init');

    $page_variant2 = $this->getMock('Drupal\page_manager\Plugin\PageVariantInterface');
    $page_variant2->expects($this->once())
      ->method('access')
      ->will($this->returnValue(TRUE));
    $page_variant2->expects($this->once())
      ->method('init')
      ->with($this->exectuable)
      ->will($this->returnValue($page_variant2));
    $this->page->expects($this->once())
      ->method('getPageVariants')
      ->will($this->returnValue(array(
        'variant1' => $page_variant1,
        'variant2' => $page_variant2,
      )));

    $this->assertSame($page_variant2, $this->exectuable->selectPageVariant());
  }

  /**
   * @covers ::addContext
   */
  public function testAddContext() {
    $context = new Context(array('bar'));
    $this->exectuable->addContext('foo', $context);
    $contexts = $this->exectuable->getContexts();
    $this->assertSame(array('foo' => $context), $contexts);
  }

  /**
   * @covers ::getContexts
   */
  public function testGetContexts() {
    $context = new Context(array('bar'));
    $event_dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
    $event_dispatcher->expects($this->once())
      ->method('dispatch')
      ->will($this->returnCallback(function ($event_name, PageManagerContextEvent $event) use ($context) {
        $event->getPageExecutable()->addContext('foo', $context);
      }));

    $container = new ContainerBuilder();
    $container->set('event_dispatcher', $event_dispatcher);
    \Drupal::setContainer($container);

    $contexts = $this->exectuable->getContexts();
    $this->assertSame(array('foo' => $context), $contexts);
  }

}
