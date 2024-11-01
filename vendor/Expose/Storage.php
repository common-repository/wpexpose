<?php

namespace Expose;

abstract class Storage
{
	protected $store = null;
    
	public abstract function initialize();
	public abstract function save($record);

    /**
     * Init the object
     * 
     * @param object $connectString Connection string to logger instance
     */
	public function __construct()
	{
		
	}

    /**
     * Set the store object instance
     *
     * @param object $store Store instance
     */
	public function setStore($store)
	{
		$this->store = $store;
	}

    /**
     * Get the current store instance
     *
     * @return object Store instance
     */
	public function getStore()
	{
		return $this->store;
	}
}
