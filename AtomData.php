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
      return new AtomNode($entry_node);
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

class AtomNode {

  private $xpath;
  private $entry_node;

  public function __construct ($entry_node) {
    $this->entry_node = $entry_node;
    $this->xpath = new DOMXPath($entry_node->ownerDocument);
    $this->xpath->registerNamespace('atom', AtomDataFeedReader::$ATOM_NS);
    $this->xpath->registerNamespace('bas', AtomDataFeedReader::$BAS_NS);
  }

  public function getValue($query = null) {
    if ($query == null) {
      return $this->entry_node->nodeValue;
    } else {
      $node = $this->getNode($query);
      if ($node) {
        return $node->getValue();
      }
    }
    return null;
  }

  public function getNode($query) {
    $nodes = $this->getNodes($query);
    if ($nodes) {
      return $nodes[0];
    } else {
      return null;
    }
  }

  public function getNodes($query) {
    $children = array();
    $node_list = $this->xpath->query($query, $this->entry_node);
    for ($i = 0; $i < $node_list->length; $i++) {
      $children[] = new AtomNode($node_list->item($i));
    }
    return $children;
  }

}

class AtomDataException extends Exception {
}

