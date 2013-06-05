AtomData library

Started by David Megginson, 2013-06-04


This is a single-file PHP library to read chained Atom feeds, and make
each entry available in a simple interface.  Chained Atom feeds are
those that contain a "link" element at the top level with rel="next",
e.g.

  <link rel="next" href="http://example.org/feed.atom?start=200"/>

The library will follow those links transparently, so that they look
link a single, long feed to the client application.

Usage is relatively straight-forward.  You start by creating an
AtomDataFeedReader object, like this:

  $atom = new AtomDataFeedReader($feed_url);

After that, you read each entry using the "next" method, until the
method returns null (which means no more entries):

  while ($entry = $atom->next()) {
    // do something
  }

The $entry returned is an instance of AtomNode, a wrapper around a DOM
node to simplify data retrieval.  It uses XPath expressions to select
parts of an entry, with the "atom" and "bas" namespaces preset.  For
example, to get the title of an entry, you can do this:

  $title = $entry->getValue('atom:title');

To get the reference number of a tender notice, you can do this:

  $reference_number = $entry->getValue('atom:content/bas:tender-notice/bas:reference-number');

The following is equivalent to the above:

  $notice = $entry->getNode('atom:content/bas:tender-notice');
  $reference_number = $notice->getValue('bas:reference-number');

The advantage of using getNode() to get a subbranch is that you can
simplify many queries against the same node:

  $notice = $entry->getNode('atom:content/bas:tender-notice');
  $reference_number = $notice->getValue('bas:reference-number');
  $solictation_number = $notice->getValue('bas:solicitation-number');
  $title_en = $notice->getValue('bas:title-en');
  $title_fr = $notice->getValue('bas:title-fr');

You can get repeated values in an array using the getValues() method:

  $gsins = $notice->getValues('bas:gsin');

You can also get repeated nodes:

  $attachments = $notice->getNodes('bas:attachment');

See test.php for a simple demo script.

--end--
