<?php
/**
 * User: ingvar.aasen
 * Date: 2025-05-28
 */

namespace Iaasen\Geonorge\LocalDb;

use Iaasen\DateTime;
use Iaasen\Geonorge\Entity\Address;
use Iaasen\Geonorge\Entity\LocationUtm;
use Iaasen\Geonorge\TranscodeHelper;
use Symfony\Component\Console\Style\SymfonyStyle;

class AddressTable extends AbstractTable
{
    public const TABLE_NAME = 'geonorge_addresses';
    public const MATRIKKEL_PATTERN = '/^(\d{4})-(\d+)(?:\/(\d+))?(?:\/(\d+))?(?:\/(\d+))?$/';
    public const MATRIKKEL_FIELD_NAMES = [
        'kommunenummer',
        'gardsnummer',
        'bruksnummer',
        'festenummer',
        'seksjonsnummer',
        'undernummer'
    ];

    public function getAddressById(int $addressId): ?Address
    {
        $tableName = static::TABLE_NAME;
        $result = $this->dbAdapter->query("SELECT * FROM $tableName WHERE id = ?;", [$addressId]);
        if(!$result->count()) return null;
        $row = $result->current();
        return self::createAddress($row->getArrayCopy());
    }

    public function getAddressByMatrikkelString(string $matrikkel): ?Address
    {
        if(preg_match(self::MATRIKKEL_PATTERN, $matrikkel, $matches)) {
            $tableName = static::TABLE_NAME;
            $fieldNames = self::MATRIKKEL_FIELD_NAMES;

            array_shift($matches); // Skip the first element that is a copy of the full string
            $conditions = [];
            $params = [];

            foreach ($matches as $index => $value) {
                if (isset($fieldNames[$index])) {
                    $conditions[] = $fieldNames[$index] . ' = ?';
                    $params[] = $value;
                }
            }

            $sql = "SELECT * FROM $tableName";
            if (!empty($conditions)) {
                $sql .= ' WHERE ' . implode(' AND ', $conditions);
            }

            $response = $this->dbAdapter->query($sql, $params);
            if($response->count() == 1) {
                return self::createAddress($response->current()->getArrayCopy());
            }
        }

        return null;
    }

    /**
     * @param string $search
     * @return Address[]
     */
    public function fuzzySearch(string $search): array
    {
        if(!strlen($search)) return [];

        $search = self::prepareFuzzySearchFields($search);

        // Prepare where search
        $where = [];
        $parameters = [];
        if(isset($search['streetName']) && is_string($search['streetName']) && strlen($search['streetName'])) {
            $where[] = "adressenavn LIKE CONCAT(?, '%')";
            $parameters[] = $search['streetName'];
        }
        if(isset($search['postalCode']) && is_string($search['postalCode']) && strlen($search['postalCode'])) {
            $where[] = "postnummer = ?";
            $parameters[] = $search['postalCode'];
        }
        foreach($search['searchContext'] AS $context) {
            $context = str_replace(['veg', 'vei'], 've_', $context);
            $where[] = "search_context LIKE CONCAT('%', ?, '%')";
            $parameters[] = $context;
        }

        // Create the query
        $table = AddressTable::TABLE_NAME;
        $sql = <<<EOT
		SELECT *
		FROM $table
		EOT;

        $i = 0;
        foreach($where AS $row) {
            $sql .= PHP_EOL . ($i == 0 ? 'WHERE ' : 'AND ') . $row;
            $i++;
        }

        $sql .= PHP_EOL . <<<EOT
		ORDER BY
			CASE
				WHEN fylkesnummer = 50 THEN 0
				ELSE 1
			END,
			adressenavn,
			nummer,
			bokstav,
			poststed
		LIMIT 20;
		EOT;

        // Execute the query
        $request = $this->dbAdapter->query($sql);
        $result = $request->execute($parameters);
        $addresses = [];
        foreach ($result as $row) {
            $addresses[] = self::createAddress($row);
        }
        return $addresses;
    }

