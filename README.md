# Adresse REST-API

REST API
--------
Client implementation of Geonorge address API's

Official documentation:
* https://kartkatalog.geonorge.no/metadata/adresse-rest-api/44eeffdc-6069-4000-a49b-2d6bfc59ac61
* https://ws.geonorge.no/adresser/v1/

Local DB import
---------------
A second solution using a local database import has been added.
The download files are found here:
* https://nedlasting.geonorge.no/geonorge/Basisdata/MatrikkelenAdresse/CSV
* https://nedlasting.geonorge.no/geonorge/Basisdata/MatrikkelenAdresseLeilighetsniva/CSV

Database table named geonorge_addresses and genorge_bruksenheter must be created in the default database.
Use the console command "geonorge:db-create"
See the following functions for the table definitions:
- \Iaasen\Geonorge\LocalDb\AddressTable::createTable()
- \Iaasen\Geonorge\LocalDb\BruksenhetTable::createTable()
