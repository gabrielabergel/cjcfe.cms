Merci pour votre message

Bonjour <?= $name ?>,

Nous avons bien reçu votre message et nous vous en remercions. Notre équipe vous répondra dans les plus brefs délais.

Récapitulatif de votre message :
<?php if (!empty($subject)): ?>
Sujet: <?= $subject ?>
<?php endif; ?>

<?= $message ?>

Cordialement,
Le CJCFE

---
<?= $site->url() ?>
