<?php
/**
 * User: ingvar.aasen
 * Date: 29.04.2024
 */

namespace Iaasen\Geonorge\Cli;

use Iaasen\Geonorge\LocalDb\AddressImportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'geonorge:db-import', description: 'Importer addresses to the local db')]
class DbImportCommand extends AbstractCommand {

	public function __construct(
		protected AddressImportService $addressImportService,
	) {
		parent::__construct();
	}

	public function execute(InputInterface $input, OutputInterface $output) : int {
		$this->io->title('Importer adresser');
		$success = $this->addressImportService->importAddresses($this->io, $input->getArgument('list'));
		if($success) {
			$this->io->success('Success');
			return 0;
		}
		$this->io->error('Failed');
		return 1;
	}

    public function configure() : void {
        $this->addArgument('list', InputArgument::OPTIONAL, 'Choices: norge, norge_leilighetsnivaa, trondelag, trondelag_leilighetsnivaa', 'norge_leilighetsnivaa');
    }

}
