<?php
/**
 * User: ingvar.aasen
 * Date: 2025-05-30
 */

namespace Iaasen\Geonorge\Cli;

use Iaasen\Debug\Timer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'geonorge:search', description: 'Search addresses')]
class SearchCommand extends AbstractCommand
{
    public function __construct(

    )
    {
        parent::__construct('geonorge:search');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Geonorge address search');
        Timer::setStart();

        $search = $input->getArgument('search');
        dd($search);

        return 0;
    }

    public function configure() : void {
        $this->addArgument('search', InputArgument::REQUIRED + InputArgument::IS_ARRAY, 'Search terms');
        $this->addOption(
            'datastore',
            'd',
            InputArgument::OPTIONAL,
            '"rest" = Geonorge REST API, "db" = Local database',
            'db'
        );
    }

}