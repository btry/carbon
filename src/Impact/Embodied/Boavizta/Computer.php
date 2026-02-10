<?php

/**
 * -------------------------------------------------------------------------
 * Carbon plugin for GLPI
 *
 * @copyright Copyright (C) 2024-2025 Teclib' and contributors.
 * @copyright Copyright (C) 2024 by the carbon plugin team.
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

namespace GlpiPlugin\Carbon\Impact\Embodied\Boavizta;

use CommonDBTM;
use Computer as GlpiComputer;
use ComputerType as GlpiComputerType;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\DataSource\Lca\Boaviztapi\ComputerModelizationAdapterTrait;
use GlpiPlugin\Cloudinventory\CloudInstance;
use Override;

class Computer extends AbstractAsset
{
    use ComputerModelizationAdapterTrait;

    protected static string $itemtype = GlpiComputer::class;

    protected string $endpoint        = 'server';

    /**
     * If the plugin CloudInventory is available, this is an object from that
     * plugin representing the cloud related data of the computer
     */
    protected ?CloudInstance $cloud_instance = null;

    /**
     * @var array Description of the asset for querying Boaviztapi
     */
    protected array $description = [];

    #[Override]
    protected function doEvaluation(): ?array
    {
        $type = $this->getType($this->item);

        $response = null;
        $this->chooseEvaluationMode($type);

        // select all impact types
        $this->endpoint .= '?' . $this->getCriteriasQueryString();

        // Query Boaviztapi
        $response = $this->query($this->description);

        $impacts = $this->client->parseResponse($response, 'embedded');
        return $impacts;
    }

    /**
     * Get the type of the computer
     * @param CommonDBTM $item
     * @return int The type of the computer
     */
    protected function getType(CommonDBTM $item): int
    {
        $computer_table = GlpiComputer::getTable();
        $computer_type_table = ComputerType::getTable();
        $glpi_computer_type_table = GlpiComputerType::getTable();
        $computer_type = new ComputerType();
        $found = $computer_type->getFromDBByRequest([
            'INNER JOIN' => [
                $glpi_computer_type_table => [
                    'FKEY' => [
                        $computer_type_table => 'computertypes_id',
                        $glpi_computer_type_table => 'id',
                    ],
                ],
                $computer_table => [
                    'FKEY' => [
                        $glpi_computer_type_table => 'id',
                        $computer_table           => 'computertypes_id',
                    ],
                ],
            ],
            'WHERE' => [
                GlpiComputer::getTableField('id') => $item->getID(),
            ],
        ]);
        if ($found === false) {
            return ComputerType::CATEGORY_UNDEFINED;
        }

        return $computer_type->fields['category'];
    }

    /**
     * Get the endpoint to use for the given type
     *
     * @return string
     */
    protected function getEndpoint(int $type): string
    {
        switch ($type) {
            case ComputerType::CATEGORY_SERVER:
            case ComputerType::CATEGORY_SERVER_RACK:
            case ComputerType::CATEGORY_SERVER_BLADE:
                return 'server';
            case ComputerType::CATEGORY_LAPTOP:
                return 'terminal/laptop';
            case ComputerType::CATEGORY_TABLET:
                return 'terminal/tablet';
            case ComputerType::CATEGORY_SMARTPHONE:
                return 'terminal/smartphone';
        }

        // ComputerType::CATEGORY_UNDEFINED
        // ComputerType::CATEGORY_DESKTOP
        return 'terminal/desktop';
    }
}
