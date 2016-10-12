<?php

namespace Drupal\views_feed_xml\Plugin\views\style;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\rest\Plugin\views\style\Serializer;
use Drupal\xsl_process\StylesheetProcessor;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Default style plugin to render an RSS feed.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "serializer_xml",
 *   title = @Translation("XML Transformed to Feed"),
 *   help = @Translation("Generates an XML feed from a data display and an XSL transformer plugin."),
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
    array $serializer_format_providers,
    PluginManagerInterface $xslProcessPluginManager,
    LanguageManagerInterface $languageManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer, $serializer_formats, $serializer_format_providers);
    $this->xslProcessPluginManager = $xslProcessPluginManager;
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
      $container->getParameter('serializer.format_providers'),
      $container->get('plugin.manager.xsl_process'),
      $container->get('language_manager')
    );
  }

  protected function defineOptions() {
    $options = parent::defineOptions();
    // allow XML only
    $options['formats'] = ['default' => ['xml']];
    $options['xml_base'] = ['default' => ''];
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

    $form['xml_base'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Base URL for feed links'),
      '#default_value' => $this->options['xml_base'],
      '#description' => $this->t('Sets the "xml:base" attribute on the feed root element. Can be used by consumers to resolve relative URLs. Will use Drupal\'s $base_url if empty.'),
      '#maxlength' => 1024,
    );

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
    // override for parent style - these are properties accessed by parent:render()
    // to select the appropriate output format
    $this->options['formats'] = ['xml'];
    $this->displayHandler->setContentType('xml');
    $this->displayHandler->setMimeType($this->options['content_type']);

    $xml = parent::render();

    // load plugin and transform to final xml
    $plugin = $this->xslProcessPluginManager->createInstance(
      $this->options['processor']
    );
    $stylesheetProcessor = new StylesheetProcessor($plugin);

    // set additional parameters
    if (!empty($this->options['rendering_language'])) {
      $parameters['feed_language'] = $this->options['rendering_language'];
    } else {
      $parameters['feed_language'] = $this->languageManager
        ->getCurrentLanguage()
        ->getId();
    }

    $link_display_id = $this->displayHandler->getLinkDisplay();
    if ($link_display_id && $display = $this->view->displayHandlers->get($link_display_id)) {

      $url = $this->view->getUrl(NULL, $link_display_id);
      $url_string = $url->setOptions(['absolute' => FALSE])->toString();
      $parameters['feed_link'] = $url_string;
    }

    $parameters['feed_description'] = $this->options['description'];
    $parameters['feed_title'] = $this->view->getTitle();
    $parameters['feed_base'] = $this->getXmlBase();

    foreach ($parameters as $name => $value) {
      $stylesheetProcessor->getXsltProcessor()->setParameter('', $name, $value);
    }

    $xml = $stylesheetProcessor->transform($xml);


    return $xml;
  }

  protected function getPluginOptions() {
    $plugins = $this->xslProcessPluginManager->getDefinitions();
    $options = [];
    foreach ($plugins as $plugin) {
      $options[$plugin['id']] = $plugin['name'];
    }
    return $options;
  }

  protected function getXmlBase() {
    global $base_url;
    return empty($this->options['xml_base']) ? $base_url : $this->options['xml_base'];
  }

}
