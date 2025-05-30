<?php
/**
 * User: ingvar.aasen
 * Date: 2025-05-28
 */

namespace Iaasen\Geonorge\LocalDb;

use Iaasen\DateTime;
use Laminas\Db\Adapter\Adapter;

abstract class AbstractTable
{
    public const TABLE_NAME = 'Must be defined';

    protected array $addressRows = [];
    protected int $cachedRows = 0;

    public function __construct(
        protected Adapter $dbAdapter
    ) {}

    public function flush() : void
    {
        if(!count($this->addressRows)) return;

        $sql = $this->getStartInsert();
        $valueRows = [];
        foreach($this->addressRows as $adresseRow) {
            foreach($adresseRow AS $key => $column) {
                if(is_null($column)) $adresseRow[$key] = 'NULL';
                else $adresseRow[$key] = '"' . $column . '"';
            }
            $valueRows[] .= '(' . implode(',', $adresseRow) . ')';
        }
        $sql .= implode(",\n", $valueRows);
        $sql .= ';';

        $this->dbAdapter->query($sql)->execute();
        $this->addressRows = [];
        $this->cachedRows = 0;
    }

    public function getStartInsert() : string
    {
        $tableName = static::TABLE_NAME;
        $columnNames = array_keys(current($this->addressRows));
        $columnsString = array_map(function ($column) { return '`' . $column . '`'; }, $columnNames);
        $columnsString = implode(',', $columnsString);
        $columnsString = '(' . $columnsString . ')';
        return 'REPLACE INTO ' . $tableName . ' ' . $columnsString . PHP_EOL . 'VALUES' . PHP_EOL;
    }

    public function deleteOldRows() : int
    {
        $tableName = static::TABLE_NAME;
        $date = new DateTime();
        $date->modify('-3 hour'); // Go back 3 hours to get before UTC in case of timezone errors
        $sql = 'DELETE FROM ' . $tableName . ' WHERE timestamp_created < "' . $date->formatMysql() . '";';
        $result = $this->dbAdapter->query($sql)->execute();
        return $result->getAffectedRows();
    }

    public function countDbAddressRows() : int {
        $tableName = static::TABLE_NAME;
        $sql = 'SELECT COUNT(*) FROM ' . $tableName . ';';
        $result = $this->dbAdapter->query($sql)->execute();
        return current($result->current());
    }

    public function truncateTable(): void
    {
        $tableName = static::TABLE_NAME;
        $sql = 'TRUNCATE TABLE ' . $tableName . ';';
        $this->dbAdapter->query($sql)->execute();
    }

}
