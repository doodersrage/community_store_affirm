<?php defined('C5_EXECUTE') or die(_("Access Denied.")); 
extract($vars);
?>

<div class="form-group">
    <label><?= t('Mode')?></label>
    <?= $form->select('affirmMode',array(false=>'Live',true=>'Test Mode'),$affirmMode); ?>
</div>

<div class="form-group">
    <label><?=t("PUBLIC API KEY")?></label>
    <input type="text" name="affirmPublicApiKey" value="<?= $affirmPublicApiKey?>" class="form-control">
</div>

<div class="form-group">
    <label><?=t("PRIVATE API KEY")?></label>
    <input type="text" name="affirmPrivateApiKey" value="<?= $affirmPrivateApiKey?>" class="form-control">
</div>

<div class="form-group">
    <label><?=t("Financial Product Key")?></label>
    <input type="text" name="affirmFinancialProductKey" value="<?= $affirmFinancialProductKey?>" class="form-control">
</div>
