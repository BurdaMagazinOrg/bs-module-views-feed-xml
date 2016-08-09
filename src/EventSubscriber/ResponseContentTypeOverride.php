<?php

namespace Drupal\views_feed_xml\EventSubscriber;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Validator\Exception\BadMethodCallException;

class ResponseContentTypeOverride implements EventSubscriberInterface {

  protected $contentType;

  /**
   * Sets a content type override if one was set using setContentType()
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onRespond(FilterResponseEvent $event) {
    if (!$event->isMasterRequest() || !isset($this->contentType)) {
      return;
    }

    $response = $event->getResponse();
    $response->headers->set('Content-Type', $this->contentType);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onRespond'];
    return $events;
  }

  public function setContentType($contentType) {
    if (isset($this->contentType)) {
      throw new BadMethodCallException(sprintf('The content type has already been set to %s, cannot change to %s', $this->contentType, $contentType));
    }
    $this->contentType = $contentType;
  }

}
