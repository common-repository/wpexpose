<?php
namespace Expose;

/**
 * Describes a database interface
 */
interface DatabaseInterface
{
    public function getDbName();
    public function setDbName($name);
    public function getDbCollection();
    public function setDbCollection($collection);
    public function getConnectString();
    public function setConnectString($string);
    public function save($data);
    
}