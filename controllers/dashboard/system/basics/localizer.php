<?php defined('C5_EXECUTE') or die('Access Denied.');

class DashboardSystemBasicsLocalizerController extends DashboardBaseController
{

    protected static function getLocales()
    {
        $locales = array();
        $languages = Localization::getAvailableInterfaceLanguages();
        Zend_Locale_Data::setCache(Cache::getLibrary());
        foreach ($languages as $language) {
            $locale = new Zend_Locale($language);
            $locales[$language] = Zend_Locale::getTranslation($locale->getLanguage(), 'language');
            $localeRegion = $locale->getRegion();
            if ($localeRegion !== false) {
                $localeRegionName = $locale->getTranslation($locale->getRegion(), 'country');
                if ($localeRegionName !== false) {
                    $locales[$language] .= ' ('.$localeRegionName.')';
                }
            }
        }
        asort($locales);

        return $locales;
    }

    private function getTranslations($grouped)
    {
        $lh = Loader::helper('localizer', 'localizer');
        /* @var $lh LocalizerHelper */
        $lh->loadAutoloaders();
        $parsers = $lh->getDynamicItemParsers();
        $result = array();
        if ($grouped) {
            foreach ($lh->getDynamicItemParsers() as $parser) {
                /* @var $parser C5TL\Parser\DynamicItem\DynamicItem */
                $translations = new \Gettext\Translations();
                $parser->parse($translations, APP_VERSION);
                $translationsHashed = array();
                foreach ($translations as $translation) {
                    /* @var $translation \Gettext\Translation */
                    $translationsHashed[md5($translation->getId())] = $translation;
                }
                uasort($translationsHashed, function ($a, $b) {
                    return strcasecmp($a->getOriginal(), $b->getOriginal());
                });
                $result[$parser->getParsedItemNames()] = $translationsHashed;
            }
        } else {
            $result = new \Gettext\Translations();
            foreach ($lh->getDynamicItemParsers() as $parser) {
                $parser->parse($result, APP_VERSION);
            }
        }

        return $result;
    }

    public function view($locale = '', $groupIndex = 0)
    {
        $lh = Loader::helper('localizer', 'localizer');
        /* @var $lh LocalizerHelper */
        if (!$lh->getConfigured()) {
            $this->redirect('/dashboard/system/basics/localizer/options/');
        }
        Loader::library('3rdparty/Zend/Locale');
        Loader::library('3rdparty/Zend/Locale/Data');
        $locales = self::getLocales();
        if (count($locales)) {
            if (!(is_string($locale) && array_key_exists($locale, $locales))) {
                $locale = Localization::activeLocale();
                if (!array_key_exists($locale, $locales)) {
                    $locale = array_shift(array_keys($locales));
                }
            }
            $this->set('locale', $locale);
            $curLocale = Localization::activeLocale();
            if ($curLocale != $locale) {
                Localization::changeLocale($locale);
            }
            $translationsGroups = $this->getTranslations(true);
            foreach ($translationsGroups as $translationsGroup) {
                foreach ($translationsGroup as $translation) {
                    /* @var $translation = \Gettext\Translation */
                    $sourceText = $translation->getOriginal();
                    if ($translation->hasContext()) {
                        $translatedText = tc($translation->getContext(), $sourceText);
                    } else {
                        $translatedText = t($sourceText);
                    }
                    if (is_string($translatedText) && ($translatedText !== '') && ($translatedText !== $sourceText)) {
                        $translation->setTranslation($translatedText);
                    }
                }
            }
            if ($curLocale != $locale) {
                Localization::changeLocale($curLocale);
            }
            $this->set('translationsGroups', $translationsGroups);
            if (!is_int($groupIndex)) {
                $groupIndex = (is_string($groupIndex) && is_numeric($groupIndex)) ? intval($groupIndex) : 0;
            }
            $keys = array_keys($translationsGroups);
            if (($groupIndex < 0) || ($groupIndex >= count($keys))) {
                $groupIndex = 0;
            }
            $this->set('currentGroup', $keys[$groupIndex]);
        }
        $this->set('locales', $locales);
    }

