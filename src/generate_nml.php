<?php
/*
 * 2CC Tram Set
 * Copyright (C) 2014-2015 Jasper Vries
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
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

//change dir to source directory
chdir('src');
//init vars
$nml = '';
$vehid_list = array(); //used for storing the numeric vehicle ids
$vehid_sort = array(); //used for storing the vehicle sort order

//include functions
include('roadvehicle.php');
include('numvehid.php');
include('vehicleinfo.php');

//get NML header and templates
$header = file_get_contents('header.pnml');
//insert version number
if (!is_numeric($argv[1])) {
	$repo_version = 1;
	echo 'Warning: Invalid version number, using version "1"' . PHP_EOL;
}
else {
	$repo_version = $argv[1];
}
$nml .= str_replace('REPO_REVISION', $repo_version, $header);
$nml .= file_get_contents('templates.pnml');

/*
Generate NML for vehicles
*/
//set directory to parse INI files from
$dir = 'vehicles';
$vehicles = scandir($dir);
foreach ($vehicles as $vehicle) {
	//ignore everything that is not an INI file or starts with . or _
	if ((substr($vehicle, 0, 1) != '_') && (substr($vehicle, 0, 1) != '.') && (substr($vehicle, -4) == '.ini') && is_file($dir.'/'.$vehicle)) {
		$ini = file_get_contents($dir.'/'.$vehicle);
		$vehid = substr($vehicle, 0, -4);
		//check for valid vehid
		if (preg_match('/[^a-zA-Z0-9]/', $vehid)) {
			echo 'Warning: Invalid Vehicle ID "'.$vehid.'"' . PHP_EOL;
		}
		else {
			//get numeric vehicle id for this vehicle, or generate one
			$numvehid = nml_numvehid($vehid);
			//preprocess vehicle details
			$vehicleinfo = nml_vehicleinfo_from_ini($ini, $vehid);
			if ($vehicleinfo !== FALSE) {
				//process vehicle and write nml code
				$nml .= nml_roadvehicle($vehicleinfo, $vehid, $numvehid);
				//add for sorting
				$vehid_sort['item_roadvehicle_'.$vehid] = $vehicleinfo['property']['introduction_date'].$vehicleinfo['str_name'];
			}
			else {
				//invalid vehicle info
				echo 'Warning: Invalid Vehicle INI "'.$vehid.'"' . PHP_EOL;
			}
		}
	}
}

//sort the vehicle list
asort($vehid_sort);
$vehid_sort = array_keys($vehid_sort);
$nml .= PHP_EOL . '//sort the vehicle list' . PHP_EOL;
$nml .= 'sort(FEAT_ROADVEHS, ['.join(', ', $vehid_sort).']);' . PHP_EOL;

//store generated nml
file_put_contents('../trams-2cc.nml', $nml);
?>