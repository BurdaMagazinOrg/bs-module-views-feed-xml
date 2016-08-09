<?php

namespace Drupal\views_feed_xml\Normalizer;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Image\Image;
use Drupal\file\Entity\File;
use Drupal\media_entity\Entity\Media;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\serialization\Normalizer\EntityReferenceFieldItemNormalizer;

/**
 * Adds the file URI to embedded file entities.
 */
class EntityReferenceFieldParagraphItemNormalizer extends EntityReferenceFieldItemNormalizer {

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
    $values = [];

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    if (($entity = $field_item->get('entity')->getValue())) {
        if ($entity instanceof Paragraph || $entity instanceof Media || $entity instanceof Image || $entity instanceof File) {
            $values = $this->serializer->normalize($entity, $format, $context);
        }
    }

    return $values + parent::normalize($field_item, $format, $context);
  }

}
