<?php defined('C5_EXECUTE') or die('Access Denied.');

class DashboardSystemBasicsLocalizerOptionsController extends DashboardBaseController
{

    public function view()
    {
        $parsers = array();
        $lh = Loader::helper('localizer', 'localizer');
        /* @var $lh LocalizerHelper */
        foreach ($lh->getDynamicItemParsers(false) as $parserHandle => $parser) {
            if ($this->isPost()) {
                $posted = $this->post("enable_$parserHandle");
                $selected = empty($posted) ? false : true;
            } else {
                $selected = $lh->getParserEnabled($parserHandle);
            }
            $parsers[] = array(
                'handle' => $parserHandle,
                'name' => $parser->getParsedItemNames(),
                'selected' => $selected,
            );
        }
        uasort($parsers, function ($a, $b) {
            return strcasecmp($a['handle'], $b['handle']);
        });
        $this->set('parsers', $parsers);
    }

    public function updated()
    {
        $this->set('message', t('The options have been saved.'));
        $this->view();
    }

    public function update()
    {
        if ($this->isPost()) {
            if ($this->token->validate('update')) {
                try {
                    $lh = Loader::helper('localizer', 'localizer');
                    /* @var $lh LocalizerHelper */
                    $map = array();
                    $someEnabled = false;
                    foreach (array_keys($lh->getDynamicItemParsers(false)) as $parserHandle) {
                        $posted = $this->post("enable_$parserHandle");
                        $enabled = empty($posted) ? false : true;
                        $map[$parserHandle] = $enabled;
                        if ($enabled) {
                            $someEnabled = true;
                        }
                    }
                    if (!$someEnabled) {
                        $this->error->add(t('Please enable at least one item class to be translated.'));
                    } else {
                        foreach ($map as $parserHandle => $enabled) {
                            $lh->setParserEnabled($parserHandle, $enabled);
                        }
                        $lh->setConfigured(true);
                        $this->redirect('/dashboard/system/basics/localizer/options/updated/');
                    }
                } catch (Exception $x) {
                    $this->error->add($x->getMessage());
                }
            } else {
                $this->error->add($this->token->getErrorMessage());
            }
        }
        $this->view();
    }
}
