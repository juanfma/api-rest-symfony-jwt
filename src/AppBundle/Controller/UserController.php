<?php

namespace AppBundle\Controller;

use AppBundle\Services\JwtAuth;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Email;
use AppBundle\Entity\Users;
use AppBundle\Services\Helpers;

class UserController extends Controller
{
    /**
     * @Route("/user/new", name="new")
     */
    public function newAction(Request $request)
    {
        $helpers = $this->get(Helpers::class);

        $json = $request->get('json', null);
        $params = json_decode($json);

        $data = array(
            'status' => 'error',
            'code' => 400,
            'data' => 'User no created!!'
        );

        if ($json != null) {
            $createdAt = new \DateTime('now');
            $rol = 'user';
            $email = (isset($params->email)) ? $params->email : null;
            $name = (isset($params->name)) ? $params->name : null;
            $surname = (isset($params->surname)) ? $params->surname : null;
            $password = (isset($params->password)) ? $params->password : null;

            $emailConstraint = new Email();
            $emailConstraint->message = 'Email not valid!!';
            $validate_email = $this->get('validator')->validate($email, $emailConstraint);

            if ($email != null && count($validate_email) == 0 && $name != null && $surname != null && $password != null) {
                $user = new Users;
                $user->setCreatedAt($createdAt);
                $user->setRole($rol);
                $user->setEmail($email);
                $user->setName($name);
                $user->setSurname($surname);

                $pwd = hash('sha256', $password);
                $user->setPassword($pwd);

                $em = $this->getDoctrine()->getManager();
                $isset_user = $em->getRepository('AppBundle:Users')->findBy(array(
                    'email' => $email
                ));

                if (count($isset_user) == 0) {
                    $em->persist($user);
                    $em->flush();

                    $data = array(
                        'status' => 'success',
                        'code' => 200,
                        'data' => 'New user created!!',
                        'user' => $user
                    );
                } else {
                    $data = array(
                        'status' => 'error',
                        'code' => 400,
                        'data' => 'User no created, duplicated!!'
                    );
                }
            }
        }
        return $helpers->json($data);
    }

    /**
     * @Route("/user/edit", name="edit")
     */
    public function editAction(Request $request)
    {
        $helpers = $this->get(Helpers::class);
        $jwt_auth = $this->get(JwtAuth::class);

        $token = $request->get('authorization', null);
        $authCheck = $jwt_auth->checkToken($token);

        if ($authCheck) {
            $em = $this->getDoctrine()->getManager();

            $identity = $jwt_auth->checkToken($token, true);

            $user = $em->getRepository('AppBundle:Users')->findOneBy(array(
                'id' => $identity->sub
            ));

            $json = $request->get('json', null);
            $params = json_decode($json);

            $data = array(
                'status' => 'error',
                'code' => 400,
                'data' => 'User no updated!!'
            );

            if ($json != null) {
                $rol = 'user';
                $email = (isset($params->email)) ? $params->email : null;
                $name = (isset($params->name)) ? $params->name : null;
                $surname = (isset($params->surname)) ? $params->surname : null;
                $password = (isset($params->password)) ? $params->password : null;

                $emailConstraint = new Email();
                $emailConstraint->message = 'Email not valid!!';
                $validate_email = $this->get('validator')->validate($email, $emailConstraint);

                if ($email != null && count($validate_email) == 0 && $name != null && $surname != null) {
                    $user->setRole($rol);
                    $user->setEmail($email);
                    $user->setName($name);
                    $user->setSurname($surname);

                    if ($password != null) {
                        $pwd = hash('sha256', $password);
                        $user->setPassword($pwd);
                    }

                    $isset_user = $em->getRepository('AppBundle:Users')->findBy(array(
                        'email' => $email
                    ));

                    if (count($isset_user) == 0 || $identity->email = $email) {
                        $em->persist($user);
                        $em->flush();

                        $data = array(
                            'status' => 'success',
                            'code' => 200,
                            'data' => 'User updated!!',
                            'user' => $user
                        );
                    } else {
                        $data = array(
                            'status' => 'error',
                            'code' => 400,
                            'data' => 'User no updated, duplicated!!'
                        );
                    }
                }
            }
        } else {
            $data = array(
                'status' => 'error',
                'code' => 400,
                'data' => 'Authorization not valid!!'
            );
        }
        return $helpers->json($data);
    }
}