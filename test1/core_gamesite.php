<?php

/**
 * SGS Core Game Site
 * File: core_gamesite.php
 *
 * Copyright (c) 2007 - 2010 Big Fish Games, Inc.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @author William Moffett <william.moffett@bigfishgames.com>
 * @version 0.9
 * @package PNP Tools
 * @subpackage SGS
 *
 * @copyright Copyright (c) 2007 - 2010 Big Fish Games, Inc.
 * @license http://creativecommons.org/licenses/GPL/2.0/ Creative Commons GNU GPL
 */
if(isset($GLOBALS))
{
	unset($GLOBALS);
}

if(!@include_once(realpath(dirname(__FILE__).'/config.php')))
{
	header('location: install.php');
	exit;
}

$path ='';

while(!file_exists($path.'config.php'))
{
	$path .='../';
}

/**
 * 
 * Replacement for depeciated eregi
 * needle and haystack are examined in a case-insensitive manner
 * Do not use for regular expression pattern matches
 * 
 * @param mixed $needle    If needle is not a string, it is converted 
 *                         to an integer and applied as the ordinal 
 *                         value of a character.
 *                          
 * @param string $haystack The string to search in.
 * 
 * @return bool
 */
function sgs_eregi($needle, $haystack)
{    
    return is_string(stristr($haystack, $needle));
    
}

/**
 * Check to see if we have a myBFGuser set
 * If we have no user setting and are not on the install.php
 * let's redirect the user
 */

$onInstallPage = sgs_eregi('install.php', $_SERVER['PHP_SELF']);


if (
    (!isset($config['myBFGuser']) || !isset($config['version']) || $config['version'] < 0.9) 
    && !$onInstallPage 
    ){
        
	header('location: '.$path.'install.php');
}



/*### gamesite details ###*/
define('MYBFGUSER',$config['myBFGuser']);
define('MYBFGIDENTIFIER', $config['myBFGidentifier']);
define('SGSVERSION', $config['version']);

/*### Defined Paths  ###*/
define('g_BASE', $path);
define('g_ADMIN', g_BASE.$config['admin_dir'].'/');
define('g_CACHE', g_BASE.$config['cache_dir'].'/');
define('g_CUSTOM', g_BASE.$config['custom_dir'].'/');
define('g_DATA', g_BASE.$config['data_dir'].'/');
define('g_DOCS', g_BASE.$config['docs_dir'].'/');
define('g_INCLUDE', g_BASE.$config['include_dir'].'/');
define('g_LANGUAGE', g_BASE.$config['language_dir'].'/');
define('g_MODULES', g_BASE.$config['module_dir'].'/');
define('g_TEMPLATES', g_BASE.$config['templates_dir'].'/');
define('g_TEST', g_BASE.$config['test_dir'].'/');
define('g_GENERATOR', 'Satellite Gamesite System');

/*### current page information ###*/
define('g_SELF', $_SERVER['PHP_SELF']);
define('g_PAGE', array_pop(explode('/',g_SELF)));
/*### path information ###*/
$abs = str_replace('\\', '/', dirname($_SERVER['PHP_SELF']));
$abs = substr($abs, -1) != '/' ? $abs.'/' : $abs;
define('g_ABSOLUTE', ($_SERVER['SERVER_PORT'] =='80' ? 'http://' : 'https://').$_SERVER['HTTP_HOST'].$abs);
define('g_RELATIVE', str_ireplace(g_PAGE,'',g_SELF));
if(g_PAGE=='error.php'){
	$dirs = count(explode('/', $_SERVER['REQUEST_URI']));
	$rel ='';
	for($d=2; $d<=$dirs;$d++){
		$rel .= '../';
	}
	define('RELATIVE_PATH', $rel);
}else{
	define('RELATIVE_PATH', g_RELATIVE);
}

/*### query string ###*/
define("g_QUERY", htmlentities($_SERVER['QUERY_STRING']));

/*###PHP INI SETTINGS###*/
if (function_exists('ini_set')){
	ini_set('magic_quotes_runtime',     0);
	ini_set('magic_quotes_sybase',      0);
	ini_set('arg_separator.output',     '&amp;');
	ini_set('session.use_only_cookies', 1);
	ini_set('session.use_trans_sid',    0);
	ini_set('session.url_rewriter.tags', 'a=href,area=href,frame=src,input=src');
}

/*###DEVELOPER SETTINGS ###*/
if(isset($config['error_reporting']) && $config['error_reporting'] !=''){
	ini_set('error_reporting', $config['error_reporting']);
}

define('DEBUG', $config['debug'] ? $config['debug'] : 'FALSE');
define('DEBUG_MODE',isset($config['debug_mode']) ? $config['debug_mode'] : 'low');
define('DEBUG_VIEW',isset($config['debug_view']) ? $config['debug_view'] : 'console');
define('DISPLAY_OBJECT',isset($config['display_object']) ? $config['display_object'] : 'FALSE');

/*##$ START SESSION ###*/
session_start();

/*### INIT GAME SITE ###*/
define('SGSINIT', TRUE);

