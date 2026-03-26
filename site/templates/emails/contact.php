Nouveau message de contact

Nom: <?= $name ?>
Email: <?= $email ?>
<?php if (!empty($phone)): ?>
Téléphone: <?= $phone ?>
<?php endif; ?>
<?php if (!empty($subject)): ?>
Sujet: <?= $subject ?>
<?php endif; ?>

Message:
<?= $message ?>

---
Envoyé depuis <?= $site->url() ?>
