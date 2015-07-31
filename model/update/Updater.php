<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA;
 *
 *
 */
namespace oat\taoBackOffice\model\update;

use oat\tao\scripts\update\OntologyUpdater;
/**
 * Class TreeService
 */
class Updater extends \common_ext_ExtensionUpdater {

    public function update($initialVersion) {
        
        $currentVersion = $initialVersion;
        
        // ontology
        if ($currentVersion == '0.8') {
            OntologyUpdater::syncModels();
            $currentVersion = '0.9';
        }
        
        return $currentVersion;
    }
}