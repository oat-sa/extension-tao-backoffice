<?php

declare(strict_types=1);

namespace oat\taoBackOffice\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\IrreversibleMigration;
use oat\tao\model\menu\SectionVisibilityFilter;
use oat\tao\scripts\tools\migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 *
 * phpcs:disable Squiz.Classes.ValidClassName
 */
final class Version202403211030273244_taoBackOffice extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {

        $sectionVisibilityFilter = $this->getServiceManager()->get(SectionVisibilityFilter::SERVICE_ID);
        $featureFlagSections = $sectionVisibilityFilter
            ->getOption(SectionVisibilityFilter::OPTION_FEATURE_FLAG_SECTIONS);

        unset($featureFlagSections['settings_my_password']);
        $sectionVisibilityFilter->setOption(
            SectionVisibilityFilter::OPTION_FEATURE_FLAG_SECTIONS,
            $featureFlagSections
        );

        $this->getServiceManager()->register(SectionVisibilityFilter::SERVICE_ID, $sectionVisibilityFilter);
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration();
    }
}
