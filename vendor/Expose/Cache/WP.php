<?php  
namespace Expose\Cache;

class WP extends \Expose\Cache
{
	/**
	 * Path for cache files
	 * @var string
	 */
	private $keys = array();
    private $usefull;
	private $hasW3TC;
    private $w3_pgcache;
    
    public function __construct() {
        $this->usefull = false;
		$this->hasW3TC = false;
		// este cache no es persistente.
		// https://codex.wordpress.org/Class_Reference/WP_Object_Cache#Persistent_Caching
        if (function_exists('wp_cache_get')) {
			$this->usefull = true;
        };
		if (defined('W3TC_DIR')) { 
			require_once ( W3TC_DIR . '/lib/W3/PgCache.php' );
			if (class_exists('W3_PgCache', false)) {
                // y esta activo!!
				$this->w3_pgcache = & W3_PgCache::instance()->_get_cache();
                //ACABAR!!!
                $this->hasW3TC = true;
			};
		}
		//echo '<pre>' . print_r(array('usefull', $this->usefull, 'hasW3TC', $this->hasW3TC), true) . '</pre>';
    }

	/**
	 * Save the cache data to a file
	 *
	 * @param string $key Identifier key (used in filename)
	 * @param mixed $data Data to cache
	 * @return boolean Success/fail of save
	 */
	public function save($key, $data)
	{
        $status = false;
        if ( $this->usefull ) {
            $status = wp_cache_set(md5($key), $data, 'wp-expose');
			if (false !== $status) $this->keys[] = $key;
        }
        return $status;
	}

	/**
	 * Get the record identified by the given key
	 *
	 * @param string $key Cache identifier key
	 * @return mixed Returns either data or null if not found
	 */
	public function get($key)
	{
        $out = false;
        if ( $this->usefull ) {
            $out = wp_cache_get(md5($key), 'wp-expose');
        }
		return $out;
	}
	
	public function flush() {
		if ( $this->usefull )
			wp_cache_flush();
	}
	
	public function delete($key) {
		if ( $this->usefull ) {
			wp_cache_delete( md5($key), 'wp-expose' );
		}
	}

}

