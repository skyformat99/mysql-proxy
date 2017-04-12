<?php
/**
 * memory cache
 *
 * @author wanglibing <toowind007@gmail.com>
 * @version $Id: MMemCache.php 29 2014-09-26 05:52:01Z wanglibing $
 */
class MMemCache extends CMemCache
{
	public $useMemcached=true;
	private $_masterServers;
	private $_slaveServers;
	private $_slave;
	
	public function setMasterServers($servers)
	{
		$this->_masterServers = $this->_parseServers($servers);
		$this->setServers($this->_masterServers);
	}
	
	public function setSlaveServers($servers)
	{
		$this->_slaveServers = $this->_parseServers($servers);
	}
	
	public function getSlave()
	{
		if ($this->_slave === null) {
			if ($this->_slaveServers !== null) {
				try {
					$config = array(
						'class' => 'CMemCache',
						'servers' => $this->_slaveServers,
						'keyPrefix' => $this->keyPrefix
					);
					$this->_slave = Yii::createComponent($config);
					$this->_slave->init();
				} catch (Exception $e) {
					$this->_slave = false;
				}
			} else {
				$this->_slave = false;
			}
		}
		
		return $this->_slave;
	}
	
	public function delete($id)
	{
		$this->getSlave() && $this->getSlave()->delete($id);
		return parent::delete($id);
	}
	
	private function _parseServers($servers)
	{
		$_servers = array();
		foreach (explode(' ', $servers) as $server) {
			if (!empty($server)) {
				$arr = explode(':', trim($server));
				$_servers[] = array(
					'host' => $arr[0],
					'port' => $arr[1]
				);
			}
		}
		
		return $_servers;
	}
	
	public function fget($id)
	{
		if(($data=$this->getValue($this->generateUniqueKey($id)))!==false)
		{
			if(!is_array($data))
				return false;
			if(!($data[1] instanceof ICacheDependency) || !$data[1]->getHasChanged())
			{
				Yii::trace('Serving "'.$id.'" from cache','system.caching.'.get_class($this));
				return $data[0];
			}
		}
		return false;
	}
}