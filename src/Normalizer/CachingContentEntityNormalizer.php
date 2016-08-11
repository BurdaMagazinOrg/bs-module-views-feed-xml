<?php
/**
 * © 2016 Valiton GmbH
 */

namespace Drupal\views_feed_xml\Normalizer;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\serialization\Normalizer\ContentEntityNormalizer as CoreContentEntityNormalizer;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class CachingContentEntityNormalizer extends CoreContentEntityNormalizer {

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  public function __construct(
    EntityManagerInterface $entity_manager,
    CacheBackendInterface $cache
  ) {
    parent::__construct($entity_manager);
    $this->cache = $cache;
  }

  public function normalize($object, $format = NULL, array $context = array()) {
    /** @var ContentEntityInterface $object */

    $cid = 'encoded:' . $object->getEntityTypeId() . ':' . $object->id();

    if ($normalized = $this->cache->get($cid)) {
      return $normalized->data;
    }

    $normalized = parent::normalize(
      $object,
      $format,
      $context
    );

    try {
      if ($url = $object->url('canonical', ['absolute' => FALSE])) {
        $normalized['url'] = $url;
      }
    } catch (RouteNotFoundException $e) {
      // this is expected to happen if an entity has no route
    }

    $this->cache->set($cid, $normalized, CacheBackendInterface::CACHE_PERMANENT, $object->getCacheTags());

    return $normalized;

  }

}