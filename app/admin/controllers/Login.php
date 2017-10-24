<?php namespace Admin\Controllers;

use AdminAuth;
use Admin\Traits\ValidatesForm;
use Request;
use Admin\Models\Users_model;
use AdminMenu;
use Template;
use ValidationException;
use Validator;

class Login extends \Admin\Classes\AdminController
{
    use ValidatesForm;

    protected $requireAuthentication = FALSE;

    public $bodyClass = 'page-login';

    public function index()
    {
        if (AdminAuth::isLogged())
            return $this->redirect('dashboard');

        Template::setTitle(lang('admin::login.text_title'));

        if ($this->validateLoginForm()) {
            $credentials = [
                'username' => post('user'),
                'password' => post('password'),
            ];

            if (!AdminAuth::authenticate($credentials, TRUE, TRUE)) {
                flash()->set('danger', lang('admin::login.alert_username_not_found'));
                return $this->redirectBack();
            }

            activity()->causedBy(AdminAuth::getUser())
                      ->log(lang('system::activities.activity_logged_in'));

            if ($redirectUrl = input('redirect'))
                return $this->redirect($redirectUrl);

            return $this->redirect('dashboard');
        }

        return $this->makeView('auth/login');
    }

    public function reset()
    {
        if (AdminAuth::islogged()) {
            return $this->redirect('dashboard');
        }

        Template::setTitle(lang('admin::login.text_password_reset_title'));

        $data['resetCode'] = input('code');

        if ($this->_resetPassword()) {
            return $this->redirect('login');
        }

        return $this->makeView('auth/reset');
    }

    protected function _resetPassword()
    {
        if ($this->validateResetForm() === TRUE) {
            if (!get_post('code')) {
                $username = post('username');
                if (AdminAuth::resetPassword($username)) {
                    flash()->set('success', lang('admin::login.alert_email_sent'));

                    return TRUE;
                }

                $error = lang('admin::login.alert_email_not_sent');
            }
            else {
                $credentials = [
                    'reset_code' => get_post('code'),
                    'password'   => post('password'),
                ];

                if (AdminAuth::validateResetPassword($credentials)) {
                    AdminAuth::completeResetPassword($credentials);
                    flash()->set('success', lang('admin::login.alert_success_reset'));

                    return TRUE;
                }

                $error = lang('admin::login.alert_failed_reset');
            }

            flash()->set('danger', $error);
            return $this->redirect(current_url());
        }
    }

    protected function validateLoginForm()
    {
        if (!$post = post())
            return false;

        return $this->validatePasses($post, [
            ['user', 'lang:admin::login.label_username', 'required|exists:users,username'],
            ['password', 'lang:admin::login.label_password', 'required|min:6'],
        ]);
    }

    protected function validateResetForm()
    {
        if (!$post = post())
            return false;

        if (input('code')) {
            $rules = [
                ['password', 'lang:admin::login.label_password', 'required|min:6|max:32|same:password_confirm]'],
                ['password_confirm', 'lang:admin::login.label_password_confirm', 'required'],
            ];
        } else {
            $rules = ['username', 'lang:admin::login.label_username', 'required|exists:users'];
        }

        return $this->validatePasses($post, $rules);
    }
}