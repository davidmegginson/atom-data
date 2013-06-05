<?php

/**
 * Class to read data continuously from a chain of Atom feeds.
 */
class AtomDataFeedReader {

  public static $ATOM_NS = 'http://www.w3.org/2005/Atom';
  public static $BAS_NS = 'http://buyandsell.gc.ca/xmlns';

  private $next_url;
  private $entry_nodelist;
  private $entry_nodelist_position;

  public function __construct ($url) {
    $this->next_url = $url;
  }

  /**
   * Get the next entry in the feed.
   */
  public function next() {
    $entry_node = $this->get_next_entry_node();
    if ($entry_node == null) {
      return null;
    } else {
      return new AtomDataEntry($entry_node);
    }
  }

  /**
   * Get the DOM node for the next entry.
   */
  private function get_next_entry_node() {
    $entry_nodes = $this->get_entry_nodelist();
    if ($entry_nodes == null) {
      return null;
    } else {
      return $entry_nodes->item($this->entry_nodelist_position++);
    }
  }

  /**
   * Get a usable nodelist if possible.
   */
  private function get_entry_nodelist() {
    if ($this->entry_nodelist && $this->entry_nodelist_position < $this->entry_nodelist->length) {
      return $this->entry_nodelist;
    } else while ($this->next_url) {
      $dom = $this->load_dom($this->next_url);
      $this->entry_nodelist = $dom->getElementsByTagNameNS(self::$ATOM_NS, 'entry');
      $this->entry_nodelist_position = 0;
      $this->next_url = $this->get_next_url($dom);
      print("Next is " . $this->next_url . "\n");
      if ($this->entry_nodelist->length > 0) {
        return $this->entry_nodelist;
      }
    }
    return null;
  }

  /**
   * Load the DOM document.
   */
  private function load_dom ($url) {
    $dom = DOMDocument::load($url);
    if ($dom === false) {
      throw new AtomDataException("Cannot open Atom file at $url");
    }
    return $dom;
  }

  /**
   * Get the rel=next link from an Atom feed.
   */
  private function get_next_url($dom) {
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('atom', self::$ATOM_NS);
    return $xpath->evaluate('string(/atom:feed/atom:link[@rel="next"]/@href)');
  }

}

class AtomDataEntry {

  private $xpath;
  private $entry_node;

  public function __construct ($entry_node) {
    $this->entry_node = $entry_node;
    $this->xpath = new DOMXPath($entry_node->ownerDocument);
    $this->xpath->registerNamespace('atom', AtomDataFeedReader::$ATOM_NS);
  }

  public function getTitle() {
    return $this->xpath->evaluate('string(atom:title)', $this->entry_node);
  }

}

class AtomDataException extends Exception {
}

