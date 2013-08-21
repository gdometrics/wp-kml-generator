<?php
class KML{

	function __construct(){
		
	}

	function output($data = array(), $echo = false){
		$this->_generate_kml($data, $echo);

		die();
	}

	private function _generate_kml($data = array(), $echo = false){
		// Creates an array of strings to hold the lines of the KML file.
		$kml = array('<?xml version="1.0" encoding="UTF-8"?>');
		$kml[] = '<kml xmlns="http://earth.google.com/kml/2.2">';
		$kml[] = ' <Document>';

		$kml[] = ' <name>'.htmlentities($data->title).'</name>';

		if(isset($data->styles) && !empty($data->styles)){
			foreach($data->styles as $style){
				$kml[] = ' <Style id="'.chopExtension($style).'-Style">';
				$kml[] = ' <IconStyle id="'.chopExtension($style).'">';
				$kml[] = ' <Icon>';
				$kml[] = ' <href>'.WKG_ICONS_URL.'/'.$style.'</href>';
				$kml[] = ' </Icon>';
				$kml[] = ' </IconStyle>';
				$kml[] = ' </Style>';
			}
		}

		if(isset($data->points) && !empty($data->points)){
			foreach($data->points as $point){
				$kml[] = ' <Placemark id="placemark' . $point->id . '">';
				$kml[] = ' <name>' . htmlentities($point->name) . '</name>';
				//$kml[] = ' <description>' . htmlentities($row['address']) . '</description>';
				$kml[] = ' <styleUrl>#' . (chopExtension($point->icon)) .'-Style</styleUrl>';
				if(isset($point->address) && !empty($point->address)){
					$kml[] = ' <address>'.htmlentities($point->address).'</address>';
					$kml[] = ' <description>' . htmlentities($point->address) . '</description>';
				}

				if((isset($point->lat) && !empty($point->lat))
					&& (isset($point->lng) && !empty($point->lng))){
					$kml[] = ' <Point>';
					$kml[] = ' <coordinates>' . $point->lng . ','  . $point->lat . '</coordinates>';
					$kml[] = ' </Point>';
				}
				
				$kml[] = ' </Placemark>';
			}
		}
		// Iterates through the rows, printing a node for each row.
		
		// while ($row = @mysql_fetch_assoc($result)) 
		// {
		//   $kml[] = ' <Placemark id="placemark' . $row['id'] . '">';
		//   $kml[] = ' <name>' . htmlentities($row['name']) . '</name>';
		//   $kml[] = ' <description>' . htmlentities($row['address']) . '</description>';
		//   $kml[] = ' <styleUrl>#' . ($row['type']) .'Style</styleUrl>';
		//   $kml[] = ' <Point>';
		//   $kml[] = ' <coordinates>' . $row['lng'] . ','  . $row['lat'] . '</coordinates>';
		//   $kml[] = ' </Point>';
		//   $kml[] = ' </Placemark>';
		 
		// } 

		// End XML file
		$kml[] = ' </Document>';
		$kml[] = '</kml>';
		$kmlOutput = join("\n", $kml);
		header('Content-type: application/vnd.google-earth.kml+xml');
		echo $kmlOutput;
	}
}
/* End of kml.class.php */