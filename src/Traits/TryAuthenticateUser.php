<?php

namespace Parfaitement\Traits;

trait TryAuthenticateUser
{
    public function beforeSuccess($validator)
    {
        $user_verify = wp_signon([
            'user_login' => $this->core->request->input('username'),
            'user_password' => $this->core->request->input('password'),
            'remember' => $this->core->request->input('remember'),
        ], is_ssl());

        if (is_wp_error($user_verify)) {

            $validator->errors()->add('failed', $user_verify->get_error_message());

            $_SESSION['errors'] = $validator->errors();
            $_SESSION['old'] = $this->core->request->input();

            return false;
        }

        return true;
    }
}