/*### LOAD OUR SITE OBJECT AND INSTANTIATE OUR CLASSES ###*/
require_once(g_INCLUDE.'site_loader.class.php');

/**
 * @global site_loader $sl
 * @name $sl
 */
$sl = new site_loader(DEBUG,DEBUG_VIEW);


$sl->class['site_timer']->markTime('realtime');

/*### LOAD USER SITE CONFIG ###*/
/**
 * @global array $siteconfig
 * @name $siteconfig
 */

$siteconfig = $sl->class['site_config']->loadConfig();

/*### PROCESS EVENTS ###*/
$sl->process_events();

if(defined('SGSADMIN') && SGSADMIN == true){
    define('PAGE_CACHE', false);
    define('MODULE_CACHE', false); 
}

/*### SET CONFIG CONSTANTS ###*/


if(isset($sl->class['site_config']->siteconfig) && is_array($sl->class['site_config']->siteconfig)){

	$sl->class['site_config']->siteconfig = array_change_key_case($sl->class['site_config']->siteconfig, CASE_UPPER);

	foreach($sl->class['site_config']->siteconfig as $key=>$value){
		if(!defined($key)){
			define($key,stripslashes($value));
		}
	}

	$sl->class['site_config']->siteconfig = array_change_key_case($sl->class['site_config']->siteconfig, CASE_LOWER);

}else{
	if(!$onInstallPage){
		header('location: install.php');
	}
}

/**
 * set the default gametype to the config setting if it has not been defined.
 */
if(!defined('GAMETYPE')){ define('GAMETYPE', $config['gametype']);	}
/**
 * set the local to the config setting if it has not been defined.
 */
if(!defined('LOCAL')){ define('LOCAL', $config['local']);	}

/*### Data Servers ### */
define('ADSERVER',$config['ADserver']);
define('XMLSERVER',$config['XMLserver']);
define('ASSETSERVER',$config['Assetserver']);
/*### BIGFISH GAMES DOMAIN HOME PAGES  ###*/
define('BFG_HOME', $config['bfg_home'][LOCAL]);
define('AFFILIATES_HOME', $config['affiliates_home']);
define('IFRAMESERVER',$config['Iframeserver'][LOCAL]);

/*### SET CACHE PATH AND TIME ###*/
$sl->class['site_cache']->set_path(g_CACHE);
if(!defined('CACHE_LIFE_TIME')){ define('CACHE_LIFE_TIME','12'); }
$sl->class['site_cache']->set_lifetime(CACHE_LIFE_TIME);

/*### THEME CHECK ###*/
if(!defined('THEME')){
	define('THEME', 'default');
	$TEMPLATE_ERROR = TRUE;
}

if(!file_exists(g_TEMPLATES.THEME.'/page.html')){
	if(!defined('g_DEFAULT')){
		define('g_DEFAULT', g_TEMPLATES.'default/');
	}
	if(!defined('g_THEME_DIR')){
		define('g_THEME_DIR', g_TEMPLATES.'default/');
	}
	$TEMPLATE_ERROR = TRUE;
}else{
	if(!defined('g_DEFAULT')){
		define('g_DEFAULT', g_TEMPLATES.'default/');
	}

	if(!defined('g_THEME_DIR')){
		define('g_THEME_DIR', g_TEMPLATES.THEME.'/');
	}
}

/*### DISPLAY THEME ERROR IF ANY ###*/
if(isset($TEMPLATE_ERROR) && is_object($sl->class['site_debug'])){
	$sl->class['site_debug']->_debug("core_gamesite", "Unable to locate ".THEME."/page.html, Loaded default theme. ");
}

/*### LOAD TEMPLATE FUNCTIONS FILE IF IT EXISTS ###*/
if(sgs_eregi('pmodules',g_PAGE)){
	@require_once(g_THEME_DIR.'functions.php');
}else if(is_file(g_THEME_DIR.'functions.php') && !sgs_eregi($config['admin_dir'],g_RELATIVE) && !sgs_eregi('admin',g_PAGE)){
	@require_once(g_THEME_DIR.'functions.php');
}

if(is_file(g_THEME_DIR.'admin_functions.php') && (sgs_eregi($config['admin_dir'],g_RELATIVE) || sgs_eregi('admin',g_PAGE))){
	define('ADMINDISPLAY', TRUE);
	@require_once(g_THEME_DIR.'admin_functions.php');
}else if(sgs_eregi($config['admin_dir'],g_RELATIVE) || sgs_eregi('admin',g_PAGE)){
	define('ADMINDISPLAY', TRUE);
	@require_once(g_ADMIN.'admin_functions.php');
}

if(!defined('SITENAME')){ define('SITENAME', 'Satellite Gamesite System'); }
if(!defined('SITETAG')){ define('SITETAG', 'v '.$config['version']); }
if(!defined('COPYRIGHT')){ define('COPYRIGHT', ''); }
if(!defined('DISCLAIMER')){	define('DISCLAIMER', ''); }
if(!defined('DESCRIPTION')){ define('DESCRIPTION', ''); }
if(!defined('KEYWORDS')){ define('KEYWORDS', ''); }

