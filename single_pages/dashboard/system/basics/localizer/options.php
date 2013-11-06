<?php defined('C5_EXECUTE') or die('Access Denied.');

$dh = Loader::helper('concrete/dashboard');
$ih = Loader::helper('concrete/interface');

echo $dh->getDashboardPaneHeaderWrapper(t('Localizer options'), false, 'span12', false);
?>
<div class="ccm-pane-body">
	<form method="post" id="localizer-options" action="<?php echo $this->action('update') ?>" class="form-horizontal">
		<?php echo $this->controller->token->output('update'); ?>
		<fieldset>
			<legend><?php echo t('Which items to you want to be translated?')?></legend>
			<table class="table table-striped">
				<tbody><?php
					foreach($translationTables as $translationTable) {
						?><tr>
							<td><?php echo $translationTable['name']; ?></td>
							<td><?php
								if(!is_bool($translationTable['selected'])) {
									?><span style="color: #777"><?php echo h(t($translationTable['selected'])); ?></span><?php
								}
								else {
									?>
									<label class="radio inline"><input type="radio" name="enable_<?php echo $translationTable['context']; ?>" value="1" <?php echo $translationTable['selected'] ? ' checked' : ''; ?>> <?php echo t('enabled')?></label>
									<label class="radio inline"><input type="radio" name="enable_<?php echo $translationTable['context']; ?>" value="0" <?php echo $translationTable['selected'] ? '' : ' checked'; ?>> <?php echo t('disabled')?></label>
									<?php
								}
							?></td>
						</tr><?php
					}
				?></tbody>
			</table>
		</fieldset>
	</form>
	<div class="alert alert-danger">
		<strong><?php echo t('Warning!'); ?></strong>
		<?php echo t('Disabled item classes causes translations to be lost!'); ?>
	</div>
</div>
<div class="ccm-pane-footer">
	<?php echo $ih->button(t('Localizer'), DIR_REL . '/' . DISPATCHER_FILENAME . '/dashboard/system/basics/localizer/', 'left'); ?>
	<?php echo $ih->button_js(t('Save'), "if(!this.already){this.already = true; $('#localizer-options').submit(); }", 'right', 'primary'); ?>
</div>
<?php

echo $dh->getDashboardPaneFooterWrapper(false);