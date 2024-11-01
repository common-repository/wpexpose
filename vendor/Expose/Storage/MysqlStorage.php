<?php

namespace Expose\Storage;

class MysqlStorage extends \Expose\Storage
{
    /**
     * @var bool defines whether the MySQL connection is been initialized
     */
    private $initialized = false;

    /**
     * @var PDO pdo object of database connection
     */
    private $pdo;

    /**
     * @var PDOStatement statement to insert a new record
     */
    private $statement;

    /**
     * @var string the table to store the logs in
     */
    private $table = 'expose_intrusions';
	
    public function __construct(\PDO $pdo, $table, $bubble = true) {
        $this->pdo = $pdo;
        $this->table = $table;
        parent::__construct();
		
		
		
    }
    /**
     * Check intrusions table existance.
     */
	private function checkTableExists() {
		
		$actualFields = array();
		$rs = $this->pdo->query('SELECT * FROM `'.$this->table.'` LIMIT 0');
        for ($i = 0; $i < $rs->columnCount(); $i++) {
            $col = $rs->getColumnMeta($i);
            $actualFields[] = $col['name'];
        }
		return (count($actualFields) > 0 );
	}
    /**
     * Initializes this handler by creating the table if it not exists
     */
    public function initialize() {
		
		if (false === $this->checkTableExists()) {
			$this->pdo->exec (
				'DROP TABLE IF EXISTS `'.$this->table.'`;'.
				'CREATE TABLE IF NOT EXISTS `'.$this->table.'` ('.
				'`id` int(11) unsigned NOT null auto_increment,'.
				'`name` varchar(128) NOT null,'.
				'`description` varchar(254) NOT NULL default "",'.
				'`value` text NOT null,'.
				'`page` varchar(255) NOT null,'.
				'`ip` varchar(15) NOT null,'.
				'`ip2` varchar(15) NOT null,'.
				'`tags` varchar(128) NOT null,'.
				'`impact` int(11) unsigned NOT null,'.
				'`origin` varchar(15) NOT null,'.
				'`created` datetime NOT null,'.
				'PRIMARY KEY  (`id`)'.
				') ENGINE=MyISAM ;');

		}
		
		$this->statement = $this->pdo->prepare(
			'INSERT INTO `'.$this->table.'` (
					name,
					description,
					value,
					page,
					ip,
					ip2,
					tags,
					impact,
					origin,
					created ) VALUES (
					:name,
					:description,
					:value,
					:page,
					:ip,
					:ip2,
					:tags,
					:impact,
					:origin,
					now())'
		);
        $this->initialized = true;
    }
	/**
	 * Save data
	 * @param type array with data
	 */
    public function save($record) {
		if (!$this->initialized) {
			$this->initialize();
		}	
		$t = array(
			'name' => $record['name'],
			'description' => $record['description'],
			'value' => $record['value'],
			'page' => $record['page'],
			'ip' => $record['ip'],
			'ip2' => $record['ip2'],
			'tags' => $record['tags'],
			'impact' => $record['impact'],
			'origin' => $record['origin']
		);
        $this->statement->execute($t);    
    }
}

