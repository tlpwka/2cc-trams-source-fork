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

function nml_roadvehicle($vehicleinfo, $vehid, $numvehid) {
	//start nml components	
	$nml = "
////////////////////////
//Pre-purchase sprites//
////////////////////////

spriteset(spriteset_{$vehid}_purchase, \"gfx/{$vehicleinfo['graphic']['default']['purchase']['sprite_file']}\") {
	template_roadvehicle_purchase({$vehicleinfo['graphic']['default']['purchase']['x_offset']}, {$vehicleinfo['graphic']['default']['purchase']['y_offset']})
}

///////////////////
//Vehicle sprites//
///////////////////
";

	$livery = 'default';
	$switch = 'switch (FEAT_ROADVEHS, SELF, switch_' .$vehid. '_' .$livery. '_position, position_in_consist) {' . PHP_EOL;
	$num_parts = count($vehicleinfo['graphic'][$livery]) - 3;
	ksort($vehicleinfo['graphic'][$livery]);
	foreach($vehicleinfo['graphic'][$livery] as $section => $values) {
		if ($section != 'purchase') {
			if ($section == 'gui') {
				$template = 'template_roadvehicle_gui';
				$search = array('ID', 'LIV', 'SEC', 'FILE', 'TEMPLATE');
				$replace = array($vehid, $livery, $section, 'gfx/'.str_replace('/', ' sideviews/', $vehicleinfo['graphic'][$livery][$section]['sprite_file']), $template, $values['x_offset'], $values['y_offset']);
				$spriteset_template = '
spriteset(spriteset_ID_LIV_SEC, "FILE") {
	TEMPLATE()
}
';
			}
			else {
				$template = 'template_roadvehicle_'.$values['length'];
				$search = array('ID', 'LIV', 'SEC', 'FILE', 'TEMPLATE', 'X_OFF', 'Y_OFF');
				$replace = array($vehid, $livery, $section, 'gfx/'.$vehicleinfo['graphic'][$livery][$section]['sprite_file'], $template, $values['x_offset'], $values['y_offset']);
				$spriteset_template = '
spriteset(spriteset_ID_LIV_SEC, "FILE") {
	TEMPLATE(X_OFF, Y_OFF)
}
';
			}
			$nml .= str_replace($search, $replace, $spriteset_template);
		}
		if (($section != 'purchase') && ($section != 'gui')) {
			$switch .= '    ';
			if ($section != 'part'.($vehicleinfo['property']['vehicleparts'])) {
				$switch .= (substr($section, 4) - 1) . ': ';
			}
			$switch .= 'spriteset_' .$vehid. '_' .$livery. '_' .$section. ';' . PHP_EOL;
		}
	}
	$switch .= '}' . PHP_EOL;
	$nml .= $switch;
	
	$nml .= "
switch (FEAT_ROADVEHS, SELF, switch_{$vehid}_{$livery}_graphics_gui, position_in_consist) {
	0: spriteset_{$vehid}_{$livery}_gui; //first in consist
	spriteset_blank; //other parts blank
}
switch (FEAT_ROADVEHS, SELF, switch_{$vehid}_graphics, (extra_callback_info1 & 0xFF)) {
	0x10..0x12: switch_{$vehid}_{$livery}_graphics_gui; //drawn in windows: depot (0x10), vehicle details (0x11) and in vehicle list (0x12)
    switch_{$vehid}_{$livery}_position; //drawn on map
}
";

	//if it is an articulated vehicle
	if ($vehicleinfo['property']['vehicleparts'] > 1) {
		$nml .= "
/////////////////////
//vehicle callbacks//
/////////////////////
";	
		//articulated vehicle callback
		$nml .= 'switch (FEAT_ROADVEHS, SELF, switch_' .$vehid. '_articulated, extra_callback_info1 ) {' . PHP_EOL;
		$nml .= "\t" . '0..' . ($vehicleinfo['property']['vehicleparts'] - 1) . ': return item_roadvehicle_' .$vehid. ';' . PHP_EOL;
		$nml .= "\t" . 'return CB_FAILED;' . PHP_EOL;
		$nml .= '}' . PHP_EOL;
		
		//length callback
		$nml .= 'switch (FEAT_ROADVEHS, SELF, switch_' .$vehid. '_length, position_in_consist ) {' . PHP_EOL;
		for ($i = 0; $i < $vehicleinfo['property']['vehicleparts']; $i++) {
			$nml .= "\t";
			if ($i < $vehicleinfo['property']['vehicleparts'] - 1) {
				$nml .= $i . ': ';
			}
			$nml .= 'return ' .$vehicleinfo['graphic'][$livery]['part'.($i+1)]['length']. ';' . PHP_EOL;
		}
		$nml .= '}' . PHP_EOL;
		
		//capacity callback
		$nml .= 'switch (FEAT_ROADVEHS, SELF, switch_' .$vehid. '_capacity, position_in_consist ) {' . PHP_EOL;
		for ($i = 0; $i < $vehicleinfo['property']['vehicleparts']; $i++) {
			$nml .= "\t";
			if ($i < $vehicleinfo['property']['vehicleparts'] - 1) {
				$nml .= $i . ': ';
			}
			$nml .= 'return ' .$vehicleinfo['property']['capacity'][$i]. ' / cargo_unit_weight;' . PHP_EOL;
		}
		$nml .= '}' . PHP_EOL;
	}
	
	/*
	PROPERTY BLOCK
	*/
	
	//construct graphics rules for articulated vehicle, inserted in vehicle properties
	if ($vehicleinfo['property']['vehicleparts'] > 1) {
		$graphics_block_insert  = "articulated_part:               switch_{$vehid}_articulated;" . PHP_EOL;
		$graphics_block_insert .= "        length:                         switch_{$vehid}_length;" . PHP_EOL;
		$graphics_block_insert .= "        cargo_capacity:                 switch_{$vehid}_capacity;" . PHP_EOL;
		$graphics_block_insert .= "        purchase_cargo_capacity:        return ".$vehicleinfo['property']['purchase_cargo_capacity'].";";
		$property_block_insert = '';
	}
	//if it is not an articulated vehicle
    else {
       	$property_block_insert = 'length:                         ' . min($vehicleinfo['graphic']['default']['part1']['length'], 8) . ';';
		$graphics_block_insert = "cargo_capacity:                 {$vehicleinfo['property']['cargo_capacity']} / cargo_unit_weight;" . PHP_EOL;;
	}
	//vehicle properties
	$nml .= "	
//////////////////////
//vehicle properties//
//////////////////////

item (FEAT_ROADVEHS, item_roadvehicle_$vehid, $numvehid) {
    property {
        name:                           string({$vehicleinfo['str_name']});
        introduction_date:              date({$vehicleinfo['property']['introduction_date']});
        model_life:                     {$vehicleinfo['property']['model_life']};
        retire_early:                   {$vehicleinfo['property']['retire_early']};
        vehicle_life:                   {$vehicleinfo['property']['vehicle_life']};
        loading_speed:                  {$vehicleinfo['property']['loading_speed']}; //should be 5*number of doors
        cost_factor:                    {$vehicleinfo['property']['cost_factor']};
        running_cost_factor:            {$vehicleinfo['property']['running_cost_factor']};
        speed:                          {$vehicleinfo['property']['speed']} km/h;
        power:                          {$vehicleinfo['property']['power']} kW;
        weight:                         {$vehicleinfo['property']['weight']};
        cargo_capacity:                 {$vehicleinfo['property']['cargo_capacity']};
		{$property_block_insert}
        tractive_effort_coefficient:    {$vehicleinfo['property']['tractive_effort_coefficient']}; //percentage of powered axles
        air_drag_coefficient:           {$vehicleinfo['property']['air_drag_coefficient']}; //0=default
        //sound_effect:                 ; //not implemented
        //visual_effect:                visual_effect(VISUAL_EFFECT_DEFAULT, 0); //not implemented
        
        reliability_decay:              {$vehicleinfo['property']['reliability_decay']};
        climates_available:             ALL_CLIMATES;
        refittable_cargo_classes:       bitmask(CC_PASSENGERS);
        sprite_id:                      SPRITE_ID_NEW_ROADVEH;
        misc_flags:                     bitmask(ROADVEH_FLAG_TRAM, ROADVEH_FLAG_2CC, ROADVEH_FLAG_AUTOREFIT);
        refit_cost:                     0;
        running_cost_base:              RUNNING_COST_ROADVEH;
    }

    graphics {
        purchase:                       spriteset_{$vehid}_purchase;
        //additional_text:              //return string(STR_DESC_);
        {$graphics_block_insert}
        switch_{$vehid}_graphics;
    }
}

//cargo refit options
if (param_refit == 1) {
    item (FEAT_ROADVEHS, item_roadvehicle_{$vehid}) {
        property {
            refittable_cargo_classes:	bitmask(CC_PASSENGERS, CC_MAIL, CC_EXPRESS);
        }
    }
}
else if (param_refit == 2) {
    item (FEAT_ROADVEHS, item_roadvehicle_{$vehid}) {
        property {
            refittable_cargo_classes:	ALL_NORMAL_CARGO_CLASSES;
        }
    }
}

//reduced model life (default)
if (param_modellife == 0) {
    item (FEAT_ROADVEHS, item_roadvehicle_{$vehid}) {
        property {
            model_life:                 {$vehicleinfo['property']['model_life_reduced']};
			retire_early:               {$vehicleinfo['property']['retire_early_reduced']};
        }
    }
}";
	
	//return NML
	return $nml;
}
?>