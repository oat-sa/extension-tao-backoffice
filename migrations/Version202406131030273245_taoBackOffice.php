<?php

declare(strict_types=1);

namespace oat\taoBackOffice\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\IrreversibleMigration;
use oat\tao\model\featureFlag\FeatureFlagCheckerInterface;
use oat\tao\model\menu\SectionVisibilityFilter;
use oat\tao\scripts\tools\migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 *
 * phpcs:disable Squiz.Classes.ValidClassName
 */
final class Version202406131030273245_taoBackOffice extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Hiding taoBo_remotelist and taoBo_tree sections in case solar design is enabled';
    }

    public function up(Schema $schema): void
    {
        /** @var SectionVisibilityFilter $sectionVisibilityFilter */
        $sectionVisibilityFilter = $this->getServiceManager()->get(SectionVisibilityFilter::SERVICE_ID);

        $sectionVisibilityFilter->hideSectionByFeatureFlag(
            'taoBo_remotelist',
            FeatureFlagCheckerInterface::FEATURE_FLAG_SOLAR_DESIGN_ENABLED
        );
        $sectionVisibilityFilter->hideSectionByFeatureFlag(
            'taoBo_tree',
            FeatureFlagCheckerInterface::FEATURE_FLAG_SOLAR_DESIGN_ENABLED
        );

        $this->getServiceManager()->register(SectionVisibilityFilter::SERVICE_ID, $sectionVisibilityFilter);
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration();
    }
}
