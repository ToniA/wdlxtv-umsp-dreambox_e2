<?php

    # Immediately respond to 'HEAD' requests
    if ( $_SERVER[''] == 'HEAD' ) {
       header('Content-Type: video/mpeg');
       exit;
    }

    # itemUrl is like
    # itemUrl=http://dm7025:8001/1:0:1:11:1001:20F6:EEEE0000:0:0:0:

    $rawURL = $_GET['itemUrl'];
    $parsedURL = parse_url($rawURL);

    $itemHost = $parsedURL['host'];
    $itemPort = $parsedURL['port'];
    $itemPath = $parsedURL['path'];
    $itemQuery = $parsedURL['query'];

    # Only one stream at a time, so refuse to connect if there's already an established connection
    #system('netstat -a | grep ' . $itemHost . ' | grep ESTABLISHED', $retval);

    #if ( ! $retval ) {
    #   header('Content-Type: video/mpeg');
    #   exit;
    #kkkk}

    _dreamboxGet($itemHost, $itemPort, $itemPath);

    function _dreamboxGet($prmHost, $prmPort, $prmPath) {
       $fp = fsockopen($prmHost, $prmPort, $errno, $errstr);
       if (!$fp) {
          echo "$errstr ($errno)<br />\n";
       } else {
          # Create the HTTP GET request
         
          $out  = "GET $prmPath HTTP/1.0\r\n";
          $out .= "User-Agent: Wget/1.12\r\n";
          $out .= "Accept: */*\r\n";
          $out .= "Host: $prmHost:$prmPort\r\n";
          $out .= "Connection: Keep-Alive\r\n";
          $out .= "\r\n";
       
          fwrite($fp, $out);
         
          # Create HTTP headers for WDTV
          # Streaming starts much faster with these
          # 'Content-Size' and 'Content-Length' headers
         
          header("Content-Type: video/mpeg");
          header("Content-Size: 65535");
          header("Content-Length: 65535");
         
          # Ignore the original headers
         
          $headerpassed = false;
          while ($headerpassed == false) {
             $line = fgets($fp);
             if ( $line == "\r\n" ) {
                $headerpassed = true;
             }
          }
             
          # Pass thru the DVB transport stream
          # It's important to disable the time limit
         
          set_time_limit(0);
          fpassthru($fp);
          set_time_limit(30);
         
          fclose($fp);
       }
    }
?>

