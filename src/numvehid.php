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

//return numeric vehicle id for vehicle id string
//will first query vehid.csv and return number from there
//if no number found in vehid.csv, generate new one and store in vehid.csv

function nml_numvehid($vehid) {
	global $vehid_list;
	$csvfile = 'vehid.csv';
	//populate vehid list on first run
	if (empty($vehid_list)) {
		$vehid_list = array();
		$handle = fopen($csvfile, 'c+b');
		while ($line = fgetcsv($handle, 0, ';', '"', '\\')) {
			$vehid_list[$line[1]] = $line[0];
		}
		fclose($handle);
	}
	//check if in list
	if (!array_key_exists($vehid, $vehid_list)) {
		//generate new key
		if (empty($vehid_list))
			$nextid = 256;
		else {
			$nextid = max($vehid_list) + 1;
		}
		//add to list
		$vehid_list[$vehid] = $nextid;
		//store in csv
		$handle = fopen($csvfile, 'ab');
		fputcsv($handle, array($nextid, $vehid), ';', '"');
		fclose($handle);
	}
	return $vehid_list[$vehid];
}
?>