<?php
/**
 * © 2016 Valiton GmbH
 */

use Drupal\node\Entity\Node;

/**
 * Implements hook_drush_command().
 */
function views_feed_xml_drush_command() {
  $items = array();

  $items['views-feed-xml-test-feed'] = array(
      'callback' => 'views_feed_xml_test_feed',
      'description' => dt('Test command. Selects a number of articles und displays the XML exported string.'),
      'options' => [],
      'examples' => [],
  );

  return $items;
}

function views_feed_xml_run_test_feed() {

  $q = \Drupal::entityQuery('node')
      ->condition('type', 'article')
      ->sort('created', 'DESC')
      ->range(0, 50);

  $nodes = Node::loadMultiple($q->execute());
  /** @var \Symfony\Component\Serializer\Serializer $serializer */
  $serializer = \Drupal::service('views_feed_xml.serializer');

  $xml = $serializer->serialize($nodes, 'xml');
  $xml = \Drupal\views_feed_xml\Util\XmlHelper::stripInvalidControlChars($xml);

  $doc = new DOMDocument();
  $doc->loadXML($xml);
  print $doc->saveXML();

}