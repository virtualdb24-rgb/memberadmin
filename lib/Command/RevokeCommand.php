<?php

declare(strict_types=1);

namespace OCA\MemberAdmin\Command;

use OCA\MemberAdmin\Service\Allowlist;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RevokeCommand extends Command {
	public function __construct(
		private Allowlist $allowlist,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('memberadmin:revoke')
			->setDescription('Retirer a un site owner la gestion d un groupe')
			->addArgument('owner', InputArgument::REQUIRED, 'Login du site owner')
			->addArgument('group', InputArgument::REQUIRED, 'Groupe a retirer');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->allowlist->revoke((string)$input->getArgument('owner'), (string)$input->getArgument('group'));
		$output->writeln('OK : droit retire');
		return 0;
	}
}
