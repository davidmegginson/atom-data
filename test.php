<?php
/**
 * @file Demonstration of the AtomData library for BuyAndSell.
 *
 * This demonstration reads a feed of tender notices
 */
require_once('AtomData.php');

// Use this to count tender notices for the demo
$counter = 0;

// This is all we have to do to start reading the feed
// (Using a local file, but can also be a URL)
$atom = new AtomDataFeedReader('./samples/sample-01.atom');

// Read every entry in all chained Atom files
while ($entry = $atom->next()) {

  // Get the notice node as a shortcut
  // (Otherwise, we'd have to include "atom:content/bas:tender-node"
  // before everything)
  $notice = $entry->getNode('atom:content/bas:tender-notice');

  // Show some info about the entry
  printf("Tender notice %d: %s\n", ++$counter, $entry->getValue('atom:id'));
  printf("  Updated: %s\n", $entry->getValue('atom:updated'));
  printf("  Amendment: %s\n", $notice->getValue('bas:amendment-number'));
  printf("  Reference number: %s\n", $notice->getValue('bas:reference-number'));
  printf("  Solicitation number: %s\n", $notice->getValue('bas:solicitation-number'));
  printf("  Title (en): %s\n", $notice->getValue('bas:title-en'));
  printf("  Title (fr): %s\n", $notice->getValue('bas:title-fr'));
  printf("  Closing date: %s\n", $notice->getValue('bas:date-closing'));
  printf("  GSIN(s): %s\n", join(',', $notice->getValues('bas:gsin')));
}