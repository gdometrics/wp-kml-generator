<?php
class KML{

	function __construct(){
		
	}

	function output($data = array(), $echo = false){
		$kml = $this->_generate_kml($data, $echo);

		return $kml;
	}

	function set_header(){
		header('Content-type: application/vnd.google-earth.kml+xml, charset=utf-8');
	}

	private function _generate_kml($data = array(), $echo = false){
		// Creates an array of strings to hold the lines of the KML file.
		$kml = array('<?xml version="1.0" encoding="UTF-8"?>');
		$kml[] = '<kml xmlns="http://earth.google.com/kml/2.2">';
		$kml[] = ' <Document>';

		$kml[] = ' <name>'.htmlentities($data->title, ENT_COMPAT, 'UTF-8').'</name>';

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
				$kml[] = ' <name>' . htmlentities($point->name, ENT_COMPAT, 'UTF-8') . '</name>';
				//$kml[] = ' <description>' . htmlentities($row['address']) . '</description>';
				$kml[] = ' <styleUrl>#' . (chopExtension($point->icon)) .'-Style</styleUrl>';
				if(isset($point->address) && !empty($point->address)){
					$kml[] = ' <address>'.htmlentities($point->address, ENT_COMPAT, 'UTF-8').'</address>';
					$kml[] = ' <description>' . htmlentities($point->address, ENT_COMPAT, 'UTF-8') . '</description>';
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

		// End XML file
		$kml[] = ' </Document>';
		$kml[] = '</kml>';
		$kmlOutput = join("\n", $kml);

		if($echo){
			$this->set_header();
			echo $kmlOutput;
		}else{
			return $kmlOutput;
		}
	}
}
/* End of kml.class.php */