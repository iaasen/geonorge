<?php
/**
 * User: ingvar.aasen
 * Date: 2025-05-28
 */

namespace Iaasen\Geonorge\LocalDb;

use Iaasen\Geonorge\Entity\Address;

class AddressService
{

    public function __construct(
        protected AddressTable    $addressTable,
        protected BruksenhetTable $bruksenhetTable,
    )
    {}

    public function getAddressById(int $id): ?Address
    {
        return $this->addressTable->getAddressById($id);
    }

    public function search()
    {

    }
}