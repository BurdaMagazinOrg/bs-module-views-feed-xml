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

    $items['views-feed-xml'] = array(
        'callback' => 'views_feed_xml_run_test_feed',
        'description' => dt('Test command'),
        'options' => [],
        'examples' => [],
    );

    return $items;
}

function views_feed_xml_run_test_feed() {

    $q = \Drupal::entityQuery('node')
        ->condition('type', 'article')
        ->sort('created', 'DESC')
        ->range(0, 20);
    $nodes = Node::loadMultiple($q->execute());
    /** @var \Symfony\Component\Serializer\Serializer $serializer */
    $serializer = \Drupal::service('views_feed_xml.serializer');
    $test = $serializer->serialize($nodes, 'xml');
    print_r($test);
}