/*### PATHS FOR TEMPLATES AND MODULES ###*/
define('BASE_DIR',g_PAGE =='error.php' ? g_ABSOLUTE : g_BASE);
define('ADMIN_DIR',g_PAGE =='error.php' ? g_ABSOLUTE.$config['admin_dir'].'/' : g_ADMIN);
define('CUSTOM_DIR',g_PAGE =='error.php' ? g_ABSOLUTE.$config['custom_dir'].'/' : g_CUSTOM);
define('DOCS_DIR',g_PAGE =='error.php' ? g_ABSOLUTE.$config['docs_dir'].'/' : g_DOCS);
define('TEST_DIR',g_PAGE =='error.php' ? g_ABSOLUTE.$config['test_dir'].'/' : g_TEST);
define('THEME_DIR',g_PAGE =='error.php' ?  g_ABSOLUTE.$config['templates_dir'].'/'.THEME.'/' : g_THEME_DIR);
define('COREJS',g_PAGE =='error.php' ?  g_ABSOLUTE.'corejs.php' : g_BASE.'corejs.php');
define('SITEMAP_URI',g_PAGE =='error.php' ? g_ABSOLUTE.'sitemap.php' : g_BASE.'sitemap.php');
define('HOME_URI',g_PAGE =='error.php' ? g_ABSOLUTE.'index.php' : g_BASE.'index.php');
/*### SET CORE PARSE TAGS ###*/
// SET USER TAGS
$sl->class['site_parse']->settag('USERNAME', USERNAME);
$sl->class['site_parse']->settag('MYBFGUSER', MYBFGUSER);
$sl->class['site_parse']->settag('MYBFGIDENTIFIER', MYBFGIDENTIFIER);
// SERVER TAGS
$sl->class['site_parse']->settag('ADSERVER', ADSERVER);
$sl->class['site_parse']->settag('ASSETSERVER', ASSETSERVER);
$sl->class['site_parse']->settag('IFRAMESERVER', IFRAMESERVER);
// BIGFISH GAMES DOMAIN HOME PAGE TAGS
$sl->class['site_parse']->settag('BFG_HOME', BFG_HOME);
$sl->class['site_parse']->settag('AFFILIATES_HOME', AFFILIATES_HOME);

/*### PATH TAGS ###*/
$sl->class['site_parse']->settag('ADMIN_DIR', ADMIN_DIR);
$sl->class['site_parse']->settag('CUSTOM_DIR', CUSTOM_DIR);
$sl->class['site_parse']->settag('DOCS_DIR', DOCS_DIR);
$sl->class['site_parse']->settag('THEME_DIR', THEME_DIR);
$sl->class['site_parse']->settag('RELATIVE_PATH', RELATIVE_PATH);
$sl->class['site_parse']->settag('BASE_DIR', BASE_DIR);
/*### PAGE AND QUERY TAGS ###*/
$sl->class['site_parse']->settag('SELF', g_SELF);
$sl->class['site_parse']->settag('PAGE', g_PAGE);
$sl->class['site_parse']->settag('QUERY', g_QUERY);
/*### FILE LOCATION TAGS ###*/
$sl->class['site_parse']->settag('COREJS', COREJS);
$sl->class['site_parse']->settag('SITEMAP_URI', SITEMAP_URI);
$sl->class['site_parse']->settag('HOME_URI', HOME_URI);
/*### SITE INFO TAGS ###*/
$sl->class['site_parse']->settag('LOCAL', LOCAL);
$sl->class['site_parse']->settag('SITENAME', SITENAME);
$sl->class['site_parse']->settag('SITETAG', SITETAG);
$sl->class['site_parse']->settag('PAGETITLE', SITENAME);
$sl->class['site_parse']->settag('DESCRIPTION', DESCRIPTION);
$sl->class['site_parse']->settag('KEYWORDS', KEYWORDS);
$sl->class['site_parse']->settag('COPYRIGHT', COPYRIGHT);
$sl->class['site_parse']->settag('GENERATOR', g_GENERATOR);
/**
 * Disclaimer Powered by replacement Hack
 */
$sl->class['site_parse']->settag('DISCLAIMER',  preg_replace(array('/\| Powered by SGS v0.6/','/Powered by SGS v0.6/'),array('',''), DISCLAIMER));
$sl->class['site_parse']->settag('VERSION', $config['version']);
$sl->class['site_parse']->settag('BUILDDATE', $config['builddate']);
$sl->class['site_parse']->settag('POWEREDBY', 'Powered by SGS v'.$config['version']);

if((defined('PAGE_CACHE') && PAGE_CACHE == TRUE) || (defined('MODULE_CACHE') && MODULE_CACHE == TRUE)){
	$sl->class['site_cache']->cache_manager();
}

// START PAGE OBJECT
$sl->class['site_parse']->page_start();

if(SGSADMIN && !sgs_eregi('install.php',g_SELF) && is_file(g_BASE.'install.php')){
	echo '<p'.((defined('ERRORCLASS') && ERRORCLASS !='') ? ' class="'.ERRORCLASS.'"' : ' class="error"').' style="margin:10px; padding:10px 30px;">Please delete the install.php from your server and chmod your config file to 644.</p>';
}

?>