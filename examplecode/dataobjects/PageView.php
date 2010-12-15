<?php

/**
 *
 * @author marcus@silverstripe.com.au
 * @license http://silverstripe.org/bsd-license/
 */
class PageView extends DataObject {
	public static $db = array(
		'UserAgent' => 'Varchar(128)',
		'BrowserVersion' => 'Varchar',
		'Browser' => 'Varchar',
		'Platform' => 'Varchar',
		'ViewDayName' => 'Varchar',
		'ViewMonth' => 'Varchar',
		'ViewDay' => 'Int',
		'ViewYear' => 'Int',
		'PageName' => 'Varchar',
		'ViewNum' => 'Int',
	);
	
	public static $defaults = array(
		'ViewNum' => 1
	);
	
	public static $has_one = array(
		'Page' => 'Page',
	);

	public function onBeforeWrite() {
		parent::onBeforeWrite();
		$browser = getBrowser();
		$this->UserAgent = $browser['user_agent'];
		$this->Browser = $browser['name'];
		$this->BrowserVersion = $browser['version'];
		$this->Platform = $browser['platform'];
		
		$this->ViewDay = date('d');
		$this->ViewDayName = date('l');
		$this->ViewMonth = date('F');
		$this->ViewYear = date('Y');
	}
}


function getBrowser() 
{ 
    $u_agent = $_SERVER['HTTP_USER_AGENT']; 
	// $u_agent = 'MSIE win32';
//	$u_agent = 'mac os x Safari 2';
	
//	$u_agent = 'mac os x Chrome 8';
	
//	$u_agent = 'linux Chrome 8';
	
    $bname = 'Unknown';
    $platform = 'Unknown';
    $version= "";

    //First get the platform?
    if (preg_match('/linux/i', $u_agent)) {
        $platform = 'linux';
    }
    elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
        $platform = 'mac';
    }
    elseif (preg_match('/windows|win32/i', $u_agent)) {
        $platform = 'windows';
    }
    
    // Next get the name of the useragent yes seperately and for good reason
    if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent)) 
    { 
        $bname = 'Internet Explorer'; 
        $ub = "MSIE"; 
    } 
    elseif(preg_match('/Firefox/i',$u_agent)) 
    { 
        $bname = 'Mozilla Firefox'; 
        $ub = "Firefox"; 
    } 
    elseif(preg_match('/Chrome/i',$u_agent)) 
    { 
        $bname = 'Google Chrome'; 
        $ub = "Chrome"; 
    } 
    elseif(preg_match('/Safari/i',$u_agent)) 
    { 
        $bname = 'Apple Safari'; 
        $ub = "Safari"; 
    } 
    elseif(preg_match('/Opera/i',$u_agent)) 
    { 
        $bname = 'Opera'; 
        $ub = "Opera"; 
    } 
    elseif(preg_match('/Netscape/i',$u_agent)) 
    { 
        $bname = 'Netscape'; 
        $ub = "Netscape"; 
    } 
    
    // finally get the correct version number
    $known = array('Version', $ub, 'other');
    $pattern = '#(?<browser>' . join('|', $known) .
    ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
    if (!preg_match_all($pattern, $u_agent, $matches)) {
        // we have no matching number just continue
    }
    
    // see how many we have
    $i = count($matches['browser']);
    if ($i != 1) {
        //we will have two since we are not using 'other' argument yet
        //see if version is before or after the name
        if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
            $version= $matches['version'][0];
        }
        else {
            $version= $matches['version'][1];
        }
    }
    else {
        $version= $matches['version'][0];
    }
    
    // check if we have a number
    if ($version==null || $version=="") {$version="?";}
    
    return array(
        'user_agent' => $u_agent,
        'name'      => $bname,
        'version'   => $version,
        'platform'  => $platform,
        'pattern'    => $pattern
    );
}