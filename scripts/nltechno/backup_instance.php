#!/usr/bin/php
<?php
/* Copyright (C) 2012 Laurent Destailleur	<eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path=dirname(__FILE__).'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit;
}

// Global variables
$version='$Revision: 1.4 $';
$error=0;

$dirroot=isset($argv[2])?$argv[2]:'';
$instance=isset($argv[1])?$argv[1]:'';
$mode=isset($argv[3])?$argv[3]:'';

// Include Dolibarr environment
$res=0;
if (! $res && file_exists($path."../../master.inc.php")) $res=@include($path."../../master.inc.php");
if (! $res && file_exists($path."../../htdocs/master.inc.php")) $res=@include($path."../../htdocs/master.inc.php");
if (! $res && file_exists("../master.inc.php")) $res=@include("../master.inc.php");
if (! $res && file_exists("../../master.inc.php")) $res=@include("../../master.inc.php");
if (! $res && file_exists("../../../master.inc.php")) $res=@include("../../../master.inc.php");
if (! $res && file_exists($dirroot."/htdocs/master.inc.php")) $res=@include($dirroot."/htdocs/master.inc.php");
if (! $res) die ("Failed to include master.inc.php file\n");
dol_include_once("/nltechno/core/lib/dolicloud.lib.php");
dol_include_once('/nltechno/class/dolicloudcustomer.class.php');

$object = new DoliCloudCustomer($db);



/*
 *	Main
 */

if (empty($dirroot) || empty($instance) || empty($mode))
{
	print "Usage:   $script_file instance    backup_dir  (test|confirm)\n";
	print "Example: $script_file myinstance  /home/dolicloud_instances/home  test\n";
	print "Return code: 0 if success, <>0 if error\n";
	exit(-1);
}


$result=$object->fetch('',$instance);
if ($result < 0)
{
	print "Error: instance ".$instance." not found.\n";
	exit(-2);
}
if (empty($object->instance) && empty($object->username_web) && empty($object->password_web) && empty($object->database_db))
{
	print "Error: properties for instance ".$instance." was not registered into database.\n";
	exit(-3);
}
if (! is_dir($dirroot))
{
	print "Error: Target directory ".$dirroot." to store backup does not exist.\n";
	exit(-4);
}

$dirdb=preg_replace('/_dolibarr/','',$object->database_db);
$login=$object->username_web;
$password=$object->password_web;
$sourcedir='/home/'.$login.'/'.$dirdb;
$server=$object->instance.'.on.dolicloud.com';

if (empty($login) || empty($dirdb))
{
	print "Error: properties for instance ".$instance." are not registered completely (missing at least login or database name).\n";
	exit(-5);
}

print 'Backup instance '.$instance.' to '.$dirroot.'/'.$login."\n";
print 'SFTP password '.$object->password_web."\n";

//$listofdir=array($dirroot.'/'.$login, $dirroot.'/'.$login.'/documents', $dirroot.'/'.$login.'/system', $dirroot.'/'.$login.'/htdocs', $dirroot.'/'.$login.'/scripts');
$listofdir=array($dirroot.'/'.$login);
foreach($listofdir as $dirtocreate)
{
	if (! is_dir($dirtocreate))
	{
		$res=@mkdir($dirtocreate);
		if (! $res)
		{
			print 'Failed to create dir '.$dirtocreate."\n";
			exit(-6);
		}
	}
}


// Define SFTP strings
/*
$sftpconnectstring=$object->username_web.'@'.$object->hostname_web.':/home/'.$object->username_web.'/'.preg_replace('/_dolibarr$/','',$object->database_db);
print 'SFTP connect string : '.$sftpconnectstring."\n";
print 'SFTP password '.$object->password_web."\n";
*/


$command="rsync";
$param=array();
if ($mode != 'confirm') $param[]="-n";
//$param[]="-a";
$param[]="-rlt";
$param[]="-v";
$param[]="--exclude .buildpath";
$param[]="--exclude .git";
$param[]="--exclude .gitignore";
$param[]="--exclude .settings";
$param[]="--exclude .project";
$param[]="--exclude build/";
//$param[]="--exclude doc/";	// To keep files into htdocs/core/module/xxx/doc dir
$param[]="--exclude dev/";
$param[]="--exclude test/";
$param[]="--stats";
$param[]="-e ssh";

//var_dump($param);
//print "- Backup documents dir ".$dirroot."/".$instance."\n";
$param[]=$login.'@'.$server.":".$sourcedir;
$param[]=$dirroot.'/'.$login;
$fullcommand=$command." ".join(" ",$param);
$output=array();
$return_var=0;
print $fullcommand."\n";
//exec($fullcommand, &$output, &$return_var);

// Output result
foreach($output as $outputline)
{
	print $outputline."\n";
}

/*
print "- Backup programs dir ".$dirroot."/".$instance."/htdocs\n";
$paramhtdoc=$param;
$paramhtdoc[]=$login.'@'.$server.":".$sourcedir.'/htdocs';
$paramhtdoc[]=$dirroot.'/'.$login.'/htdocs';
$fullcommand=$command." ".join(" ",$paramhtdoc);
$outputhtdoc=array();
$return_varhtdoc=0;
print $fullcommand."\n";
//exec($fullcommand, &$outputhtdoc, &$return_varhtdoc);

// Output result
foreach($outputhtdoc as $outputline)
{
	print $outputline."\n";
}


print "- Backup programs dir ".$dirroot."/".$instance."/scripts\n";
$paramscripts=$param;
$paramscripts[]=$login.'@'.$server.":".$sourcedir.'/scripts';
$paramscripts[]=$dirroot.'/'.$login.'/scripts';
$fullcommand=$command." ".join(" ",$paramscripts);
$outputscripts=array();
$return_varscripts=0;
print $fullcommand."\n";
//exec($fullcommand, &$outputscripts, &$return_varscripts);

// Output result
foreach($outputscripts as $outputline)
{
	print $outputline."\n";
}


print "- Make mysqldump ".$dirroot."/".$instance."/system\n";
$command="mysqldump";
$parammysql=array();
$parammysql[]='';
$parammysql[]='';
$parammysql[]='';
$fullcommand=$command." ".join(" ",$paramscripts);
$outputscripts=array();
$return_varmysql=0;
print $fullcommand."\n";
//exec($fullcommand, &$outputscripts, &$return_varscripts);
*/

// Update database
//if (empty($return_vardoc) && empty($return_varhtdocs) && empty($return_varscripts) && empty($return_varmysql))
if (empty($return_var))
{
	if ($mode == 'confirm')
	{
		$now=dol_now();
		print 'Update date of backup for instance '.$object->instance.' to '.$now."\n";
		$object->date_lastrsync=$now;
		$object->update(0);
	}
}
else
{
	print "ERROR into backup process\n";
	exit(-6);
}

exit(0);
?>