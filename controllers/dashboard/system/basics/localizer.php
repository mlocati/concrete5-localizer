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
    
    private function getTranslationGroups()
    {
        Loader::helper('localizer_composer', 'localizer')->loadAutoloaders();
        $lh = Loader::helper('localizer', 'localizer');
        /* @var $lh LocalizerHelper */
        $translationsGroups = array();
        foreach($lh->getDynamicItemParsers() as $parser) {
            /* @var $parser C5TL\Parser\DynamicItem\DynamicItem */
            $translations = new \Gettext\Translations();
            $parser->parse($translations, APP_VERSION);
            $translationsHashed = array();
            foreach($translations as $translation) {
                /* @var $translation = \Gettext\Translation */
                $translationsHashed[md5($translation->getID())] = $translation;
            }
            uasort($translationsHashed, function($a, $b) {
                return strcasecmp($a->getOriginal(), $b->getOriginal());
            });
            $translationsGroups[$parser->getParsedItemNames()] = $translationsHashed;
        }
        return $translationsGroups;
    }

    public function view()
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
            $locale = $this->post('locale');
            if (!(is_string($locale) && array_key_exists($locale, $locales))) {
                $locale = $this->get('locale');
                if (!(is_string($locale) && array_key_exists($locale, $locales))) {
                    $locale = Localization::activeLocale();
                    if (!array_key_exists($locale, $locales)) {
                        $locale = array_shift(array_keys($locales));
                    }
                }
            }
            $this->set('locale', $locale);
            $curLocale = Localization::activeLocale();
            if ($curLocale != $locale) {
                Localization::changeLocale($locale);
            }
            $translationsGroups = $this->getTranslationGroups();
            foreach ($translationsGroups as $translationsGroup) {
                foreach($translationsGroup as $translation) {
                    /* @var $translation = \Gettext\Translation */
                    $sourceText = $translation->getOriginal();
                    if($translation->hasContext()) {
                        $translatedText = tc($translation->getContext(), $sourceText);
                    }
                    else {
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
            $currentGroup = $this->post('currentGroup');
            if (!(is_string($currentGroup) && array_key_exists($currentGroup, $translationsGroups))) {
                $currentGroup = $this->get('table');
                if (!(is_string($currentGroup) && array_key_exists($currentGroup, $translationsGroups))) {
                    reset($translationsGroups);
                    $currentGroup = key($translationsGroups);
                    reset($translationsGroups);
                }
            }
            $this->set('currentGroup', $currentGroup);
        }
        $this->set('locales', $locales);
    }

    public function updated()
    {
        $this->set('message', t('The translations have been saved.'));
        $this->view();
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
                    $translationFileHelper = Loader::helper('translation_file', 'localizer');
                    $translationFileHelper->setHeader('Project-Id-Version', 'Localizer');
                    $translationFileHelper->setHeader('Language', $locale);
                    $translationFileHelper->setHeader('Language-Team', $locale);
                    if (User::isLoggedIn()) {
                        $me = new User();
                    } else {
                        $me = null;
                    }
                    $translationFileHelper->setHeader('Last-Translator', $me ? $me->getUserName() : 'unknown');
                    $lh = Loader::helper('localizer', 'localizer');
                    foreach ($this->post() as $name => $translated) {
                        $translated = is_string($translated) ? trim($translated) : '';
                        if (strlen($translated) && preg_match('/^([a-zA-Z]+)_(.+)$/', $name, $match)) {
                            $context = $match[1];
                            if ($lh->getContextEnabled($context)) {
                                $id = preg_match('/^[1-9][0-9]*$/', $match[2]) ? intval($match[2]) : null;
                                if (!is_null($id)) {
                                    switch ($context) {
                                        case 'AttributeSetName':
                                            $as = AttributeSet::getByID($id);
                                            if ((!is_object($as)) || $as->isError()) {
                                                throw new Exception(t("Unable to find the attribute set with id '%s'", $id));
                                            }
                                            $translationFileHelper->add($as->getAttributeSetName(), $translated, $context);
                                            break;
                                        case 'AttributeKeyName':
                                            $ak = AttributeKey::getInstanceByID($id);
                                            if ((!is_object($ak)) || $ak->isError()) {
                                                throw new Exception(t("Unable to find the attribute key with id '%s'", $id));
                                            }
                                            $translationFileHelper->add($ak->getAttributeKeyName(), $translated, $context);
                                            break;
                                        case 'AttributeTypeName':
                                            $at = AttributeType::getByID($id);
                                            if ((!is_object($at)) || $at->isError()) {
                                                throw new Exception(t("Unable to find the attribute type with id '%s'", $id));
                                            }
                                            $translationFileHelper->add($at->getAttributeTypeName(), $translated, $context);
                                            break;
                                        case 'PermissionKeyName':
                                            $pk = PermissionKey::getByID($id);
                                            if ((!is_object($pk)) || $pk->isError()) {
                                                throw new Exception(t("Unable to find the permission key with id '%s'", $id));
                                            }
                                            $translationFileHelper->add($pk->getPermissionKeyName(), $translated, $context);
                                            break;
                                        case 'PermissionKeyDescription':
                                            $pk = PermissionKey::getByID($id);
                                            if ((!is_object($pk)) || $pk->isError()) {
                                                throw new Exception(t("Unable to find the permission key with id '%s'", $id));
                                            }
                                            $translationFileHelper->add($pk->getPermissionKeyDescription(), $translated, $context);
                                            break;
                                        case 'PermissionAccessEntityTypeName':
                                            $pt = PermissionAccessEntityType::getByID($id);
                                            if ((!is_object($pt)) || $pt->isError()) {
                                                throw new Exception(t("Unable to find the access entity type with id '%s'", $id));
                                            }
                                            $translationFileHelper->add($pt->getAccessEntityTypeName(), $translated, $context);
                                            break;
                                        case 'JobSetName':
                                            $js = JobSet::getByID($id);
                                            if ((!is_object($js)) || $js->isError()) {
                                                throw new Exception(t("Unable to find the job set with id '%s'", $id));
                                            }
                                            $translationFileHelper->add($js->getJobSetName(), $translated, $context);
                                            break;
                                        case 'GroupName':
                                            $g = Group::getByID($id);
                                            if ((!is_object($g)) || $g->isError()) {
                                                throw new Exception(t("Unable to find the users group with id '%s'", $id));
                                            }
                                            $translationFileHelper->add($g->getGroupName(), $translated, $context);
                                            break;
                                        case 'GroupDescription':
                                            $g = Group::getByID($id);
                                            if ((!is_object($g)) || $g->isError()) {
                                                throw new Exception(t("Unable to find the users group with id '%s'", $id));
                                            }
                                            $translationFileHelper->add($g->getGroupDescription(), $translated, $context);
                                            break;
                                        case 'GroupSetName':
                                            $gs = GroupSet::getByID($id);
                                            if ((!is_object($gs)) || $gs->isError()) {
                                                throw new Exception(t("Unable to find the set of users group with id '%s'", $id));
                                            }
                                            $translationFileHelper->add($gs->getGroupSetName(), $translated, $context);
                                            break;
                                        case 'SelectAttributeValue':
                                            $sav = SelectAttributeTypeOption::getByID($id);
                                            if ((!is_object($sav)) || $sav->isError()) {
                                                throw new Exception(t("Unable to find the select option value with id '%s'", $id));
                                            }
                                            $translationFileHelper->add($sav->getSelectAttributeOptionValue(false), $translated, $context);
                                            break;
                                    }
                                }
                                $text = self::fieldName2text($match[2]);
                                if (strlen($text)) {
                                    switch ($context) {
                                        case 'AreaName':
                                            $translationFileHelper->add($text, $translated, $context);
                                            break;
                                    }
                                }
                            }
                        }
                    }
                    $folder = defined('DIR_FILES_LOCALIZER') ? DIR_FILES_LOCALIZER : DIR_BASE.'/files/localizer';
                    $filename = Loader::helper('localizer', 'localizer')->getLocalizationFilename($locale);
                    if ($translationFileHelper->isEmpty()) {
                        if (is_file($filename)) {
                            @unlink($filename);
                        }
                    } else {
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
                        if (!$translationFileHelper->save($filename)) {
                            throw new Exception(t('Unable to save file %s', $filename));
                        }
                        //@chmod($filename, 0700);
                        $currentLocale = Localization::activeLocale();
                        if ($currentLocale == $locale) {
                            Localization::changeLocale('en_US');
                            Localization::changeLocale($locale);
                        }

                        Cache::flush();
                    }
                    $this->redirect('/dashboard/system/basics/localizer/updated/?locale='.rawurlencode($locale).'&table='.rawurlencode($this->post('currentTable')));
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