    public function updated($locale, $groupIndex = 0)
    {
        $this->set('message', t('The translations have been saved.'));
        $this->view($locale, $groupIndex);
    }

    public function update()
    {
        if ($this->isPost()) {
            if ($this->token->validate('update_translations')) {
                try {
                    $locales = self::getLocales();
                    $locale = $this->post('locale');
                    if (!array_key_exists($locale, $locales)) {
                        throw new Exception(t("Invalid locale identifier: '%s'", $locale));
                    }
                    $translations = $this->getTranslations(false);
                    $translations->setLanguage($locale);
                    $translations->setHeader('Project-Id-Version', 'Localizer');
                    if (User::isLoggedIn()) {
                        $me = new User();
                    } else {
                        $me = null;
                    }
                    $translations->setHeader('Last-Translator', $me ? $me->getUserName() : 'unknown');
                    $somethingTranslated = false;
                    foreach ($translations as $translation) {
                        /* @var $translation \Gettext\Translation */
                        $translated = $this->post('translation_'.md5($translation->getId()));
                        if (is_string($translated) && ($translated !== '')) {
                            $translation->setTranslation($translated);
                            $somethingTranslated = true;
                        }
                    }
                    $lh = Loader::helper('localizer', 'localizer')
                    /* @var $lh LocalizerHelper */;
                    $filename = $lh->getLocalizationFilename($locale);
                    if ($somethingTranslated) {
                        $foldername = dirname($filename);
                        if (!is_dir($foldername)) {
                            @mkdir($foldername, DIRECTORY_PERMISSIONS_MODE, true);
                            if (!is_dir($foldername)) {
                                throw new Exception(t('Unable to create folder %s', $foldername));
                            }
                        }
                        $emptyIndex = $foldername.'/index.html';
                        if (!is_file($emptyIndex)) {
                            Loader::helper('file')->append($emptyIndex, '');
                        }
                        $translations->toMoFile($filename);
                    } else {
                        if (is_file($filename)) {
                            @unlink($filename);
                        }
                    }
                    $currentLocale = Localization::activeLocale();
                    if ($currentLocale == $locale) {
                        Localization::changeLocale('en_US');
                        Localization::changeLocale($locale);
                    }
                    Cache::flush();
                    $this->redirect('/dashboard/system/basics/localizer', 'updated', $locale, @intval($this->post('currentGroupIndex')));
                } catch (Exception $x) {
                    $this->error->add($x->getMessage());
                }
            } else {
                $this->error->add($this->token->getErrorMessage());
            }
        }
        $this->view();
    }

    protected static function buildTranslationRows($context, $items, $groupedBy = false)
    {
        $rows = '';
        if ($groupedBy) {
            foreach ($groupedBy as $gbID => $gbName) {
                if (array_key_exists($gbID, $items)) {
                    $subRows = self::buildTranslationRows($context, $items[$gbID]);
                    if (strlen($subRows)) {
                        $rows .= '<tr><th colspan="2">'.h($gbName).'</th></tr>';
                        $rows .= $subRows;
                    }
                }
            }
        } else {
            foreach ($items as $id => $translation) {
                if (strlen($translation['source'])) {
                    $rows .= '<tr><td style="width:33%">'.h($translation['source']).'</td><td><input type="text" name="'.h($context.'_'.$id).'" style="width:100%" placeholder="'.h(t('Same as English (US)')).'" value="'.h($translation['translated']).'" />';
                }
            }
        }

        return $rows;
    }

    protected static function sortBy_source($a, $b)
    {
        return strcasecmp($a['source'], $b['source']);
    }

    private static function text2fieldName($text)
    {
        return str_replace('=', '_', base64_encode($text));
    }
    private static function fieldName2text($fieldName)
    {
        $decoded = @base64_decode(str_replace('_', '=', $fieldName));

        return is_string($decoded) ? $decoded : '';
    }
}
