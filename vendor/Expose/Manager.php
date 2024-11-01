<?php

namespace Expose;

class Manager
{
    /**
     * Data to run the filter validation rules on
     * @var array
     */
    private $data = null;

    /**
     * Set of filters to execute
     * @var \Expose\FilterCollection
     */
    private $filters = null;

    /**
     * Overall impact score of the filter execution
     * @var integer
     */
    private $impact = 0;

    /**
     * If set, stop running filters when reached
     * @var integer
     */
    private $impactLimit = 0;

    /**
     * Report results from the filter execution
     * @var array
     */
    private $reports = array();

    /**
     * Names of varaibles to ignore (exceptions to the rules)
     * @var array
     */
    private $exceptions = array();

    /**
     * Data "paths" to restrict checking to
     * @var array
     */
    private $restrctions = array();

    /**
     * Logger instance
     * @var object
     */
    private $logger = null;

    /**
     * Configuration object
     * @var \Expose\Config
     */
    private $config = null;

    /**
     * Queue instance (extends \Expose\Queue)
     * @var object
     */
    private $queue = null;

    /**
     * Notify instance (for results notification)
     * @var object
     */
    private $notify = null;

    /**
     * Threshold setting
     * @var integer
     */
    private $threshold = null;

    /**
     * Caching mechanism
     * @var \Expose\Cache
     */
    private $cache = null;

	
    /**
     * Url decoding
     * @var boolean
     */
    private $useConverter = true;
    
    private $storage = null;
    
    /**
     * Report results from the filter execution
     * @var array
     */
    private $reportsGrouped = array();
	
    /**
     * Init the object and assign the filters
     *
     * @param \Expose\FilterCollection $filters Set of filters
     */
    public function __construct(
        \Expose\FilterCollection $filters,
        \Psr\Log\LoggerInterface $logger = null,
        \Expose\Queue $queue = null,
        \Expose\Storage $storage = null
    )
    {
        $this->setFilters($filters);
		$this->setPHPIDSConverter(false);
        if ($logger !== null) {
            $this->setLogger($logger);
        }
        if ($queue !== null) {
            $this->setQueue($queue);
        }
        if ($storage !== null)
            $this->setStorage($storage);
    }

    /**
     * Run the filters against the given data
     *
     * @param array $data Data to run filters against
     */
    public function run(array $data, $queueRequests = false, $notify = false, $store = false, $stop = false)
    {
        $this->getLogger()->info('Executing on data '.md5(print_r($data, true)));
        if ($queueRequests === true) {
            $this->logRequest($data);
            return true;
        }

        $this->setData($data);
        $data = $this->getData();

        $path = array();
        $filterMatches = $this->runFilters($data, $path);
        $impact = $this->impact;

        // Check our threshold to see if we even need to send
        $threshold = $this->getThreshold();
        if ($threshold != null && $impact >= $threshold ) {
			$out = true;
            if ( $store === true) {
				$out = $this->saveStorage($data);
            }
            if ( $out === true && $notify == true ) {
                //$out = $this->sendNotification($filterMatches);
                $out = $this->sendNotification( $this->getReportsGrouped() );
            }
            if ( $stop == true ) {
                $this->stopExecution();
            }
            
			return $out;
        } 
        else if ($threshold === null && $notify === true) {
            return $this->sendNotification($filterMatches);
        }
        return true;
    }
	
