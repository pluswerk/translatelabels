<?php

namespace Sitegeist\Translatelabels\ViewHelpers;

/**
 *
 * This file is part of the "translatelabels" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 */

use TYPO3\CMS\Extbase\Mvc\RequestInterface as ExtbaseRequestInterface;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;
use Sitegeist\Translatelabels\Utility\TranslationLabelUtility;

/**
 * Translate a key from locallang. The files are loaded from the folder
 * "Resources/Private/Language/".
 *
 * == Examples ==
 *
 * <code title="Translate key">
 * <f:translate key="key1" />
 * </code>
 * <output>
 * value of key "key1" in the current website language
 * </output>
 *
 * <code title="Keep HTML tags">
 * <f:format.raw><f:translate key="htmlKey" /></f:format.raw>
 * </code>
 * <output>
 * value of key "htmlKey" in the current website language, no htmlspecialchars applied
 * </output>
 *
 * <code title="Translate key from custom locallang file">
 * <f:translate key="LLL:EXT:myext/Resources/Private/Language/locallang.xlf:key1" />
 * </code>
 * <output>
 * value of key "key1" in the current website language
 * </output>
 *
 * <code title="Inline notation with arguments and default value">
 * {f:translate(key: 'argumentsKey', arguments: {0: 'dog', 1: 'fox'}, default: 'default value')}
 * </code>
 * <output>
 * value of key "argumentsKey" in the current website language
 * with "%1" and "%2" are replaced by "dog" and "fox" (printf)
 * if the key is not found, the output is "default value"
 * </output>
 *
 * <code title="Inline notation with extension name">
 * {f:translate(key: 'someKey', extensionName: 'SomeExtensionName')}
 * </code>
 * <output>
 * value of key "someKey" in the current website language
 * the locallang file of extension "some_extension_name" will be used
 * </output>
 *
 * <code title="Translate id as in TYPO3 Flow">
 * <f:translate id="key1" />
 * </code>
 * <output>
 * value of id "key1" in the current website language
 * </output>
 */
class TranslateViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Output is escaped already. We must not escape children, to avoid double encoding.
     *
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * Initialize arguments.
     *
     * @throws Exception
     */
    public function initializeArguments()
    {
        $this->registerArgument('key', 'string', 'Translation Key');
        $this->registerArgument('id', 'string', 'Translation Key compatible to TYPO3 Flow');
        $this->registerArgument('default', 'string', 'If the given locallang key could not be found, this value is used. If this argument is not set, child nodes will be used to render the default');
        $this->registerArgument('arguments', 'array', 'Arguments to be replaced in the resulting string');
        $this->registerArgument('extensionName', 'string', 'UpperCamelCased extension key (for example BlogExample)');
        $this->registerArgument('languageKey', 'string', 'Language key ("dk" for example) or "default" to use for this translation. If this argument is empty, we use the current language');
        $this->registerArgument('alternativeLanguageKeys', 'array', 'Alternative language keys if no translation does exist');
    }

    /**
     * Return array element by key.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return mixed|null|string
     * @throws \TYPO3\CMS\Backend\Exception
     * @throws AspectNotFoundException
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $key = $arguments['key'];
        $id = $arguments['id'];
        $default = $arguments['default'];
        $extensionName = $arguments['extensionName'];
        $translateArguments = $arguments['arguments'];

        // Wrapper including a compatibility layer for TYPO3 Flow Translation
        if ($id === null) {
            $id = $key;
        }

        $id = (string)$id;
        if ($id === '') {
            throw new \TYPO3Fluid\Fluid\Core\Exception('An argument "key" or "id" has to be provided', 1351584844);
        }

        $request = null;
        if ($renderingContext instanceof RenderingContext) {
            $request = $renderingContext->getRequest();
        }

        if (empty($extensionName)) {
            if ($request instanceof ExtbaseRequestInterface) {
                $extensionName = $request->getControllerExtensionName();
            } elseif (str_starts_with($id, 'LLL:EXT:')) {
                $extensionName = substr($id, 8, strpos($id, '/', 8) - 8);
            } elseif ($default) {
                if (!empty($translateArguments)) {
                    return vsprintf($default, $translateArguments);
                }
                return $default;
            } else {
                // Throw exception in case neither an extension key nor a extbase request
                // are given, since the "short key" shouldn't be considered as a label.
                throw new \RuntimeException(
                    'ViewHelper f:translate in non-extbase context needs attribute "extensionName" to resolve'
                    . ' key="' . $id . '" without path. Either set attribute "extensionName" together with the short'
                    . ' key "yourKey" to result in a lookup "LLL:EXT:your_extension/Resources/Private/Language/locallang.xlf:yourKey",'
                    . ' or (better) use a full LLL reference like key="LLL:EXT:your_extension/Resources/Private/Language/yourFile.xlf:yourKey".'
                    . ' Alternatively, you can also define a default value.',
                    1639828178
                );
            }
        }

        try {
            $value = static::translate($id, $extensionName, $translateArguments, $arguments['languageKey'], $arguments['alternativeLanguageKeys']);
        } catch (\InvalidArgumentException $e) {
            $value = null;
        }
        if ($value === null) {
            $value = $default ?? $renderChildrenClosure();
            if (!empty($translateArguments)) {
                $value = vsprintf($value, $translateArguments);
            }
        }

        if ($request instanceof ServerRequestInterface
            && ApplicationType::fromRequest($request)->isFrontend()) {
            $id = TranslationLabelUtility::getExtendLabelKeyWithLanguageFilePath($id, $extensionName);
            $value = TranslationLabelUtility::readLabelFromDatabase($id, $value);
            if (\is_array($translateArguments) && $value !== null) {
                $value = sprintf($value, ...array_values($translateArguments)) ?: sprintf('Error: could not translate key "%s" with value "%s" and %d argument(s)!', $key, $value, count($translateArguments));
            }
            if (TranslationLabelUtility::isFrontendWithLoggedInBEUser($id, $extensionName)) {
                $value = TranslationLabelUtility::renderTranslationWithExtendedInformation($id, $value, $extensionName, $translateArguments, $arguments['languageKey'], $arguments['alternativeLanguageKeys']);
            }
        }
        return $value;
    }

    /**
     * Wrapper call to static LocalizationUtility
     */
    protected static function translate(string $id, ?string $extensionName = null, array $arguments = null, Locale|string $languageKey = null, array $alternativeLanguageKeys = null): ?string
    {
        return LocalizationUtility::translate($id, $extensionName, $arguments, $languageKey, $alternativeLanguageKeys);
    }
}
