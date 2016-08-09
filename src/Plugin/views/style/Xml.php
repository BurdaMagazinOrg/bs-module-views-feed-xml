<?php

namespace Drupal\views_feed_xml\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\xsl_process\StylesheetProcessor;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\xsl_process\XslProcessorPluginManager;

/**
 * Default style plugin to render an RSS feed.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "xml",
 *   title = @Translation("XML Feed"),
 *   help = @Translation("Generates an Xml feed from a view."),
 *   display_types = {"feed"}
 * )
 */
class Xml extends StylePluginBase {

  /**
   * Does the style plugin for itself support to add fields to it's output.
   *
   * @var bool
   */
  protected $usesRowPlugin = FALSE;

  public function attachTo(array &$build, $display_id, Url $feed_url, $title) {
    $url_options = array();
    $input = $this->view->getExposedInput();
    if ($input) {
      $url_options['query'] = $input;
    }
    $url_options['absolute'] = TRUE;

    $url = $feed_url->setOptions($url_options)->toString();

    // Add the RSS icon to the view.
    $this->view->feedIcons[] = [
      '#theme' => 'feed_icon',
      '#url' => $url,
      '#title' => $title,
    ];

    // Attach a link to the RSS feed, which is an alternate representation.
    $build['#attached']['html_head_link'][][] = array(
      'rel' => 'alternate',
      'type' => 'application/xml',
      'title' => $title,
      'href' => $url,
    );
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['description'] = array('default' => '');

    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['description'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('RSS description'),
      '#default_value' => $this->options['description'],
      '#description' => $this->t('This will appear in the RSS feed itself.'),
      '#maxlength' => 1024,
    );

    // TODO inject
    $manager = \Drupal::service('plugin.manager.xsl_process');
    $plugins = $manager->getDefinitions();
    $options = [];
    foreach ($plugins as $plugin) {
      $options[$plugin['id']] = $plugin['name'];
    }

    $form['processor'] = array(
      '#type' => 'select',
      '#title' => $this->t('Processor to use'),
      '#default_value' => $this->options['processor'],
      '#description' => $this->t('The XSL processor plugin to transform the serialized XML.'),
      '#options' => $options
    );

  }

  /**
   * Get RSS feed description.
   *
   * @return string
   *   The string containing the description with the tokens replaced.
   */
  public function getDescription() {
    return $this->options['description'];
  }

  public function render() {
    $entities = [];
    foreach ($this->view->result as $row) {
      $entities[] = $row->_entity;
    }
    // TODO inject
    $serializer = \Drupal::service('views_feed_xml.serializer');
    $xml = $serializer->serialize($entities, 'xml');
    // TODO inject
    /** @var XslProcessorPluginManager $manager */
    $manager = \Drupal::service('plugin.manager.xsl_process');
    $plugin = $manager->createInstance($this->options['processor']);
    $stylesheet = new StylesheetProcessor($plugin);
    $xml = $stylesheet->transform($xml);
    $build = array(
      '#markup' => Markup::create($xml)
    );
    // TODO inject
    \Drupal::service('views_feed_xml.response_content_type_override')->setContentType('application/xml');
    return $build;
  }

}
