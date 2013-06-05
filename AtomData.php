<?php
/**
 * @file AtomData - a simple library for BAS Atom feeds.
 *
 * This library is smart enough to read chained feeds using the
 * rel="next" Atom link.  Two namespaces will be predefined:
 *
 * atom - The Atom Namespace
 * bas  - The BuyAndSell Namespace
 *
 * Usage is relatively straight-forward:
 *
 * $atom = new AtomDataFeedReader($url);
 * while ($entry = $atom->next()) {
 *  $title = $entry->getValue('atom:title');
 *  print("The title is $title\n");
 * }
 *
 * On error, the library will through an AtomDataException,
 * which you can catch for more-robust error handling.
 */

/**
 * Class to read data continuously from a chain of Atom feeds.
 */
class AtomDataFeedReader {

  /**
   * Atom namespace.
   */
  public static $ATOM_NS = 'http://www.w3.org/2005/Atom';

  /**
   * Buyandsell namespace.
   */
  public static $BAS_NS = 'http://buyandsell.gc.ca/xmlns';

  private $next_url;
  private $entry_nodelist;
  private $entry_nodelist_position;

  /**
   * Construct a new AtomFeedReader
   *
   * This constructor simply sets the first URL in the chain.  No
   * reading happens until you invoke the {@link #next} method.
   *
   * @param $url The URL of the first Atom file in the chain.
   */
  public function __construct ($url) {
    $this->next_url = $url;
  }

  /**
   * Get the next entry in the feed.
   *
   * This function will keep reading through multiple Atom files,
   * provided that they are chained using rel="next" in a top-level
   * Atom "link" element.  As a result, the separation into separate
   * Atom files is invisible to the caller (unless a file fails to
   * load).
   *
   * @return The next entry in the
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
   *
   * @return The DOM node for the next entry, or null if the chain is
   * finished.
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
   *
   * This method will try to load the next Atom file if the current
   * one is exhausted.
   *
   * @return A DOMNodeList with at least one item remaining, or null
   * if none is available.
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
   * Load a DOM document.
   *
   * @param $url The URL of the DOM document.
   * @return The DOM document
   * @exception AtomDataException if it's not possible to load the document.
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
   *
   * @param $dom The DOM document object.
   * @return The rel="next" link, or null if non is available.
   */
  private function get_next_url($dom) {
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('atom', self::$ATOM_NS);
    return $xpath->evaluate('string(/atom:feed/atom:link[@rel="next"]/@href)');
  }

}

/**
 * Wrapper around a DOM node.
 *
 * This wrapper makes it easier to query an entry, as in the following
 * example:
 *
 * <pre>
 * $entry = $atom->next();
 * $notice = $entry->getNode('//bas:tender-notice');
 * $title_en = $notice->getValue('bas:title-en');
 * </pre>
 */
class AtomNode {

  private $xpath;
  private $dom_node;

  /**
   * Construct a new node wrapper.
   *
   * @param 
   */
  public function __construct (DOMNode $dom_node) {
    $this->dom_node = $dom_node;
    $this->xpath = new DOMXPath($dom_node->ownerDocument);
    $this->xpath->registerNamespace('atom', AtomDataFeedReader::$ATOM_NS);
    $this->xpath->registerNamespace('bas', AtomDataFeedReader::$BAS_NS);
  }

  /**
   * Get the string value of a node.
   *
   * If the $query argument is omitted, get the value of the current
   * node; otherwise, get the value of the first node matching the
   * expression.
   *
   * @param $query The XPath query expression, or null to use the
   * current node.
   * @return The string value, or null if there is no matching node.
   */
  public function getValue($query = null) {
    if ($query == null) {
      return $this->dom_node->nodeValue;
    } else {
      $node = $this->getNode($query);
      if ($node) {
        return $node->getValue();
      }
    }
    return null;
  }

  /**
   * Get all string values matching an XPath expression.
   *
   * @param $query The XPath query expression.
   * @return An array of string values for all matching nodes (possibly empty).
   */
  public function getValues($query) {
    $values = array();
    $nodes = $this->getNodes($query);
    foreach ($nodes as $node) {
      $values[] = $node->getValue();
    }
    return $values;
  }

  /**
   * Get the first node matching an XPath expression.
   *
   * @param $query The XPath query expression.
   * @return The first matching node, or null if none matches.
   */
  public function getNode($query) {
    $nodes = $this->getNodes($query);
    if ($nodes) {
      return $nodes[0];
    } else {
      return null;
    }
  }

  /**
   * Return all nodes matching an XPath expression.
   *
   * @param $query The XPath query expression.
   * @return An array of matching nodes (possibly empty).
   */
  public function getNodes($query) {
    $children = array();
    $node_list = $this->xpath->query($query, $this->dom_node);
    for ($i = 0; $i < $node_list->length; $i++) {
      $children[] = new AtomNode($node_list->item($i));
    }
    return $children;
  }

}

/**
 * An Atom-specific exception.
 */
class AtomDataException extends Exception {
}
