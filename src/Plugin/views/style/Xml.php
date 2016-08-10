<?php

namespace Drupal\views_feed_xml\Plugin\views\style;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\rest\Plugin\views\style\Serializer;
use Drupal\xsl_process\StylesheetProcessor;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Default style plugin to render an RSS feed.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "serializer_xml",
 *   title = @Translation("XML Transformed"),
 *   help = @Translation("Generates an XML feed from a data display."),
 *   display_types = {"data"}
 * )
 */
class Xml extends Serializer {

  /**
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $xslProcessPluginManager;

  /**
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * @var \Symfony\Component\EventDispatcher\EventSubscriberInterface
   */
  protected $responseContentTypeOverride;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    SerializerInterface $serializer,
    array $serializer_formats,
    PluginManagerInterface $xslProcessPluginManager,
    EventSubscriberInterface $responseContentTypeOverride,
    LanguageManagerInterface $languageManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer, $serializer_formats);
    $this->xslProcessPluginManager = $xslProcessPluginManager;
    $this->responseContentTypeOverride = $responseContentTypeOverride;
    $this->languageManager = $languageManager;
  }

  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('views_feed_xml.serializer'),
      $container->getParameter('views_feed_xml.serializer.formats'),
      $container->get('plugin.manager.xsl_process'),
      $container->get('views_feed_xml.response_content_type_override'),
      $container->get('language_manager')
    );
  }

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['formats'] = ['default' => ['xml']];
    // TODO define all options
    $options['description'] = ['default' => ''];
    $options['content_type'] = ['default' => 'application/xml; charset=utf-8'];
    // 'identity' is always available
    $options['processor'] = ['default' => 'identity'];

    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    unset($form['formats']);

    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('RSS description'),
      '#default_value' => $this->options['description'],
      '#description' => $this->t('This will appear in the RSS feed itself.'),
      '#maxlength' => 1024,
    ];

    $form['content_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Content Type header of resulting response'),
      '#default_value' => $this->options['content_type'],
      '#description' => $this->t('.'),
      '#maxlength' => 64,
    ];

    $form['processor'] = [
      '#type' => 'select',
      '#title' => $this->t('Processor to use'),
      '#default_value' => $this->options['processor'],
      '#description' => $this->t(
        'The XSL processor plugin to transform the serialized XML.'
      ),
      '#options' => $this->getPluginOptions(),
      '#required' => TRUE,
    ];

  }

  public function render() {
    // override for parent style
    $this->options['formats'] = ['xml'];
    $this->displayHandler->setContentType('xml');
    $this->displayHandler->setMimeType($this->options['content_type']);

    $xml = parent::render();

    // load plugin and transform to final xml
    $plugin = $this->xslProcessPluginManager->createInstance(
      $this->options['processor']
    );
    $stylesheetProcessor = new StylesheetProcessor($plugin);

    // params
    $parameters['feed_language'] = $this->languageManager
      ->getCurrentLanguage()
      ->getId();

    $link_display_id = $this->displayHandler->getLinkDisplay();
    if ($link_display_id && $display = $this->view->displayHandlers->get($link_display_id)) {
      $url = $this->view->getUrl(NULL, $link_display_id);
      $url_options = ['absolute' => TRUE];
      if (!empty($this->view->exposed_raw_input)) {
        $url_options['query'] = $this->view->exposed_raw_input;
      }
      $url_string = $url->setOptions($url_options)->toString();
      $parameters['feed_link'] = $url_string;
    }

    $parameters['feed_description'] = $this->options['description'];
    $parameters['feed_title'] = $this->view->getTitle();

    foreach ($parameters as $name => $value) {
      $stylesheetProcessor->getXsltProcessor()->setParameter('', $name, $value);
    }

    $xml = $stylesheetProcessor->transform($xml);


    return $xml;
  }

  private function getPluginOptions() {
    $plugins = $this->xslProcessPluginManager->getDefinitions();
    $options = [];
    foreach ($plugins as $plugin) {
      $options[$plugin['id']] = $plugin['name'];
    }
    return $options;
  }

}