    public function insertRow(array $row) : void {
        if(preg_match('/\.\d+$/', $row[30])) $oppdateringsdato = DateTime::createFromFormat('d.m.Y H:i:s.u', $row[30])->format('Y-m-d H:i:s');
        else $oppdateringsdato = DateTime::createFromFormat('d.m.Y H:i:s', $row[30])->format('Y-m-d H:i:s');

        $this->addressRows[] = [
            'id' => (int)$row[32],
            'fylkesnummer' => floor((int)$row[1] / 100),
            'kommunenummer' => (int)$row[1],
            'kommunenavn' => $row[2],
            'adressetype' => $row[3],
            'adressekode' => $row[6],
            'adressenavn' => $row[7],
            'nummer' => (int)$row[8],
            'bokstav' => $row[9],
            'gardsnummer' => (int)$row[10],
            'bruksnummer' => (int)$row[11],
            'festenummer' => (int)$row[12],
            'seksjonsnummer' => null,
            'undernummer' => (int)$row[13],
            'adressetekst' => $row[14],
            'adressetekstutenadressetilleggsnavn' => $row[15],
            'adressetilleggsnavn' => $row[4],
            'epsg' => (int)$row[16],
            'nord' => $row[17],
            'øst' => $row[18],
            'postnummer' => (int)$row[19],
            'poststed' => $row[20],
            'grunnkretsnavn' => $row[22],
            'soknenavn' => $row[24],
            'tettstednavn' => $row[27],
            'search_context' => $row[7] . ' ' . $row[8] . $row[9] . ' ' . $row[20] . ' ' . $row[27] . ' ' . $row[2],
            'oppdateringsdato' => $oppdateringsdato,
        ];

        $this->cachedRows++;
        if($this->cachedRows >= 100) $this->flush();
    }

    public function insertRowLeilighetsnivaa(array $row) : void
    {
        if(preg_match('/\.\d+$/', $row[32])) $oppdateringsdato = DateTime::createFromFormat('d.m.Y H:i:s.u', $row[32])->format('Y-m-d H:i:s');
        else $oppdateringsdato = DateTime::createFromFormat('d.m.Y H:i:s', $row[32])->format('Y-m-d H:i:s');

        // Strip bruksenhetsnummer from the address texts
        if($row[15]) {
            $row[16] = substr($row[16], 0, -6); // adressetekst
            $row[17] = substr($row[17], 0, -6); // adressetekstutenadressetilleggsnavn
        }

        $this->addressRows[] = [
            'id' => (int) $row[34],
            'fylkesnummer' => floor((int) $row[0] / 100),
            'kommunenummer' => (int) $row[0],
            'kommunenavn' => $row[1],
            'adressetype' => $row[2],
            'adressekode' => $row[5],
            'adressenavn' => $row[6],
            'nummer' => (int) $row[7],
            'bokstav' => $row[8],
            'gardsnummer' => (int) $row[9],
            'bruksnummer' => (int) $row[10],
            'festenummer' => (int) $row[11],
            'seksjonsnummer' => (int) $row[12],
            'undernummer' => (int) $row[13],
            'adressetekst' => $row[16],
            'adressetekstutenadressetilleggsnavn' => $row[17],
            'adressetilleggsnavn' => $row[3],
            'epsg' => (int) $row[18],
            'nord' => (float) $row[19],
            'øst' => (float) $row[20],
            'postnummer' => (int) $row[21],
            'poststed' => $row[22],
            'grunnkretsnavn' => $row[24],
            'soknenavn' => $row[26],
            'tettstednavn' => $row[29],
            'oppdateringsdato' => $oppdateringsdato,
            'search_context' => $row[6] . ' ' . $row[7] . $row[8] . ' ' . $row[22] . ' ' . $row[29] . ' ' . $row[1],
        ];

        $this->cachedRows++;
        if($this->cachedRows >= 100) $this->flush();
    }

