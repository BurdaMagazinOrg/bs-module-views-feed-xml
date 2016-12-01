<?php

namespace Drupal\views_feed_xml\Util;

/**
 * © 2016 Valiton GmbH
 */
class XmlHelper {

  /*
   * XML doesn't allow for chars < \x20 except for \x09, \x0A and \x0D,
   * so we strip them.
   */
  public static function stripInvalidControlChars($string) {
    return str_replace([
      "\x00",
      "\x01", "\x02", "\x03", "\x04",
      "\x05", "\x06", "\x07", "\x08",
      "\x0B", "\x0C", "\x0E", "\x0F",
      "\x10", "\x11", "\x12", "\x13",
      "\x14", "\x15", "\x16", "\x17",
      "\x18", "\x19", "\x1A", "\x1B",
      "\x1C", "\x1D", "\x1E", "\x1F",
    ], '', $string);
  }

}