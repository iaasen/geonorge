<?php
/**
 * User: ingvar.aasen
 * Date: 2025-05-28
 */

namespace Iaasen\Geonorge\LocalDb;

use Iaasen\DateTime;
use Iaasen\Geonorge\Entity\Address;
use Iaasen\Geonorge\Entity\LocationLatLong;
use Iaasen\Geonorge\Entity\LocationUtm;
use Iaasen\Geonorge\TranscodeService;
use Symfony\Component\Console\Style\SymfonyStyle;

class AddressTable extends AbstractTable
{
    public const TABLE_NAME = 'geonorge_addresses';

    public function getAddressById(int $addressId): ?Address
    {
        $tableName = static::TABLE_NAME;
        $result = $this->dbAdapter->query("SELECT * FROM $tableName WHERE id = ?;", [$addressId]);
        if(!$result->count()) return null;
        $row = $result->current();
        $address = new Address($row);
        $address->location_utm = $utm = new LocationUtm($row['nord'], $row['øst'], LocationUtm::getUtmZoneFromEpsg($row['epsg']));
        $transcodeService = new TranscodeService();
        $address->location_lat_long = $latLong = $transcodeService->transcodeUTMtoLatLong($utm->utm_north, $utm->utm_east, $utm->utm_zone);
        $address->setRepresentasjonspunkt([
            'epsg' => 'EPSG:' . $latLong->epsg,
            'lat' => $latLong->latitude,
            'lon' => $latLong->longitude,
        ]);

        return $address;
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
            'nord' => (float)$row[17],
            'øst' => (float)$row[18],
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
            CREATE TABLE IF NOT EXISTS `{$tableName}` (
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
                `nord` float NOT NULL,
                `øst` float NOT NULL,
                `postnummer` smallint(6) UNSIGNED NOT NULL,
                `poststed` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_danish_ci NOT NULL,
                `grunnkretsnavn` varchar(255) NOT NULL,
                `soknenavn` varchar(255) NOT NULL,
                `tettstednavn` varchar(255) NOT NULL,
                `search_context` varchar(512) DEFAULT '',
                `oppdateringsdato` datetime DEFAULT NULL,
                `timestamp_created` datetime NOT NULL DEFAULT current_timestamp()
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
            
            ALTER TABLE `geonorge_addresses`
            ADD PRIMARY KEY (`id`),
            ADD KEY `fylkesnummer` (`fylkesnummer`),
            ADD KEY `adressenavn` (`adressenavn`),
            ADD KEY `postnummer` (`postnummer`),
            ADD KEY `search_context` (`search_context`);
        EOT)->execute();

    }

}
