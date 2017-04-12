<?php
/**
 * MDbConnection(Database Connection Manager) class is a manager of database connections.
 *
 * for the purpose of database read/write splitting.
 * It override the createCommand method,
 * detect the sql statement to decide which connection will be used.
 * Default it use the master connection.
 *
 * @author wanglibing <toowind007@gmail.com>
 * @version $Id: MDbConnection.php 6521 2015-01-15 08:12:47Z wanglibing $
 *
 */
class MDbConnection extends CDbConnection
{
	/**
	 * @var array $slaves.Slave database connection(Read) config array.
	 * The array value's format is the same as CDbConnection.
	 * <code>
	 * 'components'=>array(
	 * 	'db'=>array(
	 * 		'class' => 'MDbConnection',
	 * 		'connectionString'=>'mysql://<master>',
	 * 		'slaves'=>array(
	 * 			array('connectionString'=>'mysql://<slave01>'),
	 * 			array('connectionString'=>'mysql://<slave02>'),
	 * 		)
	 * 	)
	 * )
	 * </code>
	 */
	public $slaves = array();
	
	/**
	 * Whether enable the slave database connection.
	 * Defaut is true.Set this property to false for the purpose of only use the master database.
	 * 
	 * @var bool $enableSlave 
	 */
	public $enableSlave = true;
	
	/**
	 * @override
	 * @var bool $autoConnect Whether connect while init
	 */
	//public $autoConnect=false;
	

	/**
	 * @var CDbConnection
	 */
	private $_slave;

	/**
	 * Creates a CDbCommand object for excuting sql statement.
	 * It will detect the sql statement's behavior.
	 * While the sql is a simple read operation.
	 * It will use a slave database connection to contruct a CDbCommand object.
	 * Default it use current connection(master database).
	 * 
	 * @override 
	 * @param string $sql
	 * @return CDbCommand
	 */
	public function createCommand($query = null)
	{
		if ($this->enableSlave && !$this->getCurrentTransaction() && self::isReadOperation($query)) {
			return $this->getSlave()->createCommand($query);
		} else {
			return parent::createCommand($query);
		}
	}

	/**
	 * Construct a slave connection CDbConnection for read operation.
	 * 
	 * @return CDbConnection
	 */
	public function getSlave()
	{
		if (!isset($this->_slave)) {
			foreach ($this->slaves as $slaveConfig) {
				if (!isset($slaveConfig['class']))
					$slaveConfig['class'] = 'CDbConnection';
				try {
					if ($slave = Yii::createComponent($slaveConfig)) {
						Yii::app()->setComponent('dbslave', $slave);
						$this->_slave = $slave;
						break;
					}
				} catch (Exception $e) {
					Yii::log('Create slave database connection failed!', 'warn');
					continue;
				}
			}
			if (!$this->_slave) {
				$this->_slave = clone $this;
				$this->_slave->enableSlave = false;
			}
		}
		return $this->_slave;
	}

	/**
	 * Detect whether the sql statement is just a simple read operation.
	 * Read Operation means this sql will not change any thing ang aspect of the database.
	 * Such as SELECT,DECRIBE,SHOW etc.
	 * On the other hand:UPDATE,INSERT,DELETE is write operation.
	 * 
	 * @return bool
	 */
	public function isReadOperation($sql)
	{
		return !!preg_match('/^\s*(SELECT|SHOW|DESCRIBE|PRAGMA)/i', $sql);
	}
}