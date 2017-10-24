<div class="panel panel-table">
    <?= form_open(current_url(),
        [
            'id'   => 'list-form',
            'role' => 'form',
        ],
        ['_method' => 'POST']
    ); ?>

    <div class="table-responsive">
        <table class="table table-striped">
            <tbody>
            <?php if (count($records)) { ?>
                <?= $this->makePartial('lists/list_body') ?>
            <?php }
            else { ?>
                <tr>
                    <td colspan="<?= $columnTotal ?>"><?= $emptyMessage; ?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>

    <div class="panel-footer">
        <?= $this->makePartial('lists/list_pagination') ?>
    </div>

    <?= form_close(); ?>
</div>
