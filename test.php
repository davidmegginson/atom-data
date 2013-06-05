<?php

require_once('AtomData.php');

$atom = new AtomDataFeedReader('http://dummy.localdomain/sample-dump.xml');

while ($entry = $atom->next()) {
  print($entry->getValue('atom:id') . "\n");
  print($entry->getValue('atom:title') . "\n");
}