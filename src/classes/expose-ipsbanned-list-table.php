<?php 
if ( ! defined( 'WPEX_folder' ) || ! defined( 'ABSPATH' ) ) exit;

if( ! class_exists( 'WP_List_Table_Copied' ) ) {
    require_once( WPEX_folder . DIRECTORY_SEPARATOR . 'classes'. DIRECTORY_SEPARATOR .'class-wp-list-table-copied.php' );
}

/**
 * Create a new table class that will extend the WP_List_Table.
 * Source: http://www.paulund.co.uk/wordpress-tables-using-wp_list_table
 */
class Expose_Ipsbanned_List_Table extends WP_List_Table_Copied
{
    private $db = null;
    private $table = WPEX_ipban; //'expose_intrusions';
	private $urlIp = 'https://db-ip.com/';
    
    /**
     * Create an Expose_List_Table's instance.
     * @param type $db a $wpdb copy
     */
    public function __construct($db) {
        if ($db !== null)
            $this->setDb($db);
        parent::__construct();
    }
    /**
     * Set database connection object. It must be a $wpdb copy.
     * @param type $value a $wpdb copy
     */
    public function setDb($value) {
        $this->db = $value;
    }
    
    /**
     * Return database connection object
     * @return type
     */
    public function getDb() {
        return $this->db;
    }
    
    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items($perPage = 1)
    {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();
        $data = $this->table_data();
		
        // sorting data
        usort( $data, array( &$this, 'sort_data' ) );
		
        // pagination
		$perPage = ( (int) $perPage <= 0) ? 10 : (int) $perPage;
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);

        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ) );

        $data = array_slice($data,(($currentPage-1)*$perPage),$perPage);
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }
    
	// http://stackoverflow.com/a/37280740/3270873
	public function objectToArray($data)
    {
        if (is_object($data)) {
            $data = get_object_vars($data);
        }

        if (is_array($data)) {
            return array_map(array($this, 'objectToArray'), $data);
        }

        return $data;
    }
	
	public function sort_data( $a, $b ) {
		
		$aa = $this->objectToArray($a);
		$bb = $this->objectToArray($b);
		
		// If no sort, default to title
		$orderby = ( ! empty( $_GET['orderby'] ) && array_key_exists($_GET['orderby'], $this->get_sortable_columns())) ? $_GET['orderby'] : 'created';
		
		// If no order, default to asc
		$order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';
		// Determine sort order
		$result = strcmp( $aa[$orderby], $bb[$orderby] );
		// Send final sort direction to usort
		return ( $order === 'asc' ) ? $result : -$result;
	}
	
	
    /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    public function get_columns()
    {
        $columns = array(
            'id'         => 'ID',
            'ip4'    => __('IPv4'),
            'ip6'    => __('IPv6'),
            'created'  => __('Created'),
            'operation' => __('Operation'),
        );

        return $columns;
    }
    
    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns()
    {
        return array('id');
    }
    
    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns()
    {
        return array( 
            'ip4' => array('ip', false),
            'ip6' => array('name', false),
            'created' => array('created', true),
        );
    }
    
    /**
     * Get the table data
     *
     * @return Array
     */
    private function table_data()
    {
        $data = array();
        
        $s = (isset($_POST['s'])) ? sanitize_text_field($_POST['s']) : '';
        $cache_key = 'admin_expose_intrusions' . md5($s);
        $data = wp_cache_get($cache_key, 'plugins');
        if ( ( $data === FALSE || empty($data) ) && $this->getDb() !== null) {
            $sql = 'SELECT * FROM ' .$this->db->prefix . $this->table;
            if(!empty($s)) {
                $s = preg_replace('/[%*]/i', "", $s);
                $sql .= ' WHERE value like "%'.$s.'%"';
            }
            $sql .= ' ORDER BY created DESC';
            $data = $this->db->get_results( $sql, OBJECT );
            wp_cache_set($cache_key, $data, 'plugins');
        }
        return $data;
    }
    
    /**
     * Define what data to show on each column of the table
     *
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default( $item, $column_name )
    {
        switch( $column_name ) {
            case 'id':
            case 'ip4':
            case 'ip6':
            case 'created':
                return htmlspecialchars($item->$column_name, null, 'UTF-8');
            default:
                return print_r( $item, true ) ;
        }
    }
    // Used to display the value of the id column
	public function column_ip4($item)
	{
		return '<a href="' . $this->urlIp.$item->ip4 . '">'.$item->ip4.'</a>';
	}
    
	public function column_ip6($item)
	{
		return $item->ip6;
	}
	public function column_operation($item)
	{
		return '<a href="'.wp_nonce_url( admin_url('admin.php?page=wp-expose-admin-page-ipban.php&wpe_action=unblock&id=' . $item->id), 'wp_expose' ).'">'. __('Unblock') .'</a>';
	}
	
	
	
	/**
	 * Generates the columns for a single row of the table
	 *
	 * @since 3.1.0
	 * @access protected
	 *
	 * @param object $item The current item
	 */
	protected function single_row_columns( $item ) {
		list( $columns, $hidden ) = $this->get_column_info();

		foreach ( $columns as $column_name => $column_display_name ) {
			$class = "class='$column_name column-$column_name'";

			$style = '';
			if ( in_array( $column_name, $hidden ) )
				$style = ' style="display:none;"';

			$attributes = "$class$style";

			if ( 'cb' == $column_name ) {
				echo '<th scope="row" class="check-column">';
				echo $this->column_cb( $item );
				echo '</th>';
			}
			elseif ( method_exists( $this, 'column_' . $column_name ) ) {
				echo "<td $attributes>";
				echo call_user_func( array( $this, 'column_' . $column_name ), $item );
				echo "</td>";
			}
			else {
				echo "<td $attributes>";
				echo $this->column_default( $item, $column_name );
				echo "</td>";
			}
		}
	}
}