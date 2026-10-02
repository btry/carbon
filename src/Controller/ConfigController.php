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

namespace GlpiPlugin\Carbon\Controller;

use Glpi\Controller\AbstractController;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Exception\RedirectException;
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use Html;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ConfigController extends AbstractController
{
    #[SecurityStrategy(Firewall::STRATEGY_AUTHENTICATED)]
    #[Route(
        path: 'front/config.form.php',
        name: 'redirect from plugin wrench button',
        methods: ['GET']
    )]
    public function redirect(Request $request): Response
    {
        throw new RedirectException('../../../front/config.form.php?forcetab=GlpiPlugin%5CCarbon%5CConfig$1');
    }

    #[SecurityStrategy(Firewall::STRATEGY_AUTHENTICATED)]
    #[Route(
        path: 'front/config.form.php',
        name: 'action button from data sources configuration',
        methods: ['POST']
    )]
    public function actionButton(Request $request): Response
    {
        if (!$request->request->has('datasource')) {
            throw new BadRequestHttpException('Bad request');
        }

        // $datasource contains a namespace fragment
        // example : Lca\Boavizta
        //         : CarbonIntensity\Rte
        $datasource = $request->request->get('datasource');
        $classname = "GlpiPlugin\\Carbon\\DataSource\\$datasource\\Config";
        if (!is_a($classname, 'GlpiPlugin\\Carbon\\DataSource\\ConfigInterface', true)) {
            Session::addMessageAfterRedirect(__('Connection failed.', 'carbon'));
            Html::back();
        }

        return (new $classname())->handleActionButton($request);
    }
}
