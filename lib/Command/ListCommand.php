<?php

declare(strict_types=1);

namespace OCA\MemberAdmin\Command;

use OCA\MemberAdmin\Service\Allowlist;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends Command {
	public function __construct(
		private Allowlist $allowlist,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('memberadmin:list')
			->setDescription('Lister les droits de gestion des membres (par site owner ou global)')
			->addArgument('owner', InputArgument::OPTIONAL, 'Login du site owner (vide = tout)');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$only = (string)$input->getArgument('owner');
		$raw = $this->allowlist->groupsFor($only !== '' ? $only : '__none__');
		// on relit la map complete pour lister tout le monde si pas de filtre
		if ($only === '') {
			$mapJson = \OC::$server->get(\OCP\IConfig::class)->getAppValue('memberadmin', 'allowed', '{}');
			$map = json_decode((string)$mapJson, true) ?: [];
			foreach ($map as $owner => $groups) {
				$output->writeln($owner . ' => ' . implode(', ', (array)$groups));
			}
			return 0;
		}
		$output->writeln($only . ' => ' . implode(', ', $raw));
		return 0;
	}
}
