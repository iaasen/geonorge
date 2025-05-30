<?php
/**
 * User: ingvar.aasen
 * Date: 2025-05-30
 */

namespace Iaasen\Geonorge\Cli;

use Iaasen\Debug\Timer;
use Iaasen\Geonorge\LocalDb\AddressService as DbAddressService;
use Iaasen\Geonorge\Rest\AddressService as RestAddressService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'geonorge:address', description: 'Lookup addresses')]
class AddressCommand extends AbstractCommand
{
    public function __construct(
        protected DbAddressService   $dbAddressService,
        protected RestAddressService $restAddressService,
    ) {
        parent::__construct('geonorge:address');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        Timer::setStart();
        $this->io->title('Geonorge address lookup');
        $datastore = $input->getOption('datastore');

        $addressId = $input->getArgument('addressId');
        if($datastore == 'rest') $address = $this->restAddressService->getById($addressId);
        else $address = $this->dbAddressService->getAddressById($addressId);
        dump($address);
        return 0;
    }

    public function configure() : void {
        $this->addArgument('addressId', InputArgument::REQUIRED, 'Address Id');
        $this->addOption(
            'datastore',
            'd',
            InputArgument::OPTIONAL,
            '"rest" = Geonorge REST API, "db" = Local database',
            'db'
        );
    }

}