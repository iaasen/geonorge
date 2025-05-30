<?php
/**
 * User: ingvar.aasen
 * Date: 2025-05-28
 */

namespace Iaasen\Geonorge\LocalDb;

use Symfony\Component\Console\Style\SymfonyStyle;

class BruksenhetTable extends AbstractTable
{
    public const TABLE_NAME = 'geonorge_bruksenheter';

    /**
     * @param int[] $addressIds
     * @return array
     */
    public function getBruksenheterByAddressIds(array $addressIds): array
    {
        $tableName = static::TABLE_NAME;
        $query = "SELECT * FROM $tableName WHERE addressId IN (?)";
        $response = $this->dbAdapter->query($query, [implode(', ', $addressIds)]);
        $rows = [];
        foreach($response AS $row) {
            $rows[] = $row->getArrayCopy();
        }
        return $rows;
    }

    public function insertRow(array $row) : void
    {
        $this->addressRows[] = [
            'addressId' => (int) $row[34],
            'bruksenhet' => $row[15] ?: 'H0101',
        ];

        $this->cachedRows++;
        if($this->cachedRows >= 100) $this->flush();
    }

    public function createTable(SymfonyStyle $io, bool $force = false): void
    {
        $tableName = static::TABLE_NAME;
        $response = $this->dbAdapter->query("SHOW TABLES LIKE '$tableName';")->execute();
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
                `addressId` bigint(11) NOT NULL,
                `bruksenhet` varchar(5) NOT NULL,
                `timestamp_created` datetime NOT NULL DEFAULT current_timestamp()
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
            
            ALTER TABLE `geonorge_bruksenheter`
                ADD PRIMARY KEY (`addressId`,`bruksenhet`);
        EOT)->execute();
    }

}
