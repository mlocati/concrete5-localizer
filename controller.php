<?php defined('C5_EXECUTE') or die('Access denied.');

class LocalizerPackage extends Package {

	protected $pkgHandle = 'localizer';
	protected $appVersionRequired = '5.6.2';
	protected $pkgVersion = '1.0.0';

	public function getPackageName() {
		return t('Localizer');
	}

	public function getPackageDescription() {
		return t('Allow localizing special items, for instance user and file attributes.');
	}

	public function install() {
		$pkg = parent::install();
		$this->installOrUpgrade($pkg);
	}

	public function upgrade() {
		$currentVersion = $this->getPackageVersion();
		parent::upgrade();
		$this->installOrUpgrade($this, $currentVersion);
	}

	private function installOrUpgrade($pkg, $upgradeFromVersion = '') {
		$sp = Page::getByPath('/dashboard/system/basics/localizer');
		if((!is_object($sp)) || $sp->isError()) {
			$sp = SinglePage::add('/dashboard/system/basics/localizer', $pkg);
			$sp->update(array('cName' => t('Localizer'), 'cDescription' => t('Allow localizing special items, for instance user and file attributes.')));
			$ak = CollectionAttributeKey::getByHandle('meta_keywords');
			if(is_object($ak)) {
				$sp->setAttribute($ak, t('translate special items, translation, attribute names, attribute set names, attribute type names, permission names, permission descriptions, access entity type names'));
			}
		}
		$sp = Page::getByPath('/dashboard/system/basics/localizer/options');
		if((!is_object($sp)) || $sp->isError()) {
			$sp = SinglePage::add('/dashboard/system/basics/localizer/options', $pkg);
			$sp->update(array('cName' => t('Localizer options'), 'cDescription' => t('Set the Localizer options.')));
			$ak = CollectionAttributeKey::getByHandle('meta_keywords');
			if(is_object($ak)) {
				$sp->setAttribute($ak, t('localizer options, options localizer'));
			}
		}
	}

	public function on_start() {
		self::localeLoaded(defined('ACTIVE_LOCALE') ? ACTIVE_LOCALE : 'en_US', true);
		Events::extend('on_locale_load', __CLASS__, 'localeLoaded');
	}

	public static function localeLoaded($locale, $allowCache = false) {
		if($locale != 'en_US') {
			$filename = Loader::helper('localizer', 'localizer')->getLocalizationFilename($locale);
			if(is_file($filename)) {
				$translate = Localization::getInstance()->getActiveTranslateObject();
				if($translate) {
					$translate->addTranslation(array('content' => $filename, 'locale' => $locale, 'reload' => $allowCache ? false : true));
				}
			}
		}
	}

}
