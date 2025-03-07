<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Login user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $email = $request->email;
        $password = $request->password;

        $user = User::where('email', $email)->first();
        if ($user) {
            $is_auth = Hash::check($password, $user->password);

            if ($is_auth) {
                $token = hash('sha256', Str::random(60));
                $user->remember_token = $token;
                $user->save();

                return [
                    'status' => 'success',
                    'message' => 'Login successfuly',
                    'token' => $token,
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Email or password don´t match',
                'token' => null,
            ];
        }

        return [
            'status' => 'error',
            'message' => 'Email or password don´t match',
            'token' => null,
        ];
    }

    /**
     * Logout user from  application.
     *
     * @param  string  $token
     * @return \Illuminate\Http\Response
     */
    public function logout($token)
    {
        $user = User::where('remember_token', $token)->first();
        $user->remember_token = null;
        $user->save();

        return ['status' => 'success', 'message' => ''];
    }

    function checkRequiredFields($request, $field_list)
    {
        $errors = [];
        foreach ($field_list as &$field) {
            if (empty($request->input($field))) {
                array_push(
                    $errors,
                    (object) [
                        'field' => $field,
                        'message' => 'Name is required!',
                    ]
                );
            }
        }

        return $errors;
    }

    /**
     * Create a new user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function signup(Request $request)
    {
        $field_list = ['name', 'email', 'password', 'password2'];
        $errors = $this->checkRequiredFields($request, $field_list);
        $status = 'error';
        $email = $request->email;
        // Check if have any user register with the same email
        $find_user = User::where('email', $email)->first();
        if ($find_user) {
            array_push(
                $errors,
                (object) [
                    'field' => 'email',
                    'message' => 'Email already exists',
                ]
            );
        }
        // Check if both passwords match
        $password = $request->password;
        $password2 = $request->password2;
        if ($password !== $password2) {
            array_push(
                $errors,
                (object) [
                    'field' => 'password2',
                    'message' => "Passwords don't match",
                ]
            );
        }
        // Create user if don't have find any errors
        if (!count($errors)) {
            $password = $request->password;
            $password2 = $request->password2;

            if ($password === $password2) {
                $status = 'success';

                $new_user = new User();
                $new_user->name = $request->name;
                $new_user->email = $email;
                $new_user->password = Hash::make($password);
                $new_user->save();
            }
        }

        return ['status' => $status, 'errors' => $errors];
    }
}
