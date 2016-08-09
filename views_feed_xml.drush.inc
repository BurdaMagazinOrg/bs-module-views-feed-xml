<?php
/**
 * © 2016 Valiton GmbH
 */

use Drupal\node\Entity\Node;

/**
 * Implements hook_drush_command().
 */
function msn_feed_drush_command() {
    $items = array();

    $items['msn-test-feed'] = array(
        'callback' => 'msn_feed_run_test_feed',
        'description' => dt('Test command'),
        'options' => [],
        'examples' => [],
    );

    return $items;
}

function msn_feed_run_test_feed() {

    $q = \Drupal::entityQuery('node')
        ->condition('type', 'article')
        ->sort('created', 'DESC')
        ->range(0, 10);
    $nodes = Node::loadMultiple($q->execute());
    /** @var \Symfony\Component\Serializer\Serializer $serializer */
    $serializer = \Drupal::service('views_feed_xml.serializer');
    $test = $serializer->serialize($nodes, 'xml');
    print_r($test);
}