<?php
/**
 * User: ingvar.aasen
 * Date: 12.09.2023
 */

namespace Iaasen\Geonorge;

use Laminas\ModuleManager\Feature\ConfigProviderInterface;

class Module implements ConfigProviderInterface {
    public function getConfig() {
        return include __DIR__ . '/../config/module.config.php';
    }
}