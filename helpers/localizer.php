<?php defined('C5_EXECUTE') or die('Access Denied.');

class LocalizerHelper {

	public function getLocalizationFilename($locale) {
		$folder = defined('DIR_FILES_LOCALIZER') ? DIR_FILES_LOCALIZER : (DIR_BASE . '/files/localizer');
		return $folder . "/$locale.mo";
		
	}
}