<div class="row-fluid">
    <?= form_open(current_url(),
        [
            'id'   => 'edit-form',
            'role' => 'form',
        ],
        ['_method' => 'PATCH']
    ); ?>

    <?= $this->renderForm(); ?>

    <?= form_close(); ?>
</div>
