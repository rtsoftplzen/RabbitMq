<?php

declare(strict_types = 1);

namespace Kdyby\RabbitMq\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: self::NAME, description: self::DESCRIPTION)]
class ConsumerCommand extends \Kdyby\RabbitMq\Command\BaseConsumerCommand
{
    private const NAME = 'rabbitmq:consumer';

    private const DESCRIPTION = 'Starts a configured consumer';

	protected function configure(): void
	{
		parent::configure();

		$this->setName(self::NAME);
		$this->setDescription(self::DESCRIPTION);
	}

}
