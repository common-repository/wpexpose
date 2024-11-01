<?php
if ( ! defined( 'ABSPATH' ) || !defined('WPEX_folder')) exit;

class Expose_IPBan {
	
	private $identificador;
	private $ip4;
	private $ip6;
	private $created;
	private $_wpdb;
	private $table;

	public function __construct($wpdb, $table, $id = null, $ip4 = null, $ip6 = null, $created = null) {
		
		$this->SetDB($wpdb);
		$this->SetTable($table);

		if(!is_null($id)) {
			$this->SetID($id);
		}
			
		if(!is_null($ip4))
			$this->SetIp4($ip4);
		if(!is_null($ip6))
			$this->SetIp6($ip6);
		if(!is_null($created))
			$this->SetCreated($created);
	}
	


	protected function SetDB ($db) {
		if(!is_null($db))
			$this->_wpdb = $db;
	}
	protected function SetTable ($table) {
		if(!is_null($table) && !empty($table))
			$this->table = addslashes($table); // sure??
	}
	protected function SetID ($id) {
		if((int) $id > 0)
			$this->identificador = (int) $id;
	}
	public function SetIp4 ($ip4) {
		if( filter_var($ip4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ) {
			$this->ip4 = $ip4;
		}
			
	}	
	public function SetIp6 ($ip6) {
		if( filter_var($ip6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ) {
			$this->ip6 = $ip6;
		}
	}	
	public function SetCreated ($created) {
		if(!empty($created)) {
			$d = @new DateTime($created);
			if(!is_null($d)) {
				$this->created = $d->format('y-m-j h:i:s');
			}
			else {
				$this->created = date('y-m-j h:i:s');	
			}		
		}
		else {
			$this->created = date('y-m-j h:i:s');
		}
			
	}	
	
	public function GetDB () { 
		return (!is_null($this->_wpdb) ? $this->_wpdb : null); 
		}
	public function GetTable () { 
		return (!empty($this->table) ? $this->table : '');
		}
	public function GetID() { 
		return $this->identificador;
		}
	public function GetIp4() { 
		return (!empty($this->ip4) ? $this->ip4 : '');
		}
	public function GetIp6() { 
		return (!empty($this->ip6) ? $this->ip6 : '');
		}
	public function GetCreated() { 
		return $this->created;
		}
	

	public function GetById($id) {
		if((int) $id > 0 ) {
			$data = $this->GetDB()->get_row(
				$this->GetDB()->prepare(
					'SELECT * FROM ' . $this->GetTable(). ' WHERE id = %d LIMIT 1',
					(int) $id
					)
				);
			if ( null !== $data) {
				$this->SetID($data->id);
				$this->SetIp4($data->ip4);
				$this->SetIp6($data->ip6);
				$this->SetCreated($data->created);
			}
		}
	}

	public function GetByIp4($ip) {
		if(!empty($ip)) {
			$data = $this->GetDB()->get_row(
				$this->GetDB()->prepare(
					'SELECT * FROM ' . $this->GetTable(). ' WHERE ip4 = %s LIMIT 1',
					$ip
					)
				);
			if ( null !== $data) {
				$this->SetID($data->id);
				$this->SetIp4($data->ip4);
				$this->SetIp6($data->ip6);
				$this->SetCreated($data->created);
			}
		}
	}

	public function Save() {
		if ( !empty($this->ip4) || !empty($this->ip6) ) {
			$this->GetDB()->insert( $this->GetTable(), 
				array(
					'ip4' =>  $this->GetIp4(),
					'ip6' =>  $this->GetIp6(),
					'created' => $this->GetCreated()
				), 
				array('%s', '%s', '%s')
			);
		}	
	}

	public function Delete()
	{
		$this->GetDB()->delete( $this->GetTable(), array('id' => $this->identificador), array('%d'));
	}
}