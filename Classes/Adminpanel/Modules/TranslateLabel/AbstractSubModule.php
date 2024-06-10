<?php

declare(strict_types=1);

namespace Sitegeist\Translatelabels\Adminpanel\Modules\TranslateLabel;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Adminpanel\ModuleApi\ConfigurableInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ContentProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\DataProviderInterface;
use TYPO3\CMS\Adminpanel\Service\ConfigurationService;

abstract class AbstractSubModule extends \TYPO3\CMS\Adminpanel\ModuleApi\AbstractSubModule implements DataProviderInterface, ContentProviderInterface, ConfigurableInterface
{
    public function __construct()
    {
        $this->initialize();
    }

    protected function initialize(): void
    {
        $this->configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $this->context = GeneralUtility::makeInstance(Context::class);
    }

    public function __sleep(): array
    {
        return [];
    }

    public function __wakeup(): void
    {
        $this->initialize();
    }
}
