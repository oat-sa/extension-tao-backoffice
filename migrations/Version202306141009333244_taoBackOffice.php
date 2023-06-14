<?php

declare(strict_types=1);

namespace oat\taoBackOffice\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\IrreversibleMigration;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoBackOffice\scripts\install\MapPasswordControlFeatureFlag;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version202306141009333244_taoBackOffice extends AbstractMigration
{

    public function getDescription(): string
    {
        return 'Map Password Change to feature flag';
    }

    public function up(Schema $schema): void
    {
        $this->propagate(
            new MapPasswordControlFeatureFlag()
        )();
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration();
    }
}
