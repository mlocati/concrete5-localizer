<?php defined('C5_EXECUTE') or die('Access Denied.');

class DashboardSystemBasicsLocalizerController extends DashboardBaseController {

	protected static function getLocales() {
		$locales = array();
		$languages = Localization::getAvailableInterfaceLanguages();
		Zend_Locale_Data::setCache(Cache::getLibrary());
		foreach($languages as $language) {
			$locale = new Zend_Locale($language);
			$locales[$language] = Zend_Locale::getTranslation($locale->getLanguage(), 'language');
			$localeRegion = $locale->getRegion();
			if($localeRegion !== false) {
				$localeRegionName = $locale->getTranslation($locale->getRegion(), 'country');
				if($localeRegionName !== false) {
					$locales[$language] .= ' (' . $localeRegionName . ')';
				}
			}
		}
		asort($locales);
		return $locales;
	}

	public function view() {
		$lh = Loader::helper('localizer', 'localizer');
		if(!$lh->getConfigured()) {
			$this->redirect('/dashboard/system/basics/localizer/options/');
		}
		Loader::library('3rdparty/Zend/Locale');
		Loader::library('3rdparty/Zend/Locale/Data');
		$locales = self::getLocales();
		if(count($locales)) {
			$locale = $this->post('locale');
			if(!(is_string($locale) && array_key_exists($locale, $locales))) {
				$locale = $this->get('locale');
				if(!(is_string($locale) && array_key_exists($locale, $locales))) {
					$locale = Localization::activeLocale();
					if(!array_key_exists($locale, $locales)) {
						$locale = array_shift(array_keys($locales));
					}
				}
			}
			$attributeCategories = array();
			$attributeSetNames = array();
			$attributeKeyNames = array();
			$selectAttributeValues = null;
			if($lh->getContextEnabled('SelectAttributeValue')) {
				$selectAttributeValues = array();
			}
			foreach(AttributeKeyCategory::getList() as $akc) {
				$akcHandle = $akc->getAttributeKeyCategoryHandle();
				switch($akcHandle) {
					case 'collection':
						$akcName = t('Page attributes');
						break;
					case 'user':
						$akcName = t('User attributes');
						break;
					case 'file':
						$akcName = t('File attributes');
						break;
					default:
						$akcName = Object::uncamelcase($akcHandle);
						break;
				}
				$attributeCategories[$akcHandle] = $akcName;
				foreach($akc->getAttributeSets() as $as) {
					$attributeSetNames[$akcHandle][$as->getAttributeSetID()]['source'] = $as->getAttributeSetName();
				}
				if(isset($attributeSetNames[$akcHandle])) {
					uasort($attributeSetNames[$akcHandle], array(__CLASS__, 'sortBy_source'));
				}
				foreach(AttributeKey::getList($akcHandle) as $ak) {
					$attributeKeyNames[$akcHandle][$ak->getAttributeKeyID()]['source'] = $ak->getAttributeKeyName();
					if(is_array($selectAttributeValues)) {
						if($ak->getAttributeType()->getAttributeTypeHandle() == 'select') {
							foreach($ak->getController()->getOptions() as $option) {
								$selectAttributeValues[$akcHandle][$option->getSelectAttributeOptionID()]['source'] = $option->getSelectAttributeOptionValue(false);
							}
						}
					}
				}
				if(isset($attributeKeyNames[$akcHandle])) {
					uasort($attributeKeyNames[$akcHandle], array(__CLASS__, 'sortBy_source'));
				}
			}
			asort($attributeCategories);
			$attributeTypeNames = array();
			foreach(AttributeType::getList() as $at) {
				$attributeTypeNames[$at->getAttributeTypeID()]['source'] = $at->getAttributeTypeName();
			}
			uasort($attributeTypeNames, array(__CLASS__, 'sortBy_source'));
			$permissionCategories = array();
			$permissionKeyNames = array();
			$permissionKeyDescriptions = array();
			foreach(PermissionKeyCategory::getList() as $pkc) {
				$pkcHandle = $pkc->getPermissionKeyCategoryHandle();
				switch($pkcHandle) {
					case 'page':
						$pkcName = t('Page');
						break;
					case 'single_page':
						$pkcName = t('Single page');
						break;
					case 'stack':
						$pkcName = t('Stack');
						break;
					case 'composer_page':
						$pkcName = t('Composer page');
						break;
					case 'user':
						$pkcName = t('User');
						break;
					case 'file_set':
						$pkcName = t('File set');
						break;
					case 'file':
						$pkcName = t('File');
						break;
					case 'area':
						$pkcName = t('Area');
						break;
					case 'block_type':
						$pkcName = t('Block type');
						break;
					case 'block':
						$pkcName = t('Block');
						break;
					case 'admin':
						$pkcName = t('Administration');
						break;
					case 'sitemap':
						$pkcName = t('Site map');
						break;
					case 'marketplace_newsflow':
						$pkcName = t('MarketPlace newsflow');
						break;
					case 'basic_workflow':
						$pkcName = t('Basic workflow');
						break;
					default:
						$pkcName = Object::uncamelcase($akcHandle);
						break;
				}
				$permissionCategories[$pkcHandle] = $pkcName;
				foreach(PermissionKey::getList($pkcHandle) as $pk) {
					$permissionKeyNames[$pkcHandle][$pk->getPermissionKeyID()]['source'] = $pk->getPermissionKeyName();
					$permissionKeyDescriptions[$pkcHandle][$pk->getPermissionKeyID()]['source'] = $pk->getPermissionKeyDescription();
				}
				if(isset($permissionKeyNames[$pkcHandle])) {
					uasort($permissionKeyNames[$pkcHandle], array(__CLASS__, 'sortBy_source'));
				}
				if(isset($permissionKeyDescriptions[$pkcHandle])) {
					uasort($permissionKeyDescriptions[$pkcHandle], array(__CLASS__, 'sortBy_source'));
				}
			}
			asort($permissionCategories);
			$permissionAccessEntityTypeNames = array();
			foreach(PermissionAccessEntityType::getList() as $accessEntityType) {
				$permissionAccessEntityTypeNames[$accessEntityType->getAccessEntityTypeID()]['source'] = $accessEntityType->getAccessEntityTypeName();
			}
			uasort($permissionAccessEntityTypeNames, array(__CLASS__, 'sortBy_source'));
			$jobSetNames = array();
			foreach(JobSet::getList() as $jobSet) {
				$jobSetNames[$jobSet->getJobSetID()]['source'] = $jobSet->getJobSetName();
			}
			uasort($jobSetNames, array(__CLASS__, 'sortBy_source'));
			if($lh->getContextEnabled('GroupName') || $lh->getContextEnabled('GroupDescription')) {
				$gl = new GroupList(null, false, true);
				$groupNames = array();
				$groupDescriptions = array();
				foreach($gl->getGroupList() as $g) {
					$groupNames[$g->getGroupID()]['source'] = $g->getGroupName();
					$groupDescriptions[$g->getGroupID()]['source'] = $g->getGroupDescription();
				}
			}
			if($lh->getContextEnabled('GroupSetName')) {
				$groupSetNames = array();
				foreach(GroupSet::getList() as $gs) {
					$groupSetNames[$gs->getGroupSetID()]['source'] = $gs->getGroupSetName();
				}
			}
			$curLocale = Localization::activeLocale();
			if($curLocale != $locale) {
				Localization::changeLocale($locale);
			}
			foreach(array_keys($attributeSetNames) as $akcHandle) {
				foreach(array_keys($attributeSetNames[$akcHandle]) as $asID) {
					$localized = isset($_POST["AttributeSetName_$asID"]) ? $this->post("AttributeSetName_$asID") : tc('AttributeSetName', $attributeSetNames[$akcHandle][$asID]['source']);
					$attributeSetNames[$akcHandle][$asID]['translated'] = ($localized == $attributeSetNames[$akcHandle][$asID]['source']) ? '' : $localized;
				}
			}
			foreach(array_keys($attributeKeyNames) as $akcHandle) {
				foreach(array_keys($attributeKeyNames[$akcHandle]) as $akID) {
					$localized = isset($_POST["AttributeKeyName_$akID"]) ? $this->post("AttributeKeyName_$akID") : tc('AttributeKeyName', $attributeKeyNames[$akcHandle][$akID]['source']);
					$attributeKeyNames[$akcHandle][$akID]['translated'] = ($localized == $attributeKeyNames[$akcHandle][$akID]['source']) ? '' : $localized;
				}
			}
			foreach(array_keys($attributeTypeNames) as $atID) {
				$localized = isset($_POST["AttributeTypeName_$atID"]) ? $this->post("AttributeTypeName_$atID") : tc('AttributeTypeName', $attributeTypeNames[$atID]['source']);
				$attributeTypeNames[$atID]['translated'] = ($localized == $attributeTypeNames[$atID]['source']) ? '' : $localized;
			}
			foreach(array_keys($permissionKeyNames) as $pkcHandle) {
				foreach(array_keys($permissionKeyNames[$pkcHandle]) as $pkID) {
					$localized = isset($_POST["PermissionKeyName_$pkID"]) ? $this->post("PermissionKeyName_$pkID") : tc('PermissionKeyName', $permissionKeyNames[$pkcHandle][$pkID]['source']);
					$permissionKeyNames[$pkcHandle][$pkID]['translated'] = ($localized == $permissionKeyNames[$pkcHandle][$pkID]['source']) ? '' : $localized;
				}
			}
			foreach(array_keys($permissionKeyDescriptions) as $pkcHandle) {
				foreach(array_keys($permissionKeyDescriptions[$pkcHandle]) as $pkID) {
					$localized = isset($_POST["PermissionKeyDescription_$pkID"]) ? $this->post("PermissionKeyDescription_$pkID") : tc('PermissionKeyDescription', $permissionKeyDescriptions[$pkcHandle][$pkID]['source']);
					$permissionKeyDescriptions[$pkcHandle][$pkID]['translated'] = ($localized == $permissionKeyDescriptions[$pkcHandle][$pkID]['source']) ? '' : $localized;
				}
			}
			foreach(array_keys($permissionAccessEntityTypeNames) as $accessEntityTypeID) {
				$localized = isset($_POST["PermissionAccessEntityTypeName_$accessEntityTypeID"]) ? $this->post("PermissionAccessEntityTypeName_$accessEntityTypeID") : tc('PermissionAccessEntityTypeName', $permissionAccessEntityTypeNames[$accessEntityTypeID]['source']);
				$permissionAccessEntityTypeNames[$accessEntityTypeID]['translated'] = ($localized == $permissionAccessEntityTypeNames[$accessEntityTypeID]['source']) ? '' : $localized;
			}
			foreach(array_keys($jobSetNames) as $jobSetID) {
				$localized = isset($_POST["JobSetName_$jobSetID"]) ? $this->post("JobSetName__$jobSetID") : tc('JobSetName', $jobSetNames[$jobSetID]['source']);
				$jobSetNames[$jobSetID]['translated'] = ($localized == $jobSetNames[$jobSetID]['source']) ? '' : $localized;
			}
			if($lh->getContextEnabled('GroupName')) {
				foreach(array_keys($groupNames) as $gID) {
					$localized = isset($_POST["GroupName_$gID"]) ? $this->post("GroupName_$gID") : tc('GroupName', $groupNames[$gID]['source']);
					$groupNames[$gID]['translated'] = ($localized == $groupNames[$gID]['source']) ? '' : $localized;
				}
			}
			if($lh->getContextEnabled('GroupDescription')) {
				foreach(array_keys($groupDescriptions) as $gID) {
					$localized = isset($_POST["GroupDescription_$gID"]) ? $this->post("GroupDescription_$gID") : tc('GroupDescription', $groupDescriptions[$gID]['source']);
					$groupDescriptions[$gID]['translated'] = ($localized == $groupDescriptions[$gID]['source']) ? '' : $localized;
				}
			}
			if($lh->getContextEnabled('GroupSetName')) {
				foreach(array_keys($groupSetNames) as $gsID) {
					$localized = isset($_POST["GroupSetName_$gsID"]) ? $this->post("GroupSetName_$gsID") : tc('GroupSetName', $groupSetNames[$gsID]['source']);
					$groupSetNames[$gsID]['translated'] = ($localized == $groupSetNames[$gsID]['source']) ? '' : $localized;
				}
			}
			if(is_array($selectAttributeValues)) {
				foreach(array_keys($selectAttributeValues) as $akcHandle) {
					foreach(array_keys($selectAttributeValues[$akcHandle]) as $savID) {
						$localized = isset($_POST["SelectAttributeValue_$savID"]) ? $this->post("SelectAttributeValue_$savID") : tc('SelectAttributeValue', $selectAttributeValues[$akcHandle][$savID]['source']);
						$selectAttributeValues[$akcHandle][$savID]['translated'] = ($localized == $selectAttributeValues[$akcHandle][$savID]['source']) ? '' : $localized;
					}
				}
			}
			if($curLocale != $locale) {
				Localization::changeLocale($curLocale);
			}
			$this->set('locale', $locale);
			$translationTables = array();
			if($lh->getContextEnabled('AttributeSetName')) {
				$translationTables['AttributeSetName'] = array('name' => t('Attribute sets names'), 'rows' => self::buildTranslationRows('AttributeSetName', $attributeSetNames, $attributeCategories));
			}
			if($lh->getContextEnabled('AttributeKeyName')) {
				$translationTables['AttributeKeyName'] = array('name' => t('Attribute key names'), 'rows' => self::buildTranslationRows('AttributeKeyName', $attributeKeyNames, $attributeCategories));
			}
			if($lh->getContextEnabled('AttributeTypeName')) {
				$translationTables['AttributeTypeName'] = array('name' => t('Attribute type names'), 'rows' => self::buildTranslationRows('AttributeTypeName', $attributeTypeNames));
			}
			if($lh->getContextEnabled('PermissionKeyName')) {
				$translationTables['PermissionKeyName'] = array('name' => t('Permission key names'), 'rows' => self::buildTranslationRows('PermissionKeyName', $permissionKeyNames, $permissionCategories));
			}
			if($lh->getContextEnabled('PermissionKeyDescription')) {
				$translationTables['PermissionKeyDescription'] = array('name' => t('Permission key descriptions'), 'rows' => self::buildTranslationRows('PermissionKeyDescription', $permissionKeyDescriptions, $permissionCategories));
			}
			if($lh->getContextEnabled('PermissionAccessEntityTypeName')) {
				$translationTables['PermissionAccessEntityTypeName'] = array('name' => t('Access entity type names'), 'rows' => self::buildTranslationRows('PermissionAccessEntityTypeName', $permissionAccessEntityTypeNames));
			}
			if($lh->getContextEnabled('JobSetName')) {
				$translationTables['JobSetName'] = array('name' => t('Job set names'), 'rows' => self::buildTranslationRows('JobSetName', $jobSetNames));
			}
			if($lh->getContextEnabled('GroupName')) {
				$translationTables['GroupName'] = array('name' => t('User group names'), 'rows' => self::buildTranslationRows('GroupName', $groupNames));
			}
			if($lh->getContextEnabled('GroupDescription')) {
				$translationTables['GroupDescription'] = array('name' => t('User group descriptions'), 'rows' => self::buildTranslationRows('GroupDescription', $groupDescriptions));
			}
			if($lh->getContextEnabled('GroupSetName')) {
				$translationTables['GroupSetName'] = array('name' => t('User group set names'), 'rows' => self::buildTranslationRows('GroupSetName', $groupSetNames));
			}
			if($lh->getContextEnabled('SelectAttributeValue')) {
				$translationTables['SelectAttributeValue'] = array('name' => t('Values of the select attributes'), 'rows' => self::buildTranslationRows('SelectAttributeValue', $selectAttributeValues, $attributeCategories));
			}
			$this->set('translationTables', $translationTables);
			$currentTable = $this->post('currentTable');
			if(!(is_string($currentTable) && array_key_exists($currentTable, $translationTables))) {
				$currentTable = $this->get('table');
				if(!(is_string($currentTable) && array_key_exists($currentTable, $translationTables))) {
					reset($translationTables);
					$currentTable = key($translationTables);
					reset($translationTables);
				}
			}
			$this->set('currentTable', $currentTable);
		}
		$this->set('locales', $locales);
	}

