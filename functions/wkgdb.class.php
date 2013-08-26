<?php

class Wkgdb{
	private $wpdb;

	function __construct(){
		global $wpdb;

		$this->wpdb = $wpdb;
	}

	function get_all_list_count($filter = array()){
		$select_sql = "SELECT COUNT(*) FROM {$this->wpdb->wkg_kml_index}";
		$join_sql = $where_sql = $order_sql = $limit_sql = '';
        
        if(!empty($filters)){ // filter data
            $where_sql = 'WHERE';
            $i = 0;
            
            foreach($filters as $filter){
                if($i != 0){
                    $where_sql .= ' AND';
                }
                $where_sql .= ' ts1.tracking_code LIKE "%'.$filter.'%"';
                
                $i++;
            }
        }
        
        $sql = "$select_sql $join_sql $where_sql $order_sql $limit_sql";
        
        return $this->wpdb->get_var($sql);
	}

	public function get_kml_list($filters = array(), $limit = WKG_PER_PAGE, $offset = 0){
        $select_sql = "SELECT idx.*, COUNT(lst.id) AS list_items FROM {$this->wpdb->wkg_kml_index} AS idx";
        
        $join_sql = "LEFT JOIN {$this->wpdb->wkg_kml_list} AS lst ON idx.`id` = lst.`index_id`";
        
        $where_sql = "";
        
        if(!empty($filters)){ // filter data
            foreach($filters as $filter){
                //$where_sql .= ' AND ts1.tracking_code LIKE "%'.$filter.'%"';
            }
        }
        
        $order_sql = "GROUP BY lst.index_id ORDER BY idx.create_date DESC";
        
        if($limit > 0){
        	$limit_sql = "LIMIT $offset, $limit";	
        }else{
        	$limit_sql = '';
        }
        

        $sql = "$select_sql $join_sql $where_sql $order_sql $limit_sql";
        
        return $this->wpdb->get_results($sql);
    }

	function get_list_by_id($id = 0){
		$sql = "SELECT * FROM {$this->wpdb->wkg_kml_index} WHERE `id` = $id";

		$result = $this->wpdb->get_row($sql, ARRAY_A);

		if(!empty($result)){
			$sql = "SELECT * FROM {$this->wpdb->wkg_kml_list} WHERE `index_id` = ".$id;

			$result['points'] = $this->wpdb->get_results($sql, ARRAY_A);
		}

		return $result;
	}

	function get_list_by_slug($slug = ''){
		$sql = "SELECT * FROM {$this->wpdb->wkg_kml_index} WHERE `slug` = '$slug'";

		$result = $this->wpdb->get_row($sql);

		if(!empty($result)){
			$sql = "SELECT * FROM {$this->wpdb->wkg_kml_list} WHERE `index_id` = ".$result->id;

			$result->points = $this->wpdb->get_results($sql);

			$sql = "SELECT `icon` FROM {$this->wpdb->wkg_kml_list} WHERE `index_id` = $result->id GROUP BY `icon`";

			$icons = $this->wpdb->get_results($sql);

			foreach($icons as $icon){
				$result->styles[] = $icon->icon;
			}
		}

		return $result;
	}

	function get_cache_by_slug($slug = '', $time = 0){
		$select_sql = "SELECT MAX(che.cache_timestamp) AS cachedfile FROM {$this->wpdb->wkg_kml_cache} AS che";
        
        $join_sql = "LEFT JOIN {$this->wpdb->wkg_kml_index} AS idx ON idx.`id` = che.`index_id`";

        $where_sql = "WHERE idx.`slug` = '$slug' HAVING cachedfile > $time";

        $sql = "$select_sql $join_sql $where_sql";

        return $this->wpdb->get_var($sql);

	}

	function slug_exists($slug = '', $id = 0){
		if($slug != ''){
	        $sql = "SELECT COUNT(*) FROM {$this->wpdb->wkg_kml_index} WHERE `slug` = '$slug' AND `id` <> $id";
	        
	        return ($this->wpdb->get_var($sql) != 0);
		}

		return true;
	}

	function insert_cache($data = array()){
		return $this->wpdb->insert($this->wpdb->wkg_kml_cache, $data);
	}

	function insert_kml_list($data = array()){
		$success = true;

		if(!empty($data)){
			$index_data = array(
				'title' => $data['title'],
				'slug' => $data['slug'],
				'user_id' => get_current_user_id(),
				'create_date' => time()
				);

			if( $this->wpdb->insert($this->wpdb->wkg_kml_index, $index_data) ){
				$index_id = $this->wpdb->insert_id;

				if(!empty($data['points'])){
					foreach($data['points'] as $point){
						$point_data = array(
							'index_id' => $index_id,
							'icon' => $point['icon'],
							'name' => $point['name'],
							'address' => $point['address'],
							'lat' => floatval($point['lat']),
							'lng' => floatval($point['lng'])
							);
						$this->wpdb->insert($this->wpdb->wkg_kml_list, $point_data);
					}
				}
			}else{
				$success = false;
			}
		}else{
			$success = false;
		}

		return $success;
	}

	function update_kml_list($id = 0, $data = array()){
		$success = true;

		if($id && !empty($data)){
			$index_data = array(
				'title' => $data['title'],
				'slug' => $data['slug']
				);

			if($this->wpdb->update($this->wpdb->wkg_kml_index, $index_data, array('id'=>$id)) !== false){
				$this->_clean_old_list_items($id);

				if(!empty($data['points'])){
					foreach($data['points'] as $point){
						$point_data = array(
							'index_id' => $id,
							'icon' => $point['icon'],
							'name' => $point['name'],
							'address' => $point['address'],
							'lat' => floatval($point['lat']),
							'lng' => floatval($point['lng'])
							);
						$this->wpdb->insert($this->wpdb->wkg_kml_list, $point_data);
					}
				}
			}else{
				$success = false;
			}
		}else{
			$success = false;
		}

		return $success;
	}

	function delete_kml_list($id = 0){
		$error = 0;

		if(!$this->wpdb->delete($this->wpdb->wkg_kml_index, array('id'=>$id))){
			$error ++;
			return ($error == 0);
		}

		$this->wpdb->delete($this->wpdb->wkg_kml_list, array('index_id'=>$id));

		return ($error == 0);
	}

	function clear_cache($time = 0){
		if($time > 0){
			$sql = "DELETE FROM {$this->wpdb->wkg_kml_cache} WHERE `cache_timestamp` < $time";
			return $this->wpdb->query($sql);
		}
	}

	private function _clean_old_list_items($index_id = 0){
		if($index_id){
			return $this->wpdb->delete($this->wpdb->wkg_kml_list, array('index_id'=>$index_id));
		}
		return false;
	}
}

/* End of wkgdb.class.php */