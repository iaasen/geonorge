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
        $address = $this->addressTable->getAddressById($id);
        if(is_null($address)) return null;
        return current($this->populateBruksenheter([$address]));
    }

    public function search()
    {

    }

    /**
     * @param Address[] $addresses
     * @return Address[]
     */
    protected function populateBruksenheter(array $addresses): array
    {
        $addressIdMatrix = [];
        foreach($addresses AS $address) {
            $addressIdMatrix[$address->id] = $address;
        }

        $bruksenheter = $this->bruksenhetTable->getBruksenheterByAddressIds(array_keys($addressIdMatrix));
        foreach($bruksenheter AS $bruksenhet) {
            $addressIdMatrix[$bruksenhet['addressId']]->addBruksenhet($bruksenhet['bruksenhet']);
        }

        return $addresses;
    }

}
