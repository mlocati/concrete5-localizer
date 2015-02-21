<?php defined('C5_EXECUTE') or die('Access Denied.');

$dh = Loader::helper('concrete/dashboard');

echo $dh->getDashboardPaneHeaderWrapper(t('Translate special items'), false, 'span12', false);

if (empty($locales)) {
    ?><div class="alert"><?php echo t('No locales defined.'); ?></div><?php
}
else {
    $ih = Loader::helper('concrete/interface');
    $jh = Loader::helper('json');
    /* @var $jh JsonHelper */
    ?>
    <script type="text/javascript">
        function updateCurrentGroup() {
            $(".tsi-table").hide();
            var tsi = $("#tsi-which").val();
            $("#tsi-table-" + tsi).show();
            $('#currentGroup').val(tsi);
        }
        $(document).ready(function() {
            updateCurrentGroup();
        });
    </script>
    <div class="ccm-pane-options">
        <div class="row">
            <div class="span5">
                <label>
                    <?php echo t('Items'); ?>
                    <select id="tsi-which" name="currentGroup" onchange="updateCurrentGroup()">
                        <?php
                        $index = 0;
                        foreach (array_keys($translationsGroups) as $tg) {
                            ?><option value="<?php echo $index; ?>"<?php echo ($tg == $currentGroup) ? ' selected="selected"' : ''; ?>><?php echo h($tg); ?></option><?php
                            $index++;
                        }
                        ?>
                    </select>
                </label>
            </div>
            <div class="span5">
                <label>
                    <?php echo t('Language'); ?>
                    <select onchange="window.location.href = <?php echo h($jh->encode(View::url('/dashboard/system/basics/localizer/?locale='))); ?> + encodeURIComponent(this.value)"><?php
                    foreach ($locales as $localeID => $localeName) {
                        ?><option value="<?php echo h($localeID); ?>"<?php echo ($localeID == $locale) ? ' selected="selected"' : ''; ?>><?php echo h($localeName); ?></option><?php
                    }
                    ?></select>
                </label>
            </div>
        </div>
    </div>
    <div class="ccm-pane-body">
        <form method="post" id="user-translate-form" action="<?php echo $this->action('update') ?>" class="form-horizontal">
            <input type="hidden" name="currentGroup" id="currentGroup" value="<?php echo h($currentGroup); ?>">
            <input type="hidden" name="locale" value="<?php echo h($locale); ?>">
            <?php
            echo $this->controller->token->output('update_translations');
            $already = array();
            $duplicatedHashes = array();
            $index = 0;
            foreach ($translationsGroups as $tg => $translations) {
                ?><table class="table table-striped table-condensed tsi-table" style="display:none" id="tsi-table-<?php echo $index; ?>">
                    <tbody>
                        <?php
                        foreach ($translations as $hash => $translation) {
                            /* @var $translation \Gettext\Translation */
                            if(isset($already[$hash])) {
                                $duplicated = true;
                                if($already[$hash]) {
                                    $duplicatedHashes[] = $hash;
                                    $already[$hash] = false;
                                }
                            } else {
                                $duplicated = false;
                                $already[$hash] = true;
                            }
                            ?><tr>
                                <td style="width:33%"><?php echo h($translation->getOriginal()); ?></td>
                                <td><input type="text" style="width:100%" placeholder="<?php echo h(t('Same as English (US)')); ?>"<?php
                                    if($duplicated) {
                                        ?> data-same-as-name="<?php echo h($hash); ?>"<?php
                                    } else {
                                        ?> name="<?php echo h($hash); ?>"<?php
                                    }
                                    if($translation->hasTranslation()) {
                                        ?> value="<?php echo h($translation->getTranslation()); ?>"<?php
                                    }
                                ?> /></td>
                            </tr><?php
                        }
                        ?>
                    </tbody>
                </table><?php
                $index++;
            }
            if (!empty($duplicatedHashes)) {
                ?><script>$(document).ready(function() {
var $form = $('#user-translate-form');
function SameGroup(hash) {
	var me = this;
	me.inputs = $form.find('input[name="'+hash+'"], input[data-same-as-name="'+hash+'"]');
	me.inputs.on('change', function() {
		var sourceInput = this, sourceText = sourceInput.value;
		me.inputs.each(function() {
			if(this !== sourceInput) {
				this.value = sourceText;
			}
		});
	});
}
$.each(<?php echo $jh->encode($duplicatedHashes) ?>, function(_, hash) {
	new SameGroup(hash);   
});
                });</script><?php
            }
            ?>
        </form>
    </div>
    <div class="ccm-pane-footer">
        <?php echo $ih->button(t('Options'), DIR_REL.'/'.DISPATCHER_FILENAME.'/dashboard/system/basics/localizer/options/', 'left'); ?>
        <?php echo $ih->button_js(t('Save'), "if(!this.already){this.already = true; $('#user-translate-form').submit(); }", 'right', 'primary'); ?>
    </div>
    <?php
}

echo $dh->getDashboardPaneFooterWrapper(false);
