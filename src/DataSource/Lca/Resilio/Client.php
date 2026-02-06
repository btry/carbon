<?php

/**
 * -------------------------------------------------------------------------
 * Carbon plugin for GLPI
 *
 * @copyright Copyright (C) 2024-2025 Teclib' and contributors.
 * @license   https://www.gnu.org/licenses/gpl-3.0.txt GPLv3+
 * @link      https://github.com/pluginsGLPI/carbon
 *
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of Carbon plugin for GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Carbon\DataSource\Lca\Resilio;

use GlpiPlugin\Carbon\Config as CarbonConfig;
use GlpiPlugin\Carbon\DataSource\Lca\AbstractClient;
use GlpiPlugin\Carbon\DataSource\RestApiClientInterface;

/**
 * Documentation of the API : https://db.resilio.tech/docapi
 */
class Client extends AbstractClient
{
    private RestApiClientInterface $client;

    protected static string $source_name = 'Resilio';

    public function __construct(RestApiClientInterface $client, string $url = '')
    {
        $this->client = $client;
        if (empty($url)) {
            $url = Config::getConfigurationValue('resilio_base_url');
        }
        $this->setBaseUrl($url);
    }

    public static function getSecuredConfigs(): array
    {
        return [
            'resilio_password',
        ];
    }

    public function post(string $endpoint, array $options = []): array
    {
        $options['headers'] = [
            'Accept'       => 'application/json',
        ];
        $response = $this->client->request('POST', $this->base_url . $endpoint, $options);
        if (!$response) {
            return [];
        }

        return $response;
    }

    public function get(string $endpoint, array $options = []): array
    {
        $options['headers'] = [
            'Accept'       => 'application/json',
        ];
        $response = $this->client->request('GET', $this->base_url . $endpoint, $options);
        if (!$response) {
            return [];
        }

        return is_array($response) ? $response : [$response];
    }

    /**
     * Get the health status of the server
     *
     * @return bool true if healthy, false otherwise
     */
    public function healthCheck(): bool
    {
        try {
            $this->get('healthcheck');
        } catch (\RuntimeException $e) {
            // Only success is specified in the documentation
            // Assuming here that unhealthy service returns HTTP response 300 or higher
            // Expectation that a wrong endpoint will lead to HTTTP response 300 or higher
            return false;
        }

        return true;
    }
}
