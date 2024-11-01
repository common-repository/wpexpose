<?php
if ( ! defined( 'WPEX_folder' ) || ! defined( 'ABSPATH' ) ) exit;


if( ! class_exists( 'WP_List_Table_Copied' ) ) {
    require_once( WPEX_folder . DIRECTORY_SEPARATOR . 'classes'. DIRECTORY_SEPARATOR .'class-wp-list-table-copied.php' );
}

/**
 * Create a new table class that will extend the WP_List_Table.
 * Source: http://www.paulund.co.uk/wordpress-tables-using-wp_list_table
 */
class Expose_List_Table extends WP_List_Table_Copied
{
    private $db = null;
    private $table = WPEX_instrusions; //'expose_intrusions';
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
            'name'       => __('Variable'),
			'description'  => __('Description'),
			'value'        => __('Value'),
            'page' => __('Page'),
            'ip'    => 'IP',
            'ip2'    => 'IP2',
            'tags'    => __('Tags'),
            'impact'      => __('Impact'),
            'origin'      => __('Origin'),
            'created'      => __('Created'),
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
            'page' => array('page', false),
            'description' => array('description', false),
            'ip' => array('ip', false),
            'name' => array('name', false),
            'impact' => array('impact', false),
            'tags' => array('tags', false),
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
            case 'name':
            case 'page':
            case 'value':
            case 'description':
            case 'ip':
            case 'ip2':
            case 'tags':
            case 'impact':
            case 'origin':
            case 'created':
                return htmlspecialchars($item->$column_name, null, 'UTF-8');
            default:
                return print_r( $item, true ) ;
        }
    }
    // Used to display the value of the id column
	public function column_ip($item)
	{
		return '<a href="' . $this->urlIp.$item->ip . '">'.$item->ip.'</a>';
	}
	public function column_ip2($item)
	{
		return '<a href="' . $this->urlIp.$item->ip2 . '">'.$item->ip2.'</a>';
	}
	public function column_origin($item)
	{
		return '<a href="' . $this->urlIp.$item->origin . '">'.$item->origin.'</a>';
	}
    
	public function column_description($item)
	{
		return $item->description;
	}
	
	public function column_value($item)
	{
		return substr ($item->value, 0, 50) . '<br>' . $this->jsCode(md5($item->value), $item->value);
	}
	
	public function column_page($item)
	{
		return substr ($item->page, 0, 50) . '<br>' . $this->jsCode(md5($item->page), $item->page);
	}
	
	private function jsCode($name, $value)
	{
		return '<script>'. "\n".
		'function newWin'.$name.'(){'."\n".
		'var windowCode = window.open("", "MsgWindow", "directories=0,location=0,menubar=0,resizable=1,scrollbars=1");'."\n".
		'windowCode.document.write("<textarea style=\'width:100%;height:100%;\'>'.htmlentities( str_replace("\n",'', $value)).' + </textarea>");'."\n".
		'}</script>'."\n".
		'<a href="javascript:newWin'.$name.'();">View full code</a>'."\n";
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