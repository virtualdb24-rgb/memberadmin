<?php

declare(strict_types=1);

namespace OCA\MemberAdmin\Command;

use OCA\MemberAdmin\Service\Allowlist;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCommand extends Command {
	public function __construct(
		private Allowlist $allowlist,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('memberadmin:import')
			->setDescription('Import en masse des droits (fichier CSV : owner;group par ligne)')
			->addArgument('file', InputArgument::REQUIRED, 'Chemin du fichier CSV');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$file = (string)$input->getArgument('file');
		if (!is_file($file) || !is_readable($file)) {
			$output->writeln('<error>Fichier illisible : ' . $file . '</error>');
			return 1;
		}
		$rows = [];
		foreach (file($file, FILE_IGNORE_NEW_LINES) as $line) {
			$line = trim($line);
			if ($line === '' || str_starts_with($line, '#')) {
				continue;
			}
			$rows[] = explode(';', $line, 2);
		}
		$n = $this->allowlist->import($rows);
		$output->writeln('OK : ' . $n . ' autorisations importees');
		return 0;
	}
}
