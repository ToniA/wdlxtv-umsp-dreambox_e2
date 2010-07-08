<?php
// Dreambox UMSP E2 plugin by Toni
// http://forum.wdlxtv.com/viewtopic.php?f=49&t=320

function _pluginMain($prmQuery) {

  # If the DREAMBOX_HOSTNAME is not defined, the whole
  # plugin is not visible in the menu
   
  if (file_exists('/conf/config')) {
    $config = file_get_contents('/conf/config');
         
    if(preg_match('/DREAMBOX_HOSTNAME=\'(.+)\'/', $config, $results)) {
      $dreamboxAddress = $results[1];
    }
  }
 
  # Do we have a bouquet as the parameter?

  $queryData = array();
  parse_str($prmQuery, $queryData);
  if ($queryData['bouquet'] != '') {
    $bouquet = $queryData['bouquet'];
  } else {
    $bouquet = '';   
  }

  # No, we need to ask for the list of bouquets
  if ( $bouquet == '' ) {

    # Get all bouquets
    $dreamboxBouquetsUrl = 'web/getservices';
 
    $reader = new XMLReader();
    $bouquetsXML = file_get_contents('http://' . $dreamboxAddress . '/' . $dreamboxBouquetsUrl);
 
    $reader->XML($bouquetsXML);
    while ($reader->read()) {
      if (($reader->nodeType == XMLReader::ELEMENT) && ($reader->localName == 'e2service')) {
        #
        # Read e2service child nodes until end
        #

        do {
            $reader->read();
            if (($reader->nodeType == XMLReader::ELEMENT) && ($reader->localName == 'e2servicereference')) {
              $newBouquet['sref']  = $reader->readString();
            }
            if (($reader->nodeType == XMLReader::ELEMENT) && ($reader->localName == 'e2servicename')) {
              $newBouquet['title'] = utf8_decode($reader->readString());
            }
        } while (!(($reader->nodeType == XMLReader::END_ELEMENT) && ($reader->localName == 'e2service')));
        #
        # New bouquet item parsed. Now add as media item:
        #
         
        $retMediaItems[] = array (
          'id'         => 'umsp://plugins/dreambox-e2/dreambox-channels?bouquet=' . $newBouquet['sref'],
          'dc:title'   => $newBouquet['title'],
          'upnp:class' => 'object.container'
        );   
      } # end if
    } #end while

    # If there's just one bouquet, jump directly into the bouquet contents
    # Othervise show the bouquet list

    if ( count($retMediaItems) == 1 ) {   
      return _pluginMain('bouquet=' . $newBouquet['sref']);
    } else {
      return $retMediaItems;     
    }
  } else { 
    # We have a bouquet sRef as a parameter -> list all channels in that bouquet

    # $bouquet looks like '1:7:1:0:0:0:0:0:0:0:FROM BOUQUET \"userbouquet.favourites.tv\" ORDER BY bouquet',
    # but we should feed it in as '1:7:1:0:0:0:0:0:0:0:FROM%20BOUQUET%20%22userbouquet.favourites.tv%22%20ORDER%20BY%20bouquet'

    $dreamboxServiceUrl = 'web/getservices?sRef=' . str_replace('\\"', "%22", str_replace(" ", "%20", $bouquet));
   
    $reader = new XMLReader();
    $channellistXML = file_get_contents('http://' . $dreamboxAddress . '/' . $dreamboxServiceUrl);
 
    $reader->XML($channellistXML);
    while ($reader->read()) {
      if (($reader->nodeType == XMLReader::ELEMENT) && ($reader->localName == 'e2service')) {
        #
        # Read channelinfo child nodes until end
        #
        do {
          $reader->read();
          if (($reader->nodeType == XMLReader::ELEMENT) && ($reader->localName == 'e2servicereference')) {
            $newChannel['id']  = $reader->readString();
          }
          if (($reader->nodeType == XMLReader::ELEMENT) && ($reader->localName == 'e2servicename')) {
            $newChannel['title'] = $reader->readString();
          }
        } while (!(($reader->nodeType == XMLReader::END_ELEMENT) && ($reader->localName == 'e2service')));
        #
        # New channelinfo item parsed. Now add as media item:
        #
   
        $retMediaItems[] = array (
          'id'             => $newChannel['id'],
          'res'            => 'http://localhost/umsp/plugins/dreambox-e2/dreambox-proxy.php?itemUrl=http://' . $dreamboxAddress . ':8001/' . $newChannel['id'],
          'dc:title'       => $newChannel['title'],
          'upnp:class'     => 'object.item.videoitem',
          # picons should be under /usr/lib/enigma2/python/Plugins/Extensions/WebInterface/web-data/streampage on the Dreambox
          'upnp:album_art' => 'http://' . $dreamboxAddress . '/web-data/streampage/' . rawurlencode($newChannel['title']) . '.png',
          'protocolInfo'   => '*:*:*:*'
        );
      } # end if
    } # end while
    return $retMediaItems; 
  } # end if
}
?>

