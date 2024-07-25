<?php

declare(strict_types=1);

namespace Sitegeist\Translatelabels\EventListener;

use Sitegeist\Translatelabels\Hooks\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent;

final class TypoScriptFrontendEventListener
{
    /**
     * @var TypoScriptFrontendController
     */
    private $typoScriptFrontendController;

    /**
     * @param TypoScriptFrontendController $typoScriptFrontendController
     */
    public function __construct(TypoScriptFrontendController $typoScriptFrontendController)
    {
        $this->typoScriptFrontendController = $typoScriptFrontendController;
    }

    public function __invoke(AfterCacheableContentIsGeneratedEvent $event): void
    {
        $params = [];
        $this->typoScriptFrontendController->contentPostProcAll($params, $event->getController());
    }
}
