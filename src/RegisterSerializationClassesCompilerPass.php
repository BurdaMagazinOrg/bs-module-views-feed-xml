<?php

namespace Drupal\views_feed_xml;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Drupal\serialization\RegisterSerializationClassesCompilerPass as BaseCompilerPass;

/**
 * Adds services tagged 'normalizer' and 'encoder' to the Serializer.
 */
class RegisterSerializationClassesCompilerPass extends BaseCompilerPass {

  /**
   * Adds services to the Serializer.
   *
   * TODO see whether this can be done in a cleaner way.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   *   The container to process.
   */
  public function process(ContainerBuilder $container) {
    $definition = $container->getDefinition('views_feed_xml.serializer');

    // Retrieve registered Normalizers and Encoders from the container.
    foreach ($container->findTaggedServiceIds('normalizer') as $id => $attributes) {
      $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
      $normalizers[$priority][] = new Reference($id);
    }
    foreach ($container->findTaggedServiceIds('normalizer_deep') as $id => $attributes) {
      $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
      $normalizers[$priority][] = new Reference($id);
    }
    foreach ($container->findTaggedServiceIds('encoder') as $id => $attributes) {
      $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
      $encoders[$priority][] = new Reference($id);
    }

    // Add the registered Normalizers and Encoders to the Serializer.
    if (!empty($normalizers)) {
      $definition->replaceArgument(0, $this->sort($normalizers));
    }
    if (!empty($encoders)) {
      $definition->replaceArgument(1, $this->sort($encoders));
    }

    // Find all serialization formats known.
    $formats = [];
    foreach ($container->findTaggedServiceIds('encoder') as $attributes) {
      $formats[] = $attributes[0]['format'];
    }
    $container->setParameter('views_feed_xml.serializer.formats', $formats);

  }

}
