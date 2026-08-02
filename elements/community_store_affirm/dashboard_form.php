<?php defined('C5_EXECUTE') or die(_("Access Denied.")); 
extract($vars);
?>

<div class="form-group">
    <label><?= t('Mode') ?></label>
    <?= $form->select('affirmMode', [false => t('Live'), true => t('Test Mode')], $affirmMode); ?>
</div>

<div class="form-group">
    <label><?= t('Public API Key') ?></label>
    <input type="text" name="affirmPublicApiKey" value="<?= h($affirmPublicApiKey) ?>" class="form-control">
</div>

<div class="form-group">
    <label><?= t('Private API Key') ?></label>
    <input type="text" name="affirmPrivateApiKey" value="<?= h($affirmPrivateApiKey) ?>" class="form-control">
</div>

<div class="form-group">
    <label><?= t('Merchant Name') ?></label>
    <input type="text" name="affirmMerchantName" value="<?= h($affirmMerchantName) ?>" class="form-control">
    <span class="help-block"><?= t('Customer-facing merchant name shown in Affirm checkout. Defaults to your site name if left blank.') ?></span>
</div>

<div class="form-group">
    <label><?= t('Financial Product Key') ?></label>
    <input type="text" name="affirmFinancialProductKey" value="<?= h($affirmFinancialProductKey) ?>" class="form-control">
    <span class="help-block"><?= t('Optional financing program key from your Affirm merchant dashboard.') ?></span>
</div>
