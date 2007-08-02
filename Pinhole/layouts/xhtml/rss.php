<?php
header("Content-type: text/xml");

echo '<?xml version="1.0" encoding="iso-8859-1"?>';

?>
<rss version="2" 
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
  xmlns:admin="http://webns.net/mvcb/"
  xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
  xmlns:content="http://purl.org/rss/1.0/modules/content/">
<channel>
<title><?=$this->feed_title?></title>
<link><?=$this->basehref?></link>
<dc:language>en-ca</dc:language>
<dc:date><?=$this->current_time?></dc:date>
<?=$this->feed?>
</channel>
</rss>
