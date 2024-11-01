<?php

namespace Expose;

class DDos
{
    
    /**
     * Filter tag set
     * @var array
     */
    private $maxRequest = array();
    /**
     * Filter tag set
     * @var array
     */
    private $timeInterval = array();

    /**
     * Filter impact rating
     * @var integer
     */
    private $cache = null;

    /**
     * Init the filter and set the data if given
     *
     * @param array $data Filter data [optional]
     */
    public function __construct(\Expose\Cache $cache = null)
    {
        if (!is_null($cache))
            $this->setCache($cache);
		
		$this->maxRequest = 5;
		$this->timeInterval = 10; // seconds
    }

    /**
     * Load the data into the filter object
     *
     * @param array $data Filter data
     */
    public function checkIp($ip = null)
    {
        $out = true;
		if ( is_null($ip)) {
			return !$out;
		}
		$info = array(
			'ip' => $ip,
			'count' => 0,
			'last' => microtime(true)
			//'last' => (float) 1426097127
		);
		
		$sig = md5($ip);
        $cache = $this->getCache();
        if ($cache !== null) {
            $cacheData = $cache->get($sig);
            if ($cacheData !== null) {
                $info = $cacheData;
            }
        }
		
		var_dump(array(__METHOD__, $info));
		
		$last = microtime(true);
		echo 'resta: ' . ( $last - $info['last'] ) . ' - limite: ' . ( $this->timeInterval);
		if( ( $last - $info['last'] ) < ( $this->timeInterval ))
		{
			if ( $info['count'] + 1 <= $this->maxRequest)  {
				// ok
				$info['count']++;
				$info['last'] = $last;
			} else { $out = false;}
		} else { }
		
		if ( $cache !== null ) {
			$cache->save($sig, $info);
		}
		return $out;
    }

    /**
     * Set the cache object
     *
     * @param ExposeCache $cache Cache instance
     */
    public function setCache(\Expose\Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Get the current cache instance
     *
     * @return mixed Either a \Expose\Cache instance or null
     */
    public function getCache()
    {
        return $this->cache;
    }
}
