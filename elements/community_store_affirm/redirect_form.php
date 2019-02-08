<?php defined('C5_EXECUTE') or die(_("Access Denied."));
extract($vars);
// Here we're setting up the form we're going to submit to paypal.
// This form will automatically submit itself 
?>

<script>
affirm.checkout(<?php echo $affJSON; ?>);
affirm.checkout.open();
</script>