<?php

namespace Drupal\views_feed_xml\Normalizer;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Image\Image;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\serialization\Normalizer\ComplexDataNormalizer;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Adds the file URI to embedded file entities.
 */
class EntityReferenceFieldItemDeepNormalizer extends ComplexDataNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = EntityReferenceItem::class;

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []) {

    $normalized = [];

    if (empty($context['reference_recursion_stack'])) {
      $context['reference_recursion_stack'] = [];
    }

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    if (($entity = $field_item->get('entity')->getValue()) && $entity instanceof ContentEntityInterface) {

      if ($this->needsRecursion($entity)) {
        // use key check for recursion stack array as it's much faster and the order doesn't matter
        if (isset($context['reference_recursion_stack'][$entity->uuid()])) {
          $normalized['target_recursion'] = TRUE;
        }
        else {
          $nextContext = $context;
          $nextContext['reference_recursion_stack'][$entity->uuid()] = TRUE;
          $normalized = $this->serializer->normalize(
            $entity,
            $format,
            $nextContext
          );
        }
      }

      // this part comes from Drupal\serialization\Normalizer\EntityReferenceFieldItemNormalizer::normalize,
      // we have to copy it to catch the exception from url()
      $normalized['target_type'] = $entity->getEntityTypeId();
      $normalized['target_uuid'] = $entity->uuid();

      try {
        if ($url = $entity->url('canonical', ['absolute' => FALSE])) {
          $normalized['url'] = $url;
        }
      } catch (RouteNotFoundException $e) {
        // this is expected to happen if an entity (e.g. custom entity) has no route
      }

    }

    return $normalized + parent::normalize($field_item, $format, $context);
  }

  protected function needsRecursion(ContentEntityInterface $entity) {
    return (
      $entity instanceof Paragraph
      || $entity instanceof Media
      || $entity instanceof Image
      || $entity instanceof File
    );
  }

}
