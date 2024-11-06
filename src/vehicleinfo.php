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

//parse a vehicle ini file and check for valid contents
//return vehicleinfo as array
function nml_vehicleinfo_from_ini($ini, $vehid) {
	$vehicleinfo = array();
	
	//get ini contents
	$ini = parse_ini_string($ini, TRUE);
	if ($ini === FALSE) {
		echo 'Warning: Not a valid INI file for "'.$vehid.'"' . PHP_EOL;
		return FALSE;
	}
	
	//string name
	$vehicleinfo['vehicle_id'] = $vehid;
	$vehicleinfo['str_name'] = 'STR_NAME_'.strtoupper($vehid);
	
	//check if string is in english language file, if not add it
	$langfile = file_get_contents('../lang/english.lng');
	//check if name defined, otherwise use string name
	if (!isset($ini['vehicle']['name']) || empty($ini['vehicle']['name'])) $ini['vehicle']['name'] = $vehicleinfo['str_name'];
	if (!preg_match('/\n'.$vehicleinfo['str_name'].'\h*:.+/',$langfile)) {
		//add to langfile
		if (substr($langfile, -1) != "\n") $langfile .= "\n";
		$langfile .= $vehicleinfo['str_name'];
		$langfile .= str_repeat(' ', max(0, 24 - strlen($vehicleinfo['str_name'])));
		$langfile .= ':'.$ini['vehicle']['name']."\n";
		file_put_contents('../lang/english.lng', $langfile);
	}
	
	/*
	PROPERTIES
	*/
	
	//capacity calculations
	//check if capacity set
	if (!isset($ini['property']['capacity']) || empty($ini['property']['capacity'])) {
		echo 'Warning: No [property] capacity set for "'.$vehid.'"' . PHP_EOL;
		return FALSE;
	}
	$vehicleinfo['property']['capacity'] = explode(',', $ini['property']['capacity']);
	//check if each capacity part is a valid number
	foreach ($vehicleinfo['property']['capacity'] as $i => $capacity) {
		if (!is_numeric($capacity) || ($capacity < 0)) {
			echo 'Warning: Invalid [property] capacity set in part '.(++$i).' for "'.$vehid.'"' . PHP_EOL;
			return FALSE;
		}
	}
	$vehicleinfo['property']['vehicleparts'] = count($vehicleinfo['property']['capacity']);
	$vehicleinfo['property']['cargo_capacity'] = array_sum($vehicleinfo['property']['capacity']);
	$vehicleinfo['property']['purchase_cargo_capacity'] = round($vehicleinfo['property']['cargo_capacity']/$vehicleinfo['property']['vehicleparts']);
	
	//introduction year
	if (!isset($ini['property']['introduction_year']) || empty($ini['property']['introduction_year']) || ($ini['property']['introduction_year'] <= 0) || ($ini['property']['introduction_year'] > 5000000)) {
		echo 'Warning: Invalid [property] introduction_year set for "'.$vehid.'" (limit 0-5000000)' . PHP_EOL;
		return FALSE;
	}
	$vehicleinfo['property']['introduction_date'] = $ini['property']['introduction_year'].',01,01';
	
	//vehicle life
	if (!isset($ini['property']['vehicle_life']) || !is_numeric($ini['property']['vehicle_life']) || ($ini['property']['vehicle_life'] <= 0) || ($ini['property']['vehicle_life'] > 255)) {
		$vehicleinfo['property']['vehicle_life'] = 28;
	}
	else {
		$vehicleinfo['property']['vehicle_life'] = $ini['property']['vehicle_life'];
	}
	
	//model life and retire early
	//retire early
	$vehicleinfo['property']['retire_early'] = $vehicleinfo['property']['vehicle_life'] + 8;
	//reduced model life (10 years)
	$vehicleinfo['property']['model_life_reduced'] = 10 + $vehicleinfo['property']['vehicle_life'] + 8;
	//reduced retire early, normally equal to regular retire eary, except for vehicles that never expire (see below)
	$vehicleinfo['property']['retire_early_reduced'] = $vehicleinfo['property']['retire_early'];
	//model life
	if (!isset($ini['property']['model_life']) || !is_numeric($ini['property']['model_life']) || ($ini['property']['model_life'] <= 0) || ($ini['property']['model_life'] > 254)) {
		$vehicleinfo['property']['model_life'] = 'VEHICLE_NEVER_EXPIRES';
		$vehicleinfo['property']['retire_early'] = 0;
		//reduced model life (10 years), only never expire for vehicles 2010 onwards
		if ($ini['property']['introduction_year'] >= 2010) {
			$vehicleinfo['property']['model_life_reduced'] = 'VEHICLE_NEVER_EXPIRES';
			$vehicleinfo['property']['retire_early_reduced'] = 0;
		}
	}
	else {
		$vehicleinfo['property']['model_life'] = $ini['property']['model_life'] + $vehicleinfo['property']['vehicle_life'] + 8;
	}
	
	//loading speed
	if (!isset($ini['property']['loading_speed']) || !is_numeric($ini['property']['loading_speed']) || ($ini['property']['loading_speed'] <= 0) || ($ini['property']['loading_speed'] > 51)) {
		$vehicleinfo['property']['loading_speed'] = 10;
	}
	else {
		$vehicleinfo['property']['loading_speed'] = $ini['property']['loading_speed'] * 5;
	}
	
	//speed
	if (!isset($ini['property']['speed']) || !is_numeric($ini['property']['speed']) || ($ini['property']['speed'] <= 0) || ($ini['property']['speed'] > 514)) {
		echo 'Warning: Invalid [property] speed set for "'.$vehid.'" (limit 0-514 km/h)' . PHP_EOL;
		return FALSE;
	}
	$vehicleinfo['property']['speed'] = $ini['property']['speed'];
	
	//power
	if (!isset($ini['property']['power']) || !is_numeric($ini['property']['power']) || ($ini['property']['power'] <= 0) || ($ini['property']['power'] > 2550)) {
		echo 'Warning: Invalid [property] power set for "'.$vehid.'" (limit 0-2550 hp)' . PHP_EOL;
		return FALSE;
	}
	$vehicleinfo['property']['power'] = $ini['property']['power'];
	
	//weight
	if (!isset($ini['property']['weight']) || !is_numeric($ini['property']['weight']) || ($ini['property']['weight'] <= 0) || ($ini['property']['weight'] > 63.75)) {
		echo 'Warning: Invalid [property] weight set for "'.$vehid.'" (limit 0-63.75 ton)' . PHP_EOL;
		return FALSE;
	}
	$vehicleinfo['property']['weight'] = $ini['property']['weight'];
	
	//TE
	$vehicleinfo['property']['tractive_effort_coefficient'] = 0.5;
	
	//AD
	$vehicleinfo['property']['air_drag_coefficient'] = 0;
	
	//RD
	$vehicleinfo['property']['reliability_decay'] = 20;
	
	//calculate cost factor
	$vehicleinfo['property']['cost_factor'] = max(1, round(0.5 * ($vehicleinfo['property']['vehicle_life'] * 0.2 + $vehicleinfo['property']['loading_speed'] * 1 + pow($vehicleinfo['property']['power'] , 1.1) * 0.1 + $vehicleinfo['property']['weight'] * 1)));
	if ($vehicleinfo['property']['cost_factor'] > 255 ) {
		echo 'Notice: [property] cost_factor maxed out for "'.$vehid.'" ('.$vehicleinfo['property']['cost_factor'].', set to 255)' . PHP_EOL;
		$vehicleinfo['property']['cost_factor'] = 255;
	}
	
	//calculate running cost factor
	$vehicleinfo['property']['running_cost_factor'] = max(1, round($vehicleinfo['property']['cargo_capacity'] * 0.3 + pow($vehicleinfo['property']['weight'], 1.1) * 1.2 + $vehicleinfo['property']['speed'] * 0.3));
	if ($vehicleinfo['property']['running_cost_factor'] > 255 ) {
		echo 'Notice: [property] running_cost_factor maxed out for "'.$vehid.'" ('.$vehicleinfo['property']['running_cost_factor'].', set to 255)' . PHP_EOL;
		$vehicleinfo['property']['running_cost_factor'] = 255;
	}
	
	//cargo property limited to 255
	$vehicleinfo['property']['cargo_capacity'] = min(255, array_sum($vehicleinfo['property']['capacity']));
	
	/*
	GRAPHICS
	*/
	
	//default livery
	//additional liveries not yet supported
	//check if sprite file defined and exists
	if (!isset($ini['default']['sprite_file']) || empty($ini['default']['sprite_file'])) {
		echo 'No [default] sprite_file set for "'.$vehid.'"' . PHP_EOL;
		return FALSE;
	}
	if (!is_file('../gfx/'.$ini['default']['sprite_file'])) {
		echo 'No such graphics file "'.$ini['default']['sprite_file'].'" for "'.$vehid.'"' . PHP_EOL;
		return FALSE;
	}
	//determine vehicle parts
	$graphicssections = array('gui', 'purchase');
	for ($i = 1; $i <= $vehicleinfo['property']['vehicleparts']; $i++) {
		$graphicssections[] = 'part'.$i;
	}
	
	foreach ($graphicssections as $section) {
		//x_offset
		if (!isset($ini['default'][$section]['x_offset']) || !is_numeric($ini['default'][$section]['x_offset']) || ($ini['default'][$section]['x_offset'] <= 0)) {
			echo 'Invalid [default] x_offset set for "'.$vehid.'" part "'.$section.'"' . PHP_EOL;
			return FALSE;
		}
		$vehicleinfo['graphic']['default'][$section]['x_offset'] = $ini['default'][$section]['x_offset'];
		//y_offset
		if (!isset($ini['default'][$section]['y_offset']) || !is_numeric($ini['default'][$section]['y_offset']) || ($ini['default'][$section]['y_offset'] <= 0)) {
			echo 'Invalid [default] y_offset set for "'.$vehid.'" part "'.$section.'"' . PHP_EOL;
			return FALSE;
		}
		$vehicleinfo['graphic']['default'][$section]['y_offset'] = $ini['default'][$section]['y_offset'];
		//length
		if (($section != 'gui') && ($section != 'purchase')) {
			if (!isset($ini['default'][$section]['length']) || !is_numeric($ini['default'][$section]['length']) || ($ini['default'][$section]['length'] <= 0) || ($ini['default'][$section]['length'] > 8)) {
				echo 'Invalid [default] length set for "'.$vehid.'" part "'.$section.'"' . PHP_EOL;
				return FALSE;
			}
			$vehicleinfo['graphic']['default'][$section]['length'] = $ini['default'][$section]['length'];
		}
		$vehicleinfo['graphic']['default'][$section]['sprite_file'] = $ini['default']['sprite_file'];
	}
	
	//return vehicle info
	return $vehicleinfo;
}
?>