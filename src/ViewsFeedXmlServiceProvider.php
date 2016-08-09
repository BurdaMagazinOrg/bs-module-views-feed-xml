<?php

namespace Drupal\views_feed_xml;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\serialization\RegisterEntityResolversCompilerPass;

/**
 * Serialization dependency injection container.
 */
class ViewsFeedXmlServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $container->addCompilerPass(new RegisterSerializationClassesCompilerPass());
    $container->addCompilerPass(new RegisterEntityResolversCompilerPass());
  }

}
