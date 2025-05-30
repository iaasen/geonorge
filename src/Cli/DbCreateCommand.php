<?php
/**
 * User: ingvar.aasen
 * Date: 2025-05-30
 */

namespace Iaasen\Geonorge\Cli;

use Iaasen\Geonorge\LocalDb\AddressTable;
use Iaasen\Geonorge\LocalDb\BruksenhetTable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'geonorge:db-create', description: 'Create the local db tables in the default db adapter')]
class DbCreateCommand extends AbstractCommand
{

    public function __construct(
        protected AddressTable $addressTable,
        protected BruksenhetTable $bruksenhetTable,
    ) {
        parent::__construct('geonorge:db-create');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Create local db tables');
        $force = $input->getOption('force');

        if($force) $this->io->writeln('Force mode enabled. The tables will be recreated if they already exists');

        $this->addressTable->createTable($this->io, $force);
        $this->bruksenhetTable->createTable($this->io, $force);

        return 0;
    }

    public function configure() : void {
        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Recreate the tables if they already exists',
        );
    }

}