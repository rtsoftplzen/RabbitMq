<?php

declare(strict_types = 1);

namespace Kdyby\RabbitMq\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: self::NAME, description: self::DESCRIPTION)]
class RpcServerCommand extends \Symfony\Component\Console\Command\Command
{
	private const NAME = 'rabbitmq:rpc-server';

	private const DESCRIPTION = 'Starts a configured RPC server';

	/**
	 * @inject
	 * @var \Kdyby\RabbitMq\Connection
	 */
	public $connection;

	protected function configure(): void
	{
		$this
			->setName(self::NAME)
			->setDescription(self::DESCRIPTION)
			->addArgument('name', InputArgument::REQUIRED, 'Server Name')
			->addOption('messages', 'm', InputOption::VALUE_OPTIONAL, 'Messages to consume', 0)
			->addOption('debug', 'd', InputOption::VALUE_OPTIONAL, 'Debug mode', FALSE);
	}

	/**
	 * Executes the current command.
	 *
	 * @throws \InvalidArgumentException When the number of messages to consume is less than 0
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		\define('AMQP_DEBUG', (bool) $input->getOption('debug'));

		/** @var int $amount */
		$amount = $input->getOption('messages');
		if ($amount < 0) {
			throw new \InvalidArgumentException('The -m option should be null or greater than 0');
		}

		$rpcServer = $this->connection->getRpcServer($input->getArgument('name'));
		$rpcServer->start($amount);

		return 0;
	}

}
