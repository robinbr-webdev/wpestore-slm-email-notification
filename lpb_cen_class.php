<?php
class LPB_CEN extends WPLM_List_Licenses{
	function __construct(){
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'item',     //singular name of the listed records
            'plural'    => 'items',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
        
    }
	
	function prepare_items() {
        /**
         * First, lets decide how many records per page to show
         */
        $per_page = 50;
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);
        
        $this->process_bulk_action();
    	
    	global $wpdb;
        $license_table = SLM_TBL_LICENSE_KEYS;
        
	/* -- Ordering parameters -- */
	    //Parameters that are going to be used to order the result
	$orderby = !empty($_GET["orderby"]) ? strip_tags($_GET["orderby"]) : 'id';
	$order = !empty($_GET["order"]) ? strip_tags($_GET["order"]) : 'DESC';

        if (isset($_POST['slm_search'])) {
            $search_term = trim(strip_tags($_POST['slm_search']));
            $prepare_query = $wpdb->prepare("SELECT * FROM " . $license_table . " WHERE `license_key` LIKE '%%%s%%' OR `email` LIKE '%%%s%%' OR `txn_id` LIKE '%%%s%%' OR `first_name` LIKE '%%%s%%' OR `last_name` LIKE '%%%s%%'", $search_term, $search_term, $search_term,  $search_term, $search_term); 
            $data = $wpdb->get_results($prepare_query, ARRAY_A);
        }else{
			//$this->update_license_data();
            $data = $wpdb->get_results("SELECT * FROM $license_table where id in ( 
				select max(id) as id from $license_table group by email 
			) ORDER BY $orderby $order", ARRAY_A); 
           // $data = $wpdb->get_results("SELECT * FROM $license_table ORDER BY $orderby $order", ARRAY_A); 
		   
		   
        }
		
		/* echo "<pre>";
		print_r($data); 
		echo "</pre>";
         */
        $current_page = $this->get_pagenum();
        $total_items = count($data);
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);
        $this->items = $data;
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
        ) );
    }
}
?>