	/**
	 * 
	 * @param type $data
	 * @return boolean
	 */
	public function saveStorage($data) {
		
		if (count($this->getReports()) > 0) {
			$ip2 = $_SERVER['REMOTE_ADDR'];
            $ip = (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $ip2;

			foreach ($this->getReports() as $report)
			{
				foreach ($report->getFilterMatch() as $filter) {					
					$t = array(
						'name' => $report->getVarName(),
						'description' => htmlentities($filter->getDescription()),
						'value' => htmlentities($report->getVarValue()),
						'page' => ( isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''),
						'tags' => implode(",", $filter->getTags()),
						'ip' => $ip,
						'ip2' => $ip2,
						'impact' => $filter->getImpact(),
						'origin' => $_SERVER['SERVER_ADDR'],
						'created' => date('Y-m-d H:i:s')
					);	
					$this->getStorage()->save($t);
                }
			}
        }
        return true;
	}
    
    /**
     * 
     */
    private function stopExecution() {
        die( __('Access denied'));
    }
    
    /**
     * Send the notification of the matching filters
     *
     * @param array $filterMatches Set of matching filter results
     * @return boolean Success/fail of notification send
     */
    public function sendNotification($filterMatches)
    {
        if (count($filterMatches) > 0) {
            return $this->notify($filterMatches);
        }
        return true;
    }

    /**
     * Run through the filters on the given data
     *
     * @param array $data Data to check
     * @param array $path Current "path" in the data
     * @param integer $lvl Current nesting level
     * @return array Set of filter matches
     */
    public function runFilters($data, $path, $lvl = 0)
    {
        $filterMatches = array();
        $restrictions = $this->getRestrictions();
		if (is_array($data))
			$data = new \ArrayIterator($data);
		$data->rewind();
        //echo '<pre>' . print_r(array(__METHOD__, 'data', $data, $path, $lvl), true) . '</pre>';
        
		$sig = md5(print_r($data, true));
        $cache = $this->getCache();
		//echo '<pre>' . print_r(array(__METHOD__, 'cache', $cache), true) . '</pre>';
        if ($cache !== null) {
            $cacheData = $cache->get($sig);
            if (false !== $cacheData) {
                //return $cacheData;
				$this->reports = $cacheData['reports'];
				$this->impact = $cacheData['impact'];
				$this->getLogger()->info('Data retrieved from cache - '.$sig);
				return $cacheData['filterMatches'];
            }
        }
		//if (is_array($data)) $data = new \ArrayIterator($data);
		//echo '<pre>' . print_r($data, true) . '</pre>';
		
        while($data->valid() && !$this->impactLimitReached()) {
            $index = $data->key();
            $value = $data->current();
            $data->next();
			
			//echo '<pre>' . print_r(array('value', $value), true) . '</pre>';
            if (count($path) > $lvl) {
                $path = array_slice($path, 0, $lvl);
            }
            $path[] = $index;
			//echo '<pre>' . print_r(array('path', $path), true) . '</pre>';
            // see if it's an exception
            if ($this->isException(implode('.', $path))) {
                $this->getLogger()->info('Exception found on '.implode('.', $path));
                continue;
            }
            $p = implode('.', $path);
            // See if we have restrictions & if the path matches
            if (!empty($restrictions) && !in_array($p, $restrictions)) {
                $this->getLogger()->info(
                    'Restrictions enabled, no match on path '.implode('.', $path),
                    array('restrictions' => $restrictions)
                );
                continue;
            }

            if (is_array($value)) {
				//echo '<pre>' . print_r('dentro', true) . '</pre>';
                $l = $lvl+1;
                $filterMatches = array_merge(
                    $filterMatches,
                    $this->runFilters($value, $path, $l)
                );
				//echo '<pre>' . print_r(array('saltamos...',$filterMatches), true) . '</pre>';
                continue;
				//echo '<pre>' . print_r('no debieramos ver esto', true) . '</pre>';
            }
			
			if ( $this->getPHPIDSConverter() === true) {
				//echo '<pre>' . print_r(array('before centrifuge: ', $value), true) .'</pre>';
				$value = \PhpIds\IDS_Converter::runAll($value);
				$value = \PhpIds\IDS_Converter::runCentrifuge($value);
				//echo '<pre>' . print_r(array('after centrifuge: ', $value), true) .'</pre>';
			}
            $filterMatches = array_merge($filterMatches, $this->processFilters($value, $index, $path));
        }
        //$filterMatches = array_merge($filterMatches, $this->processFilters($report, $value, $index, $path));
		
        if ($cache !== null) {
			$cache->save($sig, array (
				'reports' => $this->reports,
				'impact' => $this->impact,
				'filterMatches' => $filterMatches
			));
        }
        return $filterMatches;
    }
	
    /**
     * Runs value through all filters
     * @param $value
     * @param $index
     * @param $path
     */
    protected function processFilters($value, $index, $path)
    {
        $filtersMatched = array();
        $filters = $this->getFilters();
        $filters->rewind();
        while($filters->valid() && !$this->impactLimitReached()) {
            $filter = $filters->current();
            $filters->next();
            
			// added: to detect URL encoded parameters
            //if ($this->useConverter)
				//$value = urldecode($value);
            
            if ($filter->execute($value) === true) {
                $this->getLogger()->info(
                    'Match found on Filter ID '.$filter->getId(),
                    array($filter->toArray())
                );

                $report = new \Expose\Report($index, $value, $path);
                $report->addFilterMatch($filter);
                $this->reports[] = $report;
                $this->impact += $filter->getImpact();
                // added
                $filtersMatched[] = $filter;
            }
        }
        // added
        return $filtersMatched;
    }

    /**
     * Tests if the impact limit has been reached
     *
     * @return bool
     */
    protected function impactLimitReached()
    {
        if ($this->impactLimit < 1) {
            return false;
        }

        $reached = $this->impact >= $this->impactLimit;
        if ($reached) {
            $this->getLogger()->info(
                'Reached Impact limit'
            );
        }
        return $reached;
    }

    /**
     * Sets the impact limit
     *
     * @param $value
     */
    public function setImpactLimit($value)
    {
        $this->impactLimit = (int) $value;
    }

    /**
     * If enabled, send the notification of the test run
     *
     * @param array $filterMatches Set of matches against filters
     * @throws \InvalidArgumentException If notify type is inavlid
     */
    public function notify(array $filterMatches)
    {
        $notify = $this->getNotify();
        if ($notify === null) {
            throw new \InvalidArgumentException(
                'Invalid notification method'
            );
        }
		echo '<h1>Enviamos...</h1>';
        $notify->send($filterMatches);
    }

    /**
     * Log the request information
     *
     * @param array $requestData Request data
     */
    public function logRequest($requestData)
    {
        $queue = $this->getQueue();
        $queue->add($requestData);
    }

    /**
     * Get the current queue object
     *     If none is set, throws an exception, we need it!
     *
     * @return object Queue instance
     */
    public function getQueue()
    {
        if ($this->queue === null) {
            throw new \Expose\Exception\QueueNotDefined('Queue object is not defined');
        }
        return $this->queue;
    }

    /**
     * Set the current queue object
     *     Extends \Expose\Queue
     *
     * @param object $queue Queue instance
     */
    public function setQueue($queue)
    {
        $this->queue = $queue;
    }

    /**
     * Set the notification method for the results
     *
     * @param \Expose\Notify $notify Notification object
     */
    public function setNotify($notify)
    {
	   $this->notify = $notify;
    }

    /**
     * Get the notification method for the results
     *
     * @return \Expose\Notify instance
     */
    public function getNotify()
    {
	   return $this->notify;
    }

    /**
     * Get the current set of reports
     *
     * @return array Set of \Expose\Reports
     */
    public function getReports()
    {
        return $this->reports;
    }
	
    /**
     * Get the current set of reports grouped by variable
     *
     * @return array Set of \Expose\Reports
     */
    public function getReportsGrouped($force = false)
    {
        if(!$force && is_array($this->reportsGrouped) && !empty($this->reportsGrouped))
            return $this->reportsGrouped;
        $groupedReports = array();
        $index = 0;
		foreach ( $this->reports as $report) {
			$path = implode('.', $report->getVarPath());
            if(!array_key_exists ($path, $groupedReports)) {
                $groupedReports[$path] = array();
            }
            $groupedReports[$path]['value'] = $report->getVarValue();
			foreach ($report->getFilterMatch() as $filter) {
				$groupedReports[$path]['filters'][] = $filter;
			}
		}
		
		//echo '<pre>' . print_r($groupedReports, true). '</pre>';
        $this->reportsGrouped = $groupedReports;
		return $this->reportsGrouped;
    }

    /**
     * Get the current overall impact score
     *
     * @return integer Impact score
     */
    public function getImpact()
    {
        return $this->impact;
    }

    /**
     * Set the overall impact value of the execution
     *
     * @param integer $impact Impact value
     */
    public function setImpact($impact)
    {
        $this->impact = $impact;
    }

    /**
     * Set the source data for the execution
     *
     * @param array $data Data to validate
     */
    public function setData(array $data)
    {
        $this->data = new \Expose\DataCollection($data);
    }

    /**
     * Get the current source data
     *
     * @return array Source data
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set the filters for the current validation
     *
     * @param \Expose\FilterCollection $filters Filter collection
     */
    public function setFilters(\Expose\FilterCollection $filters)
    {
        $this->filters = $filters;
    }

    /**
     * Get the current set of filters
     *
     * @return \Expose\FilterCollection Filter collection
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Add a variable name for an exception
     *
     * @param string $varName Variable name
     */
    public function setException($path)
    {
        $path = (!is_array($path)) ? array($path) : $path;
        $this->exceptions = array_merge($this->exceptions, $path);
    }

    /**
     * Get a list of all exceptions
     *
     * @return array Exception list
     */
    public function getExceptions()
    {
        return $this->exceptions;
    }

    /**
     * Add a path to restrict the checking to
     *
     * @param string|array $path Path(s) to add to the restrictions
     */
    public function setRestriction($path)
    {
        $path = (!is_array($path)) ? array($path) : $path;
        $this->restrctions = array_merge($this->restrctions, $path);
    }

    /**
     * Get the list of all current restrictions
     *
     * @return array Set of restrictions
     */
    public function getRestrictions()
    {
        return $this->restrctions;
    }

    /**
     * Get the log "resource" (Ex. database table name)
     * @return string Resouce name
     */
    public function getLogResource()
    {
        return $this->logResource;
    }

    /**
     * Set the log "resource" name
     * @param string $resourceName Resource name
     */
    public function setLogResource($resourceName)
    {
        $this->logResource = $resourceName;
    }

    /**
     * Get the logging database name
     * @return string Database name
     */
    public function getLogDatabase()
    {
        return $this->logDatabase;
    }

    /**
     * Set the logging database name
     * @param string $dbname Database name
     */
    public function setLogDatabase($dbname)
    {
        $this->logDatabase = $dbname;
    }

    /**
     * Test to see if a variable is an exception
     *     Checks can be exceptions, so we preg_match it
     *
     * @param string $path Variable "path" (Ex. "POST.foo.bar")
     * @return boolean Found/not found
     */
    public function isException($path)
    {
        $isException = false;
        foreach ($this->exceptions as $exception) {
            if ($isException === false) {
                if ($path === $exception || preg_match('/^'.$exception.'$/', $path) !== 0) {
                    $isException = true;
                }
            }
        }

        return $isException;
    }

    /**
     * Set the current instance's logger object
     *
     * @param object $logger PSR-3 compatible Logger instance
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * Get the current logger instance
     *     If it's not set, throw an exception - we need it!
     *
     * @return object PSR-3 compatible logger object
     */
    public function getLogger()
    {
        if ($this->logger === null) {
            //throw new \Expose\Exception\LoggerNotDefined('Logger instance not defined');
			$this->logger = new \Expose\Log\EmptyLog();
        }
        return $this->logger;
    }

    /**
     * Set the configuration for the object
     *
     * @param array|string $config Either an array of config settings
     *     or the path to the config file
     * @throws \InvalidArgumentException If config file doesn't exist
     */
    public function setConfig($config)
    {
        if (is_array($config)) {
            $this->config = new Config($config);
        } else {
            // see if it's a file path
            if (is_file($config)) {
                $cfg = parse_ini_file($config, true);
                $this->config = new Config($cfg);
            } else {
                throw new \InvalidArgumentException(
                    'Could not load configuration file '.$config
                );
            }
        }
    }

    /**
     * Get the configuration object/settings
     *
     * @return \Expose\Config object
     */
    public function getConfig()
    {
        return $this->config;
    }

    public function setThreshold($threshold)
    {
        if (is_numeric($threshold) === false) {
            throw new \InvalidArgumentException(
                'Invalid threshold "'.$threshold.'", must be numeric'
            );
        }
        $this->threshold = $threshold;
    }

    /**
     * Get the current threshold value
     *
     * @return integer Threshold value (numeric)
     */
    public function getThreshold()
    {
        return $this->threshold;
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

    /**
     * Expose the current set of reports in the given format
     *
     * @param string $format Fromat for the export
     * @return mixed Report output (or null if the export type isn't found)
     */
    public function export($format = 'text')
    {
        $className = '\\Expose\\Export\\'.ucwords(strtolower($format));
        if (class_exists($className)) {
            $export = new $className($this->getReports());
            return $export->render();
        }
        return null;
    }
	
    public function exportGrouped($format = 'htmlGrouped')
    {
        $className = '\\Expose\\Export\\'.ucwords(strtolower($format));
        if (class_exists($className)) {
            $export = new $className( $this->getReportsGrouped() );
            return $export->render();
        }
        return null;
    }
	
    /**
     * Get Filter's detection status from given Id
     *
     * @param int $id Filter's ID
     * @return boolean
     */
	// added by Jorge Hoya
	public function isIdDetected($id)
	{
		if ( empty($id) || (int)$id == 0)
			return false;
		$id = (int) $id;
		if ( !empty($this->reports) ) {
			foreach ( $this->reports as $report)
			{
				foreach ($report->getFilterMatch() as $filter) {
					if ( (int) $filter->getId() == $id )
					{
						return true;
					}
				}
			}
		}
		return false;
	}
    /**
     * Get PHPIDS's Converter using
     *
     * @return boolean
     */
	public function getPHPIDSConverter()
	{
		return (isset($this->useConverter)) ? $this->useConverter : false;
	}
    /**
     * Set PHPIDS's Converter using
     *
	 * @param boolean $encoded
     * @return void
     */
	public function setPHPIDSConverter($encoded = true)
	{
		$this->useConverter = (bool) $encoded;
	}

    /**
	 * 
	 * @return type
	 */
    public function getStorage()
	{
		return (isset($this->storage)) ? $this->storage : false;
	}
    /**
	 * 
	 * @param \Expose\Storage $value
	 */
	public function setStorage(\Expose\Storage $value)
	{
		$this->storage = $value ;
	}
    
}