    public function createTable(SymfonyStyle $io, bool $force = false): void
    {
        $tableName = static::TABLE_NAME;
        $response = $this->dbAdapter->query("SHOW TABLES LIKE '{$tableName}';")->execute();
        if($response->count()) {
            $io->writeln('Table ' . $tableName . ' already exists');
            if(!$force) return;
            $io->writeln('Dropping table ' . $tableName);
            $this->dbAdapter->query("DROP TABLE IF EXISTS {$tableName};")->execute();
        }
        else $io->writeln('Table ' . $tableName . ' does not exist');

        $io->writeln('Creating table ' . $tableName);
        $this->dbAdapter->query(<<<EOT
            CREATE TABLE `{$tableName}` (
                `id` bigint(11) UNSIGNED NOT NULL,
                `fylkesnummer` tinyint(2) UNSIGNED NOT NULL,
                `kommunenummer` smallint(11) UNSIGNED NOT NULL,
                `kommunenavn` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_danish_ci NOT NULL,
                `adressetype` varchar(255) NOT NULL,
                `adressekode` mediumint(6) UNSIGNED NOT NULL,
                `adressenavn` varchar(255) NOT NULL,
                `nummer` smallint(6) NOT NULL,
                `bokstav` varchar(2) NOT NULL,
                `gardsnummer` smallint(6) UNSIGNED NOT NULL,
                `bruksnummer` smallint(6) UNSIGNED NOT NULL,
                `festenummer` smallint(6) UNSIGNED DEFAULT NULL,
                `seksjonsnummer` smallint(6) UNSIGNED DEFAULT NULL,
                `undernummer` smallint(6) UNSIGNED DEFAULT NULL,
                `adressetekst` varchar(255) NOT NULL,
                `adressetekstutenadressetilleggsnavn` varchar(255) NOT NULL,
                `adressetilleggsnavn` varchar(255) NOT NULL,
                `epsg` smallint(6) UNSIGNED NOT NULL,
                `nord` decimal(10,2) NOT NULL,
                `øst` decimal(10,2) NOT NULL,
                `postnummer` smallint(6) UNSIGNED NOT NULL,
                `poststed` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_danish_ci NOT NULL,
                `grunnkretsnavn` varchar(255) NOT NULL,
                `soknenavn` varchar(255) NOT NULL,
                `tettstednavn` varchar(255) NOT NULL,
                `search_context` varchar(512) DEFAULT '',
                `oppdateringsdato` datetime DEFAULT NULL,
                `timestamp_created` datetime NOT NULL DEFAULT current_timestamp()
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
            
            ALTER TABLE `{$tableName}`
                ADD PRIMARY KEY (`id`),
                ADD KEY `fylkesnummer` (`fylkesnummer`),
                ADD KEY `adressenavn` (`adressenavn`),
                ADD KEY `postnummer` (`postnummer`),
                ADD KEY `search_context` (`search_context`),
                ADD KEY `matrikkel` (`kommunenummer`,`gardsnummer`,`bruksnummer`),
                ADD KEY `festenummer` (`festenummer`),
                ADD KEY `seksjonsnummer` (`seksjonsnummer`),
                ADD KEY `order_by` (`fylkesnummer`,`adressenavn`,`nummer`,`bokstav`,`poststed`);
        EOT)->execute();

    }

    public static function prepareFuzzySearchFields(string $search): array
    {
        if(!strlen($search)) return [];
        $response = [];
        $searchContext = preg_split("/[, ]/", $search, -1, PREG_SPLIT_NO_EMPTY);

        if(preg_match('/\d{4}/', reset($searchContext))) {
            $response['postalCode'] = reset($searchContext);
            array_shift($searchContext);
        }
        if(count($searchContext)) {
            $response['streetName'] = array_shift($searchContext);
            $response['streetName'] = str_replace(['veg', 'vei'], 've_', $response['streetName']);
        }
        $response['searchContext'] = $searchContext;

        return $response;
    }

    private static function createAddress(array $dbRow): Address
    {
        $address = new Address($dbRow);
        $address->location_utm = $utm = new LocationUtm($dbRow['nord'], $dbRow['øst'], LocationUtm::getUtmZoneFromEpsg($dbRow['epsg']));

        // Transcode to latlong
        //$address->location_lat_long = TranscodeHelper::convertUtm33ToEtrs89UsingGeonorge($address->location_utm);
        $address->location_lat_long = TranscodeHelper::convertUtm33ToEtrs89UsingProj4php($address->location_utm);
        $address->setRepresentasjonspunkt([
            'epsg' => 'EPSG:' . $address->location_lat_long->epsg,
            'lat' => $address->location_lat_long->latitude,
            'lon' => $address->location_lat_long->longitude,
        ]);


        return $address;
    }

}
