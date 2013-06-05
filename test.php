<?php

require_once('AtomData.php');

$atom = new AtomDataFeedReader('http://dummy.localdomain/sample-dump.xml');

while ($entry = $atom->next()) {
  print($entry->getTitle() . "\n");
}