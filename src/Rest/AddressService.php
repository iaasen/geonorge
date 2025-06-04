<?php
/**
 * User: ingvar.aasen
 * Date: 13.05.2019
 * Time: 15:45
 */

namespace Iaasen\Geonorge\Rest;


use Iaasen\Geonorge\Entity\LocationLatLong;
use Iaasen\Geonorge\TranscodeHelper;
use Iaasen\Geonorge\Entity\Address;
use GuzzleHttp\Exception\ConnectException;
use Nteb\ApiEntities\Exception\GatewayTimeoutException;

/**
 * Documentation:
 * - https://ws.geonorge.no/adresser/v1/ - Finn adresser med søk eller matrikkel
 * - https://ws.geonorge.no/eiendom/v1/ - Finn eiendom nær en gitt koordinat
 * - https://ws.geonorge.no/transformering/v1/ - Konverter koordinater
 * - https://www.kartverket.no/Kart/transformere-koordinater/
 */
class AddressService
{
	const BASE_URL = 'adresser/v1/';
	protected Transport $transport;

	public function __construct()
	{
		$this->transport = new Transport(['base_url' => Transport::BASE_URL . self::BASE_URL]);
	}


	/**
	 * @param string $search
	 * @return Address[]
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function search(string $search) : array {
		$url = 'sok';
		$query = [
			'sok' => $search,
		];
		try {
			$data = json_decode($this->transport->sendGet($url, $query));
		}
		catch (ConnectException $e) {
			if(
				isset($e->getHandlerContext()['error']) &&
				strpos($e->getHandlerContext()['error'], 'Failed to connect') !== null) {
				throw new GatewayTimeoutException($e->getHandlerContext()['error']);
			}
			else throw $e;
		}
		$addresses = [];
		foreach($data->adresser AS $row) {
			$addresses[] = $this->createObject($row);
		}
		return $addresses;
	}


	public function getById(string $id) : ?Address {
		$url = 'sok';
		$fields = explode('-', base64_decode($id));
		if(count($fields) != 6) return null;

		$fieldNames = [
			0 => 'kommunenummer',
			1 => 'gardsnummer',
			2 => 'bruksnummer',
			3 => 'festenummer',
			4 => 'nummer',
			5 => 'bokstav',
		];
		$query = [];
		foreach($fieldNames AS $key => $fieldName) {
			if(isset($fields[$key]) && strlen($fields[$key])) $query[$fieldName] = $fields[$key];
		}

		try {
			$data = json_decode($this->transport->sendGet($url, $query));
		}
		catch (ConnectException $e) {
			if(
				isset($e->getHandlerContext()['error']) &&
				strpos($e->getHandlerContext()['error'], 'Failed to connect') !== null) {
				throw new GatewayTimeoutException($e->getHandlerContext()['error']);
			}
			else throw $e;
		}
		if(count($data->adresser)) return $this->createObject(array_pop($data->adresser));
		else return null;
	}


	/**
	 * @param string $matrikkel
	 * @return Address[]
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function getByMatrikkel(string $matrikkel) : array {
		preg_match('/(\d+)-(\d+)\/(\d+)(\/(\d+))?/', $matrikkel, $matches);
		$query = [
			'kommunenummer' => $matches[1],
			'gardsnummer' => $matches[2],
			'bruksnummer' => $matches[3],
		];
		if(isset($matches[5])) $query['festenummer'] = $matches[5];

		$url = 'sok';
		$data = json_decode($this->transport->sendGet($url, $query));

		$addresses = [];
		foreach($data->adresser AS $row) {
			$addresses[] = $this->createObject($row);
		}
		return $addresses;
	}

	protected function createObject($data) : Address {
		$address = new Address($data);
        $address->location_lat_long = new LocationLatLong(round($address->representasjonspunkt->lat, 6), round($address->representasjonspunkt->lon, 6));
        //$address->location_utm = TranscodeHelper::convertEtrs89ToUtm33UsingGeonorge($address->location_lat_long);
        $address->location_utm = TranscodeHelper::convertErts89ToUtm33UsingProj4php($address->location_lat_long);
        if(!count($address->bruksenhetsnummer)) $address->bruksenhetsnummer = ['H0101'];
		return $address;
	}


    /**
     * Used for testing
     */
    public function getTransport(): Transport
    {
        return $this->transport;
    }

}