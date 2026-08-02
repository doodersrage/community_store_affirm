<?php defined('C5_EXECUTE') or die('Access Denied.');
extract($vars);
?>

<script>
affirm.checkout(<?= $affJSON ?>);
affirm.checkout.open();
</script>