<?php namespace System\Controllers;

use Admin\Traits\WidgetMaker;
use AdminAuth;
use Exception;
use System\Classes\ExtensionManager;
use System\Models\Extensions_model;
use System\Models\Settings_model;
use AdminMenu;
use Template;

class Extensions extends \Admin\Classes\AdminController
{
    use WidgetMaker;

    public $implement = [
        'Admin\Actions\ListController',
    ];

    public $listConfig = [
        'list' => [
            'model'          => 'System\Models\Extensions_model',
            'title'          => 'lang:system::extensions.text_title',
            'emptyMessage'   => 'lang:system::extensions.text_empty',
            'defaultSort'    => ['title', 'ASC'],
            'showCheckboxes' => FALSE,
            'configFile'     => 'extensions_model',
        ],
    ];

    public $formConfig = [
        'name'       => 'lang:system::extensions.text_form_name',
        'model'      => 'System\Models\Extensions_model',
        'create'     => [
            'title'         => 'lang:admin::default.form.create_title',
            'redirect'      => 'extensions/edit/{code}',
            'redirectClose' => 'extensions',
        ],
        'edit'       => [
            'title'         => 'lang:admin::default.form.edit_title',
            'redirect'      => 'extensions/edit/{code}',
            'redirectClose' => 'extensions',
        ],
        'delete'     => [
            'redirect' => 'extensions',
        ],
        'configFile' => '',
    ];

    protected $requiredPermissions = 'Admin.Extensions';

    public $formWidget;

