<?php defined('C5_EXECUTE') or die('Access denied.');

class LocalizerComposerHelper
{
    public function loadAutoloaders()
    {
        if (!class_exists('\Gettext\Translations', true)) {
            Loader::library('3rdparty/gettext/gettext/src/autoloader', 'localizer');
        }
        if (!class_exists('\C5TL\Languages\Language', true)) {
            Loader::library('3rdparty/gettext/languages/src/autoloader', 'localizer');
        }
        if (!class_exists('\C5TL\Parser', true)) {
            Loader::library('3rdparty/mlocati/concrete5-translation-library/src/autoloader', 'localizer');
        }
    }
}
