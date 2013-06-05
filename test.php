<?php

require_once('AtomData.php');

$atom = new AtomDataFeedReader('http://dummy.localdomain/sample-dump.xml');

$entry = $atom->next();