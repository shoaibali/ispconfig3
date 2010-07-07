<?php

class dashlet_modules {
	
	function show() {
		global $app, $conf;
		
		//* Loading Template
		$app->uses('tpl');
		
		$tpl = new tpl;
		$tpl->newTemplate("dashlets/templates/modules.htm");
		
		$lng_file = 'lib/lang/'.$_SESSION['s']['language'].'_dashlet_modules.lng';
		include($lng_file);
		$tpl->setVar($wb);
		
		/*
		 * Show all modules, the user is allowed to use
		*/
		$modules = explode(',', $_SESSION['s']['user']['modules']);
		$mod = array();
		if(is_array($modules)) {
			foreach($modules as $mt) {
				if(is_file('../' . $mt . '/lib/module.conf.php')) {
					if(!preg_match("/^[a-z]{2,20}$/i", $mt)) die('module name contains unallowed chars.');
					include_once('../' . $mt.'/lib/module.conf.php');
					/* We don't want to show the dashboard */
					if ($mt != 'dashboard') {
						$mod[] = array(	'modules_title' 	=> $app->lng($module['title']),
								'modules_startpage'	=> $module['startpage'],
								'modules_name'  	=> $module['name']);
					}
				}
			}

			$tpl->setloop('modules', $mod);
		}
		
		return $tpl->grab();
		
	}
}








?>