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
use Piwik\Plugins\SegmentEditor\API as SegmentEditorAPI;
use Piwik\Settings\FieldConfig;
use Piwik\Settings\Plugin\UserSettings as BaseUserSettings;

class UserSettings extends BaseUserSettings
{
    /** @var \Piwik\Settings\Plugin\UserSetting */
    public $defaultSegment;

    /** @var array|null */
    private $cachedSegments = null;

    protected function init()
    {
        $this->defaultSegment = $this->createDefaultSegmentSetting();
    }

    public function segmentExists($definition)
    {
        return array_key_exists($definition, $this->getAvailableSegments());
    }

    private function createDefaultSegmentSetting()
    {
        return $this->makeSetting('defaultSegment', '', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
            $field->title = Piwik::translate('DefaultSegmentApplied_DefaultSegment');
            $field->description = Piwik::translate('DefaultSegmentApplied_Description');
            $field->uiControl = FieldConfig::UI_CONTROL_SINGLE_SELECT;
            $field->availableValues = $this->getAvailableSegments();
            $field->inlineHelp = Piwik::translate('DefaultSegmentApplied_InlineHelp', ['<em>', '</em>']);
            $field->validate = function ($value) {
                if ($value === '' || $value === null) {
                    return;
                }
                if (!array_key_exists($value, $this->getAvailableSegments())) {
                    throw new \Exception(Piwik::translate('General_ValidatorError'));
                }
            };
        });
    }

    private function getAvailableSegments()
    {
        if ($this->cachedSegments !== null) {
            return $this->cachedSegments;
        }

        $options = ['' => Piwik::translate('SegmentEditor_DefaultAllVisits')];

        try {
            $segments = SegmentEditorAPI::getInstance()->getAll();

            foreach ($segments as $segment) {
                $options[$segment['definition']] = $segment['name'];
            }
        } catch (\Exception $e) {
            StaticContainer::get(LoggerInterface::class)->warning('DefaultSegmentApplied: failed to fetch available segments: {message}', ['message' => $e->getMessage()]);
        }

        $this->cachedSegments = $options;
        return $this->cachedSegments;
    }
}
