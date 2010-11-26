<?php
	/*

	Dreambox UMSP E2 plugin by Toni
	http://forum.wdlxtv.com/viewtopic.php?f=49&t=320

	Wrapper provided by shunte to conform to latest WEC based configuration

	*/

	function _pluginMain($prmQuery) {

		# If the DREAMBOX_HOSTNAME is not defined, the whole
		# plugin is not visible in the menu
   
		if (file_exists('/conf/config')) 
		{
			$config = file_get_contents('/conf/config');         
    			if(preg_match('/DREAMBOX_HOSTNAME=\'(.+)\'/', $config, $m)) 
			{
				$dreamboxAddress = $m[1];
    			}
		}
 
		if(isset($dreamoxAddress)&&($dreamoxAddress!=''))
		{

			$myMediaItems[] = array(
				'id'		=> 'umsp://plugins//dreambox-e2/dreambox-channels',
				'parentID'	=> 'umsp://plugins/dreambox-e2',
				'restricted'	=> '1',
				'dc:title'	=> 'Dreambox E2 Channels',
				'upnp:class'	=> 'object.container',
				'upnp:album_art'=> '',
			);
			$myMediaItems[] = array(
				'id'		=> 'umsp://plugins//dreambox-e2/dreambox-recordings',
				'parentID'	=> 'umsp://plugins/dreambox-e2',
				'restricted'	=> '1',
				'dc:title'	=> 'Dreambox E2 Recordings',
				'upnp:class'	=> 'object.container',
				'upnp:album_art'=> '',
			);

			return $myMediaItems;

		}
	} # end function
?>

