<?php

return [
	'laminas-cli' => [
		'commands' => [
			'geonorge:db-create' => \Iaasen\Geonorge\Cli\DbCreateCommand::class,
			'geonorge:db-import' => \Iaasen\Geonorge\Cli\DbImportCommand::class,
			'geonorge:address' => \Iaasen\Geonorge\Cli\AddressCommand::class,
			'geonorge:search' => \Iaasen\Geonorge\Cli\SearchCommand::class,
		],
	],
	'service_manager' => [
		'abstract_factories' => [
		],
		'factories' => [
		],
	],
];
