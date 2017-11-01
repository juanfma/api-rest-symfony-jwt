<?php

namespace AppBundle\Services;

use Firebase\JWT\JWT;

class JwtAuth
{
    public $manager;
    public $key;

    public function __construct($manager)
    {
        $this->manager = $manager;
        $this->key = 'soylaclavesecreta435735742';
    }

    public function signup($email, $password, $getHash = false)
    {
        $user = $this->manager->getRepository('AppBundle:Users')->findOneBy(array(
            'email' => $email,
            'password' => $password
        ));

        if (is_object($user)) {
            $token = array(
                'sub' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'surname' => $user->getSurname(),
                'iat' => time(),
                'exp' => time() + (7 * 24 * 60 * 60)
            );

            $jwt = JWT::encode($token, $this->key, 'HS256');
            $decoded = JWT::decode($jwt, $this->key, array('HS256'));

            if ($getHash) {
                $data = $jwt;
            } else {
                $data = $decoded;
            }
        } else {
            $data = array(
                'status' => 'error',
                'data' => 'Email or password incorrect'
            );
        }

        return $data;
    }

    public function checkToken($jwt, $getIdentity = false)
    {
        $auth = false;

        try {
            $decoded = JWT::decode($jwt, $this->key, array('HS256'));
        } catch (\Exception $e) {

        }

        if (isset($decoded) && is_object($decoded) && isset($decoded->sub)) {
            $auth = true;
        }

        if (!$getIdentity) {
            return $auth;
        } else {
            return $decoded;
        }
    }
}