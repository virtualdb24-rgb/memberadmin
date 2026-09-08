<?php

declare(strict_types=1);

namespace OCA\MemberAdmin\Command;

use OCA\MemberAdmin\Service\Allowlist;
use OCP\IGroupManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * occ memberadmin:role <role-group> <add|remove|list> [<prefix>...]
 *
 * Membres d'un groupe-role gerent automatiquement tous les groupes dont le nom
 * commence par le(s) prefixe(s) configure(s).
 */
class RoleCommand extends Command {
	public function __construct(
		private Allowlist $allowlist,
		private IGroupManager $groupManager,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('memberadmin:role')
			->setDescription('Regle automatique : les membres du groupe-role gerent les groupes d un prefixe')
			->addArgument('role', InputArgument::REQUIRED, 'Groupe-role (ex: sigblow_gestion)')
			->addArgument('action', InputArgument::REQUIRED, 'add | remove | list')
			->addArgument('prefixes', InputArgument::IS_ARRAY, 'Prefixes de groupes (ex: axion_group)');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$role = (string)$input->getArgument('role');
		$action = (string)$input->getArgument('action');
		$prefixes = array_values(array_filter(array_map('trim', $input->getArgument('prefixes')), static fn ($p) => $p !== ''));

		if ($this->groupManager->get($role) === null) {
			$output->writeln('<error>Groupe-role introuvable : ' . $role . '</error>');
			return 1;
		}

		switch ($action) {
			case 'add':
				if ($prefixes === []) {
					$output->writeln('<error>Indiquez au moins un prefixe</error>');
					return 1;
				}
				foreach ($prefixes as $p) {
					$this->allowlist->roleAddPrefix($role, $p);
					$output->writeln('OK : les membres de ' . $role . ' gerent les groupes "' . $p . '*"');
				}
				break;
			case 'remove':
				foreach ($prefixes as $p) {
					$this->allowlist->roleRemovePrefix($role, $p);
					$output->writeln('OK : prefixe ' . $p . ' retire du role ' . $role);
				}
				break;
			case 'list':
				$prefixes = $this->allowlist->roleList($role);
				$output->writeln($role . ' => ' . implode(', ', $prefixes));
				break;
			default:
				$output->writeln('<error>Action inconnue (add|remove|list)</error>');
				return 1;
		}
		return 0;
	}
}
