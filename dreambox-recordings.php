<?php
// Dreambox E2 UMSP plugin by TOni
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
 
  $queryData = array();
  parse_str($prmQuery, $queryData);
  if ($queryData['path'] != '') {
    $path = $queryData['path'];
  } else {
     # read the locations from Dreambox
     # I assume there is only one location, in my case it's '/hdd/movie/'
 
    # Location URL, list all recordings
    $dreamboxLocationsUrl = 'web/getlocations';
 
    $reader = new XMLReader();
    $locationsXML = file_get_contents('http://' . $dreamboxAddress . '/' . $dreamboxLocationsUrl);
 
    $reader->XML($locationsXML);
    while ($reader->read()) {
      if (($reader->nodeType == XMLReader::ELEMENT) && ($reader->localName == 'e2locations')) {
        #
        # Read e2locations child nodes until end
        #
        do {
            $reader->read();
            if (($reader->nodeType == XMLReader::ELEMENT) && ($reader->localName == 'e2location')) {
              $path  = $reader->readString();
            }
        } while (!(($reader->nodeType == XMLReader::END_ELEMENT) && ($reader->localName == 'e2location')));
      }
    }
  }

# First, read the media player items, as this is the *only* way to read the subdirectories

  # Media URL, list media items under the given path
  $dreamboxMedialistUrl = 'web/mediaplayerlist?path=' . $path;
 
  $reader = new XMLReader();
  $medialistXML = file_get_contents('http://' . $dreamboxAddress . '/' . $dreamboxMedialistUrl);
 
  $reader->XML($medialistXML);
  while ($reader->read()) {
    if (($reader->nodeType == XMLReader::ELEMENT) && ($reader->localName == 'e2file')) {
      #
      # Read e2file child nodes until end
      #
      do {
          $reader->read();
          if (($reader->nodeType == XMLReader::ELEMENT) && ($reader->localName == 'e2isdirectory')) {
            $newDir['isdirectory']  = $reader->readString();
          }
          if (($reader->nodeType == XMLReader::ELEMENT) && ($reader->localName == 'e2servicereference')) {
            $newDir['directory'] = utf8_decode($reader->readString());
          }
      } while (!(($reader->nodeType == XMLReader::END_ELEMENT) && ($reader->localName == 'e2file')));
      #
      # New channelinfo item parsed. Now add as media item, unless it's the trashcan directory
      # or the parent directory (WD TV remote has a 'back' button)
      #
   
      if ( $newDir['isdirectory'] == 'True' &&
           strncmp($path, $newDir['directory'], strlen($newDir['directory'])) &&
           $newDir['directory'] != $path . '.trashcan/' ) {          
        $retMediaItems[] = array (
          'id'        => 'umsp://plugins/dreambox-e2/dreambox-recordings?path=' . $newDir['directory'],
          'dc:title'  => basename($newDir['directory']),
          'upnp:class' => 'object.container'
        );
      }
    } # end if
  } #end while
 
 
# Now, read the movie information from this directory
 
  # Movies URL, list all recordings
  $dreamboxMovielistUrl = 'web/movielist?dirname=' . $path;
 
  #$reader = new XMLReader();
  $movielistXML = file_get_contents('http://' . $dreamboxAddress . '/' . $dreamboxMovielistUrl);
 
  $reader->XML($movielistXML);
  while ($reader->read()) {
    if (($reader->nodeType == XMLReader::ELEMENT) && ($reader->localName == 'e2movie')) {
      #
      # Read channelinfo child nodes until end
      #
      do {
          $reader->read();
              if (($reader->nodeType == XMLReader::ELEMENT) && ($reader->localName == 'e2filename')) {
          $newMovie['id']  = $reader->readString();
        }
              if (($reader->nodeType == XMLReader::ELEMENT) && ($reader->localName == 'e2title')) {
          $newMovie['title'] = utf8_decode($reader->readString());
              }
              if (($reader->nodeType == XMLReader::ELEMENT) && ($reader->localName == 'e2description')) {
          $newMovie['description'] = utf8_decode($reader->readString());
              }
              if (($reader->nodeType == XMLReader::ELEMENT) && ($reader->localName == 'e2filesize')) {
          $newMovie['filesize'] = $reader->readString();
              }
      } while (!(($reader->nodeType == XMLReader::END_ELEMENT) && ($reader->localName == 'e2movie')));
      #
      # New channelinfo item parsed. Now add as media item:
      #
   
      $retMediaItems[] = array (
        'id'        => $newMovie['id'],
        'res'       => 'http://' . $dreamboxAddress . ':80/file?file=' . $newMovie['id'],
#        'res'       => 'http://localhost/umsp/plugins/dreambox-e2/dreambox-proxy.php?itemUrl=http://' . $dreamboxAddress . ':80/file?file=' . $newMovie['id'],
        'dc:title'  => $newMovie['title'],
        'desc'      => $newMovie['description'],
        'size'      => $newMovie['filesize'],
        'upnp:class'    => 'object.item.videoitem',
        'protocolInfo'    => 'http-get:*:video/mpeg:*'
      );
    } # end if
  } #end while

  return $retMediaItems; 
} # end function
?>

