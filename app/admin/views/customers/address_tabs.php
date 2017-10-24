<?
$addresses = count($field->value) ? $field->value : $field->options();
$countries = System\Models\Countries_model::pluck('country_name');
?>
<div data-control="address-tabs"
     data-last-counter="<?= count($addresses) ?>">
    <ul id="sub-tabs" class="nav nav-tabs">
        <?php if (count($addresses)) { ?>
            <?php $index = 0;
            foreach ($addresses as $address) { ?>
                <li class="<?= $index++ == 0 ? 'active' : '' ?>">
                    <a href="#<?= $field->getId('address-'.$index); ?>" data-toggle="tab">
                        <?= lang('admin::customers.text_tab_address').' '.$index; ?>&nbsp;&nbsp;
                        <i class="fa fa-times-circle"
                           data-confirm="<?= lang('admin::default.alert_warning_confirm'); ?>"
                           data-remove-address></i>
                    </a>
                </li>
            <?php } ?>
        <?php } ?>
        <li>
            <a role="button" data-add-address><i class="fa fa-book"></i>&nbsp;<i class="fa fa-plus"></i></a>
        </li>
    </ul>

    <div class="tab-content" data-append-to>
        <?php if (count($addresses)) { ?>
            <?php $index = 0;
            foreach ($addresses as $address) { ?>
                <? $index++; ?>
                <?= $this->makePartial('customers/address', [
                    'field' => $field,
                    'countries' => $countries,
                    'address'   => $address,
                    'index'     => $index,
                ]); ?>
            <?php } ?>
        <?php } ?>
    </div>

    <script type="text/template" data-address-template>
        <?= $this->makePartial('customers/address', [
            'field' => $field,
            'countries' => $countries,
            'address' => [
                'address_id' => '',
                'address_1'  => '',
                'address_2'  => '',
                'city'       => '',
                'state'      => '',
                'postcode'   => '',
                'country_id' => '',
            ],
            'index'   => '%%index%%',
        ]) ?>
    </script>
</div>
