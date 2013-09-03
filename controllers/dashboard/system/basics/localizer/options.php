<?php defined('C5_EXECUTE') or die('Access Denied.');

class DashboardSystemBasicsLocalizerOptionsController extends DashboardBaseController {

	private function getContexts() {
		return array(
			'AttributeSetName' => t('Attribute sets names'),
			'AttributeKeyName' => t('Attribute key names'),
			'AttributeTypeName' => t('Attribute type names'),
			'PermissionKeyName' => t('Permission key names'),
			'PermissionKeyDescription' => t('Permission key descriptions'),
			'PermissionAccessEntityTypeName' => t('Access entity type names'),
			'JobSetName' => t('Job set names'),
			'GroupName' => t('User group names'),
			'GroupDescription' => t('User group descriptions'),
			'GroupSetName' => t('User group set names'),
			'SelectAttributeValue' => t('Values of the select attributes')
		);
	}
	public function view() {
		$translationTables = array();
		$lh = Loader::helper('localizer', 'localizer');
		foreach($this->getContexts() as $context => $name) {
			$vMin = $lh->getMinAppVersionForContext($context);
			if(version_compare(APP_VERSION, $vMin) < 0) {
				$selected = t('Available from concrete5 %s', $vMin);
			}
			elseif($this->isPost()) {
				$posted = $this->post("enable_$context");
				$selected = empty($posted) ? false : true;
			}
			else {
				$selected = $lh->getContextEnabled($context);
			}
			$translationTables[] = array(
				'context' => $context,
				'name' => $name,
				'selected' => $selected
			);
		}
		$this->set('translationTables', $translationTables);
	}

	public function updated() {
		$this->set('message', t('The options have been saved.'));
		$this->view();
	}

	public function update() {
		if($this->isPost()) {
			if ($this->token->validate('update')) {
				try {
					$map = array();
					$someEnabled = false;
					foreach(array_keys($this->getContexts()) as $context) {
						$posted = $this->post("enable_$context");
						$enabled = empty($posted) ? false : true;
						$map[$context] = $enabled;
						if($enabled) {
							$someEnabled = true;
						}
					}
					if(!$someEnabled) {
						$this->error->add(t('Please enable at least one item class to be translated.'));
					}
					else {
						$lh = Loader::helper('localizer', 'localizer');
						foreach($map as $context => $enabled) {
							$lh->setContextEnabled($context, $enabled);
						}
						$lh->setConfigured(true);
						$this->redirect('/dashboard/system/basics/localizer/options/updated/');
					}
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

}
