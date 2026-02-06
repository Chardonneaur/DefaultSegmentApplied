<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DefaultSegmentApplied;

use Piwik\Container\StaticContainer;
use Piwik\Log\LoggerInterface;
use Piwik\Piwik;
use Piwik\Plugin;

class DefaultSegmentApplied extends Plugin
{
    public function registerEvents()
    {
        return [
            'Template.jsGlobalVariables'             => 'addJsGlobalVariables',
            'AssetManager.getJavaScriptFiles'        => 'getJsFiles',
            'Translate.getClientSideTranslationKeys' => 'getClientSideTranslationKeys',
        ];
    }

    public function addJsGlobalVariables(&$out)
    {
        if (Piwik::isUserIsAnonymous()) {
            return;
        }

        try {
            $userSettings = new UserSettings();
            $defaultSegment = $userSettings->defaultSegment->getValue();

            $encoded = $defaultSegment ? json_encode($defaultSegment, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) : false;
            if ($encoded !== false && $userSettings->segmentExists($defaultSegment)) {
                $out .= "    piwik.defaultSegment = $encoded;\n";
            } else {
                $out .= "    piwik.defaultSegment = '';\n";
            }
        } catch (\Exception $e) {
            StaticContainer::get(LoggerInterface::class)->warning('DefaultSegmentApplied: failed to load default segment: {message}', ['message' => $e->getMessage()]);
            $out .= "    piwik.defaultSegment = '';\n";
        }
    }

    public function getJsFiles(&$jsFiles)
    {
        $jsFiles[] = 'plugins/DefaultSegmentApplied/javascripts/defaultSegmentApplier.js';
    }

    public function getClientSideTranslationKeys(&$translationKeys)
    {
        $translationKeys[] = 'DefaultSegmentApplied_DefaultSegment';
        $translationKeys[] = 'DefaultSegmentApplied_NoDefaultSegment';
    }
}
