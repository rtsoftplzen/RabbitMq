<?php

namespace Kdyby\RabbitMq;

use PhpAmqpLib\Message\AMQPMessage;



/**
 * @author Alvaro Videla <videlalvaro@gmail.com>
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @method onConsume(Consumer $self, AMQPMessage $msg)
 * @method onReject(Consumer $self, AMQPMessage $msg, $processFlag)
 * @method onAck(Consumer $self, AMQPMessage $msg)
 */
class Consumer extends BaseConsumer
{

	/**
	 * @var array
	 */
	public $onConsume = array();

	/**
	 * @var array
	 */
	public $onReject = array();

	/**
	 * @var array
	 */
	public $onAck = array();

	/**
	 * @var array
	 */
	public $onStart = array();

	/**
	 * @var array
	 */
	public $onStop = array();

	/**
	 * @var int $memoryLimit
	 */
	protected $memoryLimit;



	/**
	 * Set the memory limit
	 *
	 * @param int $memoryLimit
	 */
	public function setMemoryLimit($memoryLimit)
	{
		$this->memoryLimit = $memoryLimit;
	}



	/**
	 * Get the memory limit
	 *
	 * @return int
	 */
	public function getMemoryLimit()
	{
		return $this->memoryLimit;
	}



	/**
	 * @param int $msgAmount
	 */
	public function consume($msgAmount)
	{
		$this->target = $msgAmount;

		$this->setupConsumer();

		$this->onStart($this);
		while (count($this->getChannel()->callbacks)) {
			$this->maybeStopConsumer();
			$this->getChannel()->wait(null, false, $this->getIdleTimeout());
		}
	}



	/**
	 * Purge the queue
	 */
	public function purge()
	{
		$this->getChannel()->queue_purge($this->queueOptions['name'], true);
	}



	public function processMessage(AMQPMessage $msg)
	{
		$this->onConsume($this, $msg);
		$processFlag = call_user_func($this->callback, $msg);
		$this->handleProcessMessage($msg, $processFlag);
	}



	protected function handleProcessMessage(AMQPMessage $msg, $processFlag)
	{
		if ($processFlag === IConsumer::MSG_REJECT_REQUEUE || false === $processFlag) {
			// Reject and requeue message to RabbitMQ
			$msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], true);
			$this->onReject($this, $msg, $processFlag);

		} elseif ($processFlag === IConsumer::MSG_SINGLE_NACK_REQUEUE) {
			// NACK and requeue message to RabbitMQ
			$msg->delivery_info['channel']->basic_nack($msg->delivery_info['delivery_tag'], false, true);
			$this->onReject($this, $msg, $processFlag);

		} else {
			if ($processFlag === IConsumer::MSG_REJECT) {
				// Reject and drop
				$msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], false);
				$this->onReject($this, $msg, $processFlag);

			} else {
				// Remove message from queue only if callback return not false
				$msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
				$this->onAck($this, $msg);
			}
		}

		$this->consumed++;
		$this->maybeStopConsumer();

		if ($this->isRamAlmostOverloaded()) {
			$this->stopConsuming();
		}
	}



	/**
	 * Checks if memory in use is greater or equal than memory allowed for this process
	 *
	 * @return boolean
	 */
	protected function isRamAlmostOverloaded()
	{
		if ($this->getMemoryLimit() === NULL) {
			return FALSE;
		}

		return memory_get_usage(true) >= ($this->getMemoryLimit() * 1024 * 1024);
	}

}
