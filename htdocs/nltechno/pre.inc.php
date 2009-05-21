<?php
/* Copyright (C) 2008-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 */

/**
 *		\file 		htdocs/nltechno/pre.inc.php
 *		\ingroup    nltechno
 *		\brief      File to manage left menu for NLTechno module
 *		\version    $Id: pre.inc.php,v 1.2 2009/05/21 17:51:18 eldy Exp $
 */

define('NOCSRFCHECK',1);

$res=@include("../main.inc.php");
if (! $res) @include("../../../dolibarr/htdocs/main.inc.php");	// Used on dev env only

$user->getrights('nltechno');

function llxHeader($head = "", $title="", $help_url='')
{
	global $conf,$langs;
	$langs->load("agenda");

	top_menu($head, $title);

	$menu = new Menu();

	$menu->add(DOL_URL_ROOT."/nltechno/index.php?mainmenu=nltechno&idmenu=".$_SESSION["idmenu"], $langs->trans("Admin NLTechno"));

/*	$MAXAGENDA=5;
    $i=1;
	while ($i <= $MAXAGENDA)
	{
		$paramkey='GOOGLE_AGENDA_NAME'.$i;
		$paramcolor='GOOGLE_AGENDA_COLOR'.$i;
		//print $paramkey;
		if (! empty($conf->global->$paramkey))
		{
			$addcolor=false;
			if (isset($_GET["nocal"]))
			{
				if ($_GET["nocal"] == $i) $addcolor=true;
			}
			else $addcolor=true;

			$link=DOL_URL_ROOT."/google/index.php?mainmenu=google&idmenu=".$_SESSION["idmenu"]."&nocal=".$i;

			$text='';
			$text.='<table class="nobordernopadding"><tr valign="middle" class="nobordernopadding"><td style="padding-left: 4px; padding-right: 4px" nowrap="nowrap">';
			$box ='<!-- Box color '.$selected.' -->';
			$box.='<table style="border-collapse: collapse; margin:0px; padding: 0px; border: 1px solid #888888;';
			if ($addcolor) $box.=' background: #'.$conf->global->$paramcolor.';';
			$box.='" width="12" height="10">';
			$box.='<tr class="nocellnopadd"><td></td></tr>';
			$box.='</table>';
			$text.=$box;

			$text.='</td><td>';
			$text.='<a class="vsmenu" href="'.$link.'">'.$conf->global->$paramkey.'</a>';
			$text.='</td></tr>';
			$text.='</table>';

			$menu->add_submenu($link, $text);
		}
		$i++;
	}
*/

	left_menu($menu->liste, $help_url);
}
?>
