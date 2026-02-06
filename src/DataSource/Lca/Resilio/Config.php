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

use GlpiPlugin\Carbon\Config as PluginConfig;
use GlpiPlugin\Carbon\DataSource\ConfigInterface;
use GlpiPlugin\Carbon\DataSource\RestApiClient;
use Session;

class Config implements ConfigInterface
{
    public const ENV_BASE_URL = 'GLPI_PLUGIN_CARBON_RESILIO_BASE_URL';
    private const DEFAULT_BASE_URL = 'https://db.resilio.tech/api';

    public static function getSecuredConfigs(): array
    {
        return [];
    }

        public function getConfigTemplate(): string
    {
        $hide_resilio_base_url = (getenv(self::ENV_BASE_URL) !== false);
        $commercial_url = 'https://resilio-solutions.com/';
        $twig = <<<TWIG
        {% import "components/form/fields_macros.html.twig" as fields %}

        {{ fields.largeTitle(
            __('Resilio', 'carbon'),
            'fas fa-gears'
        ) }}

        <a target="_blank" href="$commercial_url" ><i class="fa-solid fa-globe"></i>&nbsp;About</a>

TWIG;
        if (!$hide_resilio_base_url) {
            $default_base_url = self::DEFAULT_BASE_URL;
            $twig .= <<<TWIG
            {{ fields.textField(
                'resilio_base_url',
                current_config['resilio_base_url'] ?? '$default_base_url',
                __('Base URL to the Resilio instance', 'carbon')
            ) }}
TWIG;
        }

        $twig .= <<<TWIG
            {{ fields.textField(
                'resilio_email',
                current_config['resilio_email'] ?? '',
                __('Email address', 'carbon')
            ) }}

            {{ fields.passwordField(
                'resilio_password',
                current_config['resilio_password'] ?? '',
                __('Password', 'carbon')
            ) }}
TWIG;

        return $twig;
    }

    public function configUpdate(array $input): array
    {
        if (isset($input['resilio_base_url']) && strlen($input['resilio_base_url']) > 0) {
            $old_url = PluginConfig::getPluginConfigurationValue('resilio_base_url');
            if ($old_url != $input['resilio_base_url']) {
                $resilio = new Client(new RestApiClient(['http_errors' => true]), $input['resilio_base_url']);
                if ($resilio->healthCheck() === false) {
                    unset($input['resilio_base_url']);
                    Session::addMessageAfterRedirect(__('Invalid Resilio API URL', 'carbon'), false, ERROR);
                }
            }
        }
        return $input;
    }

    public static function getConfigurationValue(string $name)
    {
        if ($name === 'resilio_base_url') {
            $value = getenv(self::ENV_BASE_URL);
            if ($value !== false) {
                return $value;
            }
        }

        return PluginConfig::getPluginConfigurationValue($name);
    }
}