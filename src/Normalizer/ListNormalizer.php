<?php
/**
 * © 2016 Valiton GmbH
 */

namespace Drupal\views_feed_xml\Normalizer;

use Drupal\serialization\Normalizer\ListNormalizer as BaseListNormalizer;

/**
 * Class ListNormalizer
 *
 * TODO: This class can be removed once https://www.drupal.org/node/2715141 is fixed
 *
 * @package Drupal\views_feed_xml\Normalizer
 */
class ListNormalizer extends BaseListNormalizer {

  /**
   * Copy of parent method, only difference being that we pass $context
   * to the Serializer::normalize() invocation.
   *
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = array()) {
    // don't call parent method
    $attributes = array();
    foreach ($object as $fieldItem) {
      $attributes[] = $this->serializer->normalize($fieldItem, $format, $context);
    }
    return $attributes;
  }

}