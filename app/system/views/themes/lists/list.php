<div class="row-fluid">
    <div class="panel panel-inverse">
        <?= form_open(current_url(),
            [
                'id'   => 'list-form',
                'role' => 'form',
            ],
            ['_method' => 'POST']
        ); ?>

        <div class="panel-body">
            <div class="row">
                <?= $this->makePartial('themes/theme_list'); ?>
            </div>
        </div>

        <div class="panel-footer">
            <?= $this->makePartial('lists/list_pagination') ?>
        </div>

        <?= form_close(); ?>
    </div>
</div>