    public $toolbarWidget;

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('extensions');
    }

    public function index()
    {
        if (AdminAuth::hasPermission('Admin.Extensions.Manage'))
            Extensions_model::syncLocal();

        $this->asExtension('ListController')->index();
    }

    public function settings($context, $code = null)
    {
        try {
            if (!strlen($code)) {
                throw new Exception(lang('admin::extensions.alert_setting_missing_id'));
            }

            if (!$settingItem = Settings_model::getSettingItem($code)) {
                throw new Exception(lang('admin::extensions.alert_setting_not_found'));
            }

            if ($settingItem->permissions)
                AdminAuth::restrict($settingItem->permissions);

            $pageTitle = lang($settingItem->label ?: 'text_edit_title');
            Template::setTitle($pageTitle);
            Template::setHeading($pageTitle);

            $model = $this->formFindModelObject($settingItem);

            $this->initFormWidget($model, $context);
        } catch (Exception $ex) {
            $this->handleError($ex);
        }
    }

    public function upload($context = null)
    {
        Template::setTitle(lang('admin::extensions.text_add_title'));
        Template::setHeading(lang('admin::extensions.text_add_title'));

        Template::setButton(lang('admin::extensions.button_icon_back'), ['class' => 'btn btn-default', 'href' => admin_url('extensions')]);
        Template::setButton(lang('admin::extensions.button_browse'), ['class' => 'btn btn-default pull-right', 'href' => admin_url('updates/browse/extensions')]);

        // Prep the optional toolbar widget
        if (isset($config['toolbar']) AND isset($this->widgets['toolbar'])) {
            $this->toolbarWidget = $this->widgets['toolbar'];
            $this->toolbarWidget->addButtons(array_get($config['toolbar'], 'buttons', []));
        }
    }

    public function delete($context, $extensionCode = null)
    {
        try {
            $pageTitle = lang('admin::extensions.text_delete_title');
            Template::setTitle($pageTitle);
            Template::setHeading($pageTitle);

            $extensionClass = ExtensionManager::instance()->findExtension($extensionCode);
            $model = Extensions_model::where('name', $extensionCode)->first();

            // Extension must be disabled before it can be deleted
            if ($model AND $model->status) {
                flash()->set('warning', sprintf(lang('admin::extensions.alert_error_nothing'), lang('admin::extensions.text_deleted').lang('admin::extensions.alert_is_installed')));
                return $this->redirectBack();
            }

            // Extension not found in filesystem
            // so delete from database
            if (!$extensionClass) {
                Extensions_model::deleteExtension($extensionCode, TRUE);
                flash()->set('success', sprintf(lang('admin::extensions.alert_success'), "Extension deleted "));
                return $this->redirectBack();
            }

            // Lets display a delete confirmation screen
            // with list of files to be deleted
            $meta = $extensionClass->extensionMeta();
            $this->vars['extensionModel'] = $model;
            $this->vars['extensionMeta'] = $meta;
            $this->vars['extensionName'] = isset($meta['name']) ? $meta['name'] : '';
            $this->vars['extensionData'] = $model->data;
            $this->vars['filesToDelete'] = ExtensionManager::instance()->files_path($extensionCode);
        } catch (Exception $ex) {
            $this->handleError($ex);
        }
    }

    public function index_onInstall($context = null)
    {
        $extensionCode = post('code');
        $extension = ExtensionManager::instance()->findExtension($extensionCode);

        if (Extensions_model::install($extensionCode, $extension)) {
            $meta = $extension->extensionMeta();
            $title = isset($meta['name']) ? $meta['name'] : '';

            flash()->set('success', sprintf(lang('admin::extensions.alert_success'), "Extension {$title} installed "));
            if ($extension->registerComponents()) {
                flash()->set('info', sprintf(lang('admin::extensions.alert_info_layouts'), admin_url('layouts')));
            }
        }
        else {
            flash()->set('danger', lang('admin::extensions.alert_error_try_again'));
        }

        $this->refreshList('list');
    }

    public function index_onUninstall($context = null)
    {
        $extensionCode = post('code');
        $extension = ExtensionManager::instance()->findExtension($extensionCode);

        if (Extensions_model::uninstall($extensionCode, $extension) AND $extension) {
            $meta = $extension->extensionMeta();
            $extension_name = isset($meta['name']) ? $meta['name'] : '';

            flash()->set('success', sprintf(
                lang('admin::extensions.alert_success'), "Extension {$extension_name} uninstalled "
            ));
        }
        else {
            flash()->set('danger', lang('admin::extensions.alert_error_try_again'));
        }

        $this->refreshList('list');
    }

    public function settings_onSave($context, $extensionCode = null)
    {
        if (!strlen($extensionCode)) {
            throw new Exception(lang('admin::extensions.alert_setting_missing_id'));
        }

        if (!$settingItem = Settings_model::getSettingItem($extensionCode)) {
            throw new Exception(lang('admin::extensions.alert_setting_not_found'));
        }

        if ($settingItem->permissions)
            AdminAuth::restrict($settingItem->permissions);

        $model = $this->formFindModelObject($settingItem);

        $this->initFormWidget($model, $context);

        $validate = $this->formValidate($this->formWidget);
        if ($validate === FALSE)
            return;

        if ($model->set($this->formWidget->getSaveData())) {
            flash()->set('success', sprintf(lang('admin::default.alert_success'), lang($settingItem->label).' settings updated '));
        }
        else {
            flash()->set('warning', sprintf(lang('admin::default.alert_error_nothing'), 'updated'));
        }

        return $this->refresh();
    }

    public function upload_onUpload($context = null)
    {
        if (!isset($_FILES['extension_zip']) OR !$this->validateUpload()) {
            flash()->set('danger', sprintf(
                lang('admin::extensions.alert_error'), lang('admin::extensions.error_config_no_found')
            ));
            $this->refresh();
        }

        $extractedPath = ExtensionManager::instance()->extract_extension($_FILES['extension_zip']['tmp_name']);
        if (!$extractedPath) {
            flash()->set('danger', sprintf(
                lang('admin::extensions.alert_error'), lang('admin::extensions.error_config_no_found')
            ));
            $this->refresh();
        }

        $extension_code = basename($extractedPath);
        $path = ExtensionManager::instance()->path($extension_code);
        $extension = ExtensionManager::instance()->loadExtension($extension_code, $path);

        if ($extension) {
            Extensions_model::install($extension_code, $extension);

            $meta = $extension->extensionMeta();
            $extension_name = isset($meta['name']) ? $meta['name'] : '';
            $alert = "Extension {$extension_name} uploaded & installed ";
            flash()->set('success', sprintf(lang('admin::extensions.alert_success'), $alert));
        }

        $this->refresh();
    }

    public function delete_onDelete($context = null, $extensionCode = null)
    {
        $deleteData = post('delete_data');

        $extension = ExtensionManager::instance()->findExtension($extensionCode);

        if (Extensions_model::deleteExtension($extensionCode, ($deleteData == 1))) {
            $meta = $extension->extensionMeta();
            $extension_name = isset($meta['name']) ? $meta['name'] : '';

            flash()->set('success', sprintf(lang('admin::extensions.alert_success'), "Extension {$extension_name} deleted "));
        }
        else {
            flash()->set('danger', lang('admin::extensions.alert_error_try_again'));
        }

        return $this->redirectBack();
    }

    public function listOverrideColumnValue($record, $column, $alias = null)
    {
        if ($column->type != 'button')
            return null;

        $attributes = $column->attributes;

        if ($column->columnName == 'delete' AND $record->status)
            $attributes['class'] = $attributes['class'].' disabled';

        if ($column->columnName != 'delete' AND !$record->class)
            $attributes['class'] = 'btn btn-default disabled';

        return $attributes;
    }

    protected function initFormWidget($model, $context = null)
    {
        $config = $model->getFieldConfig();

        $modelConfig = array_except($config, 'toolbar');
        $modelConfig['model'] = $model;
        $modelConfig['arrayName'] = str_singular(strip_class_basename($model, '_model'));
        $modelConfig['context'] = $context;

        // Form Widget with extensibility
        $this->formWidget = $this->makeWidget('Admin\Widgets\Form', $modelConfig);
        $this->formWidget->bindToController();

        // Prep the optional toolbar widget
        if (isset($config['toolbar']) AND isset($this->widgets['toolbar'])) {
            $this->toolbarWidget = $this->widgets['toolbar'];
            $this->toolbarWidget->addButtons(array_get($config['toolbar'], 'buttons', []));
        }
    }

    protected function createModel($class)
    {
        if (!strlen($class) OR !class_exists($class)) {
            throw new Exception(sprintf(lang('admin::extensions.alert_setting_missing_model'), $class));
        }

        $model = new $class;

        return $model;
    }

    protected function formFindModelObject($settingItem)
    {
        $model = $this->createModel($settingItem->model);

        // Prepare query and find model record
        $query = $model->newQuery();
        $result = $query->where('name', $settingItem->owner)->first();

        if (!$result) {
            throw new Exception(lang('admin::extensions.alert_setting_not_found'));
        }

        return $result;
    }

    protected function formValidate($form)
    {
        if (!isset($form->config['rules']))
            return null;

        return $this->validatePasses($form->getSaveData(), $form->config['rules']);
    }

    protected function validateUpload()
    {
        if (!isset($_FILES['extension_zip']))
            return FALSE;

        $zip = $_FILES['extension_zip'];
        if (!strlen($zip['name']) OR !strlen($zip['tmp_name']))
            return FALSE;

        if (preg_match('/\s/', $zip['name'])) {
            flash()->set('danger', lang('admin::extensions.error_upload_name'));

            return FALSE;
        }

        if ($zip['type'] !== 'application/zip') {
            flash()->set('danger', lang('admin::extensions.error_upload_type'));

            return FALSE;
        }

        $zip['name'] = html_entity_decode($zip['name'], ENT_QUOTES, 'UTF-8');
        $zip['name'] = str_replace(['"', "'", "/", "\\"], "", $zip['name']);
        $filename = $this->security->sanitize_filename($zip['name']);
        $zip['name'] = basename($filename, '.zip');

        if (!empty($zip['error'])) {
            flash()->set('danger', lang('admin::extensions.error_php_upload').$zip['error']);

            return FALSE;
        }

        if (ExtensionManager::instance()->hasExtension($zip['name'])) {
            flash()->set('danger', sprintf(lang('admin::extensions.alert_error'), lang('admin::extensions.error_extension_exists')));

            return FALSE;
        }

        if (is_uploaded_file($zip['tmp_name'])) return TRUE;

        return FALSE;
    }
}