	public function updated() {
		$this->set('message', t('The translations have been saved.'));
		$this->view();
	}

	public function update() {
		if($this->isPost()) {
			if ($this->token->validate('update_translations')) {
				try {
					$locales = self::getLocales();
					$locale = $this->post('locale');
					if(!array_key_exists($locale, $locales)) {
						throw new Exception(t("Invalid locale identifier: '%s'", $locale));
					}
					$translationFileHelper = Loader::helper('translation_file', 'localizer');
					$translationFileHelper->setHeader('Language', $locale);
					$lh = Loader::helper('localizer', 'localizer');
					foreach($this->post() as $name => $translated) {
						$translated = is_string($translated) ? trim($translated) : '';
						if(strlen($translated) && preg_match('/^(.+)_([1-9][0-9]*)$/', $name, $match)) {
							$context = $match[1];
							if($lh->getContextEnabled($context)) {
								$id = intval($match[2]);
								switch($context) {
									case 'AttributeSetName':
										$as = AttributeSet::getByID($id);
										if((!is_object($as)) || $as->isError()) {
											throw new Exception(t("Unable to find the attribute set with id '%s'", $id));
										}
										$translationFileHelper->add($as->getAttributeSetName(), $translated, $context);
										break;
									case 'AttributeKeyName':
										$ak = AttributeKey::getInstanceByID($id);
										if((!is_object($ak)) || $ak->isError()) {
											throw new Exception(t("Unable to find the attribute key with id '%s'", $id));
										}
										$translationFileHelper->add($ak->getAttributeKeyName(), $translated, $context);
										break;
									case 'AttributeTypeName':
										$at = AttributeType::getByID($id);
										if((!is_object($at)) || $at->isError()) {
											throw new Exception(t("Unable to find the attribute type with id '%s'", $id));
										}
										$translationFileHelper->add($at->getAttributeTypeName(), $translated, $context);
										break;
									case 'PermissionKeyName':
										$pk = PermissionKey::getByID($id);
										if((!is_object($pk)) || $pk->isError()) {
											throw new Exception(t("Unable to find the permission key with id '%s'", $id));
										}
										$translationFileHelper->add($pk->getPermissionKeyName(), $translated, $context);
										break;
									case 'PermissionKeyDescription':
										$pk = PermissionKey::getByID($id);
										if((!is_object($pk)) || $pk->isError()) {
											throw new Exception(t("Unable to find the permission key with id '%s'", $id));
										}
										$translationFileHelper->add($pk->getPermissionKeyDescription(), $translated, $context);
										break;
									case 'PermissionAccessEntityTypeName':
										$pt = PermissionAccessEntityType::getByID($id);
										if((!is_object($pt)) || $pt->isError()) {
											throw new Exception(t("Unable to find the access entity type with id '%s'", $id));
										}
										$translationFileHelper->add($pt->getAccessEntityTypeName(), $translated, $context);
										break;
									case 'JobSetName':
										$js = JobSet::getByID($id);
										if((!is_object($js)) || $js->isError()) {
											throw new Exception(t("Unable to find the job set with id '%s'", $id));
										}
										$translationFileHelper->add($js->getJobSetName(), $translated, $context);
										break;
									case 'GroupName':
										$g = Group::getByID($id);
										if((!is_object($g)) || $g->isError()) {
											throw new Exception(t("Unable to find the users group with id '%s'", $id));
										}
										$translationFileHelper->add($g->getGroupName(), $translated, $context);
										break;
									case 'GroupDescription':
										$g = Group::getByID($id);
										if((!is_object($g)) || $g->isError()) {
											throw new Exception(t("Unable to find the users group with id '%s'", $id));
										}
										$translationFileHelper->add($g->getGroupDescription(), $translated, $context);
										break;
									case 'GroupSetName':
										$gs = GroupSet::getByID($id);
										if((!is_object($gs)) || $gs->isError()) {
											throw new Exception(t("Unable to find the set of users group with id '%s'", $id));
										}
										$translationFileHelper->add($gs->getGroupSetName(), $translated, $context);
										break;
									case 'SelectAttributeValue':
										$sav = SelectAttributeTypeOption::getByID($id);
										if((!is_object($sav)) || $sav->isError()) {
											throw new Exception(t("Unable to find the select option value with id '%s'", $id));
										}
										$translationFileHelper->add($sav->getSelectAttributeOptionValue(false), $translated, $context);
										break;
								}
							}
						}
					}
					$folder = defined('DIR_FILES_LOCALIZER') ? DIR_FILES_LOCALIZER : DIR_BASE . '/files/localizer';
					$filename = Loader::helper('localizer', 'localizer')->getLocalizationFilename($locale);
					if($translationFileHelper->isEmpty()) {
						if(is_file($filename)) {
							@unlink($filename);
						}
					}
					else {
						$foldername = dirname($filename);
						if(!is_dir($foldername)) {
							@mkdir($foldername, DIRECTORY_PERMISSIONS_MODE, true);
							if(!is_dir($foldername)) {
								throw new Exception(t('Unable to create folder %s', $foldername));
							}
						}
						$emptyIndex = $foldername . '/index.html';
						if(!is_file($emptyIndex)) {
							Loader::helper('file')->append($emptyIndex, '');
						}
						if(!$translationFileHelper->save($filename)) {
							throw new Exception(t('Unable to save file %s', $filename));
						}
						//@chmod($filename, 0700);
						$currentLocale = Localization::activeLocale();
						if($currentLocale == $locale) {
							Localization::changeLocale('en_US');
							Localization::changeLocale($locale);
						}
					}
					$this->redirect('/dashboard/system/basics/localizer/updated/?locale=' . rawurlencode($locale) . '&table=' . rawurlencode($this->post('currentTable')));
				}
				catch(Exception $x) {
					$this->error->add($x->getMessage());
				}
			}
			else {
				$this->error->add($this->token->getErrorMessage());
			}
		}
		$this->view();
	}

	protected static function buildTranslationRows($context, $items, $groupedBy = false) {
		$rows = '';
		if($groupedBy) {
			foreach($groupedBy as $gbID => $gbName) {
				if(array_key_exists($gbID, $items)) {
					$subRows = self::buildTranslationRows($context, $items[$gbID]);
					if(strlen($subRows)) {
						$rows .= '<tr><th colspan="2">' . h($gbName) . '</th></tr>';
						$rows .= $subRows;
					}
				}
			}
		}
		else {
			foreach($items as $id => $translation) {
				if(strlen($translation['source'])) {
					$rows .= '<tr><td style="width:33%">' . h($translation['source']) . '</td><td><input type="text" name="' . h($context . '_' . $id) . '" style="width:100%" placeholder="' . h(t('Same as English (US)')) . '" value="' . h($translation['translated']) . '" />';
				}
			}
		}
		return $rows;
	}

	protected static function sortBy_source($a, $b) {
		return strcasecmp($a['source'], $b['source']);
	}

}
