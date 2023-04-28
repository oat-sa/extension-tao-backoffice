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
 * Copyright (c) 2022 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoBackOffice\migrations;

use common_report_Report as Report;
use Doctrine\DBAL\Schema\Schema;
use oat\tao\model\user\TaoRoles;
use oat\tao\model\accessControl\func\AclProxy;
use oat\tao\model\accessControl\func\AccessRule;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoBackOffice\controller\Lists;

final class Version202202111423239710_taoBackOffice extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Grant access to getListElements for ' . TaoRoles::PROPERTY_MANAGER;
    }

    public function up(Schema $schema): void
    {
        AclProxy::applyRule($this->createRule());

        $this->addReport(Report::createInfo(
            'Access to Lists::getListElements() has been granted for ' .
            TaoRoles::PROPERTY_MANAGER
        ));
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'ACLs applied by this migration should not be rolled back'
        );
    }

    private function createRule(): AccessRule
    {
        return new AccessRule(
            AccessRule::GRANT,
            TaoRoles::PROPERTY_MANAGER,
            [
                'controller' => Lists::class,
            ]
        );
    }
}
