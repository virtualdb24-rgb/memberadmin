<?php

declare(strict_types=1);

namespace OCA\MemberAdmin\Command;

use OCA\MemberAdmin\Service\Allowlist;
use OCP\IGroupManager;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GrantCommand extends Command {
	public function __construct(
		private Allowlist $allowlist,
		private IGroupManager $groupManager,
		private IUserManager $userManager,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('memberadmin:grant')
			->setDescription('Autoriser un site owner a gerer un groupe (ajout/retrait de membres)')
			->addArgument('owner', InputArgument::REQUIRED, 'Login du site owner')
			->addArgument('group', InputArgument::REQUIRED, 'Groupe qu il pourra gerer');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$owner = (string)$input->getArgument('owner');
		$gid = (string)$input->getArgument('group');

		if ($this->userManager->get($owner) === null) {
			$output->writeln('<error>Utilisateur introuvable : ' . $owner . '</error>');
			return 1;
		}
		if ($this->groupManager->get($gid) === null) {
			$output->writeln('<error>Groupe introuvable : ' . $gid . '</error>');
			return 1;
		}
		if ($gid === 'admin') {
			$output->writeln('<error>Le groupe admin ne peut pas etre delegue</error>');
			return 1;
		}
		$this->allowlist->grant($owner, $gid);
		$output->writeln('OK : ' . $owner . ' gere desormais le groupe ' . $gid);
		return 0;
	}
}
