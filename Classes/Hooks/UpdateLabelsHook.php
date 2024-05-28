<?php

namespace Sitegeist\Translatelabels\Hooks;


use Sitegeist\Translatelabels\Domain\Model\Translation;
use Sitegeist\Translatelabels\Domain\Repository\TranslationRepository;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Sitegeist\Translatelabels\Renderer\FrontendRenderer;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use Sitegeist\Translatelabels\Exception\LabelReplaceException;
use Sitegeist\Translatelabels\Utility\TranslationLabelUtility;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Extbase\Object\ObjectManager;


class UpdateLabelsHook
{
    public function processDatamap_afterDatabaseOperations($status, $table, $id, array $fieldArray, DataHandler $dataHandler)
    {
        if ($table === 'tx_translatelabels_domain_model_translation') {
            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
            $translationRepository = $objectManager->get(TranslationRepository::class);
            assert($translationRepository instanceof TranslationRepository);
            $translation = $translationRepository->findByUid($id);
            if ($translation) {
                assert($translation instanceof Translation);
                TranslationLabelUtility::flushCache($translation->getLabelkey());
            }
        }
    }
}
