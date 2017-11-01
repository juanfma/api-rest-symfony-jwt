<?php

namespace AppBundle\Controller;

use AppBundle\Services\JwtAuth;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Tasks;
use AppBundle\Services\Helpers;

class TaskController extends Controller
{
    /**
     * @Route("/task/{id}", requirements={"id": "\d+"})
     */
    public function newAction(Request $request, $id = null)
    {
        $helpers = $this->get(Helpers::class);
        $jwt_auth = $this->get(JwtAuth::class);

        $token = $request->get('authorization', null);
        $authCheck = $jwt_auth->checkToken($token);

        if ($authCheck) {
            $identity = $jwt_auth->checkToken($token, true);
            $json = $request->get('json', null);

            if ($json != null) {
                $params = json_decode($json);

                $createdAt = new \DateTime('now');
                $updatedAt = new \DateTime('now');

                $user_id = ($identity->sub != null) ? $identity->sub : null;
                $title = (isset($params->title)) ? $params->title : null;
                $description = (isset($params->description)) ? $params->description : null;
                $status = (isset($params->status)) ? $params->status : null;

                if ($user_id != null && $title != null) {
                    $em = $this->getDoctrine()->getManager();
                    $user = $em->getRepository('AppBundle:Users')->findOneBy(array(
                        'id' => $user_id
                    ));

                    if ($id == null) {
                        $task = new Tasks();
                        $task->setUser($user);
                        $task->setTitle($title);
                        $task->setDescription($description);
                        $task->setStatus($status);
                        $task->setCreatedAt($createdAt);
                        $task->setUpdatedAt($updatedAt);

                        $em->persist($task);
                        $em->flush();

                        $data = array(
                            'status' => 'success',
                            'code' => 200,
                            'data' => $task
                        );
                    } else {
                        $task = $em->getRepository('AppBundle:Tasks')->findOneBy(array(
                            'id' => $id
                        ));

                        if (isset($identity->sub) && $identity->sub == $task->getUser()->getId()) {
                            $task->setTitle($title);
                            $task->setDescription($description);
                            $task->setStatus($status);
                            $task->setUpdatedAt($updatedAt);

                            $em->persist($task);
                            $em->flush();

                            $data = array(
                                'status' => 'success',
                                'code' => 200,
                                'data' => $task
                            );

                        } else {
                            $data = array(
                                'status' => 'error',
                                'code' => 400,
                                'data' => 'Task not updated, you not owner!!'
                            );
                        }
                    }
                } else {
                    $data = array(
                        'status' => 'error',
                        'code' => 400,
                        'data' => 'Task not created, validation failed!!'
                    );
                }
            } else {
                $data = array(
                    'status' => 'error',
                    'code' => 400,
                    'data' => 'Task not created, params failed!!'
                );
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

    /**
     * @Route("/task/list")
     */
    public function listAction(Request $request)
    {
        $helpers = $this->get(Helpers::class);
        $jwt_auth = $this->get(JwtAuth::class);

        $token = $request->get('authorization', null);
        $authCheck = $jwt_auth->checkToken($token);

        if ($authCheck) {
            $identity = $jwt_auth->checkToken($token, true);

            $em = $this->getDoctrine()->getManager();

            $dql = 'SELECT t FROM AppBundle:Tasks t ORDER BY t.id DESC';
            $query = $em->createQuery($dql);

            $page = $request->query->getInt('page', 1);
            $paginator = $this->get('knp_paginator');
            $items_per_page = 10;

            $pagination = $paginator->paginate($query, $page, $items_per_page);
            $total_items_count = $pagination->getTotalItemCount();

            $data = array(
                'status' => 'success',
                'code' => 200,
                'total_items_count' => $total_items_count,
                'page_actual' => $page,
                'items_per_page' => $items_per_page,
                'total_pages' => ceil($total_items_count / $items_per_page),
                'data' => $pagination
            );
        } else {
            $data = array(
                'status' => 'error',
                'code' => 400,
                'data' => 'Authorization not valid!!'
            );
        }

        return $helpers->json($data);
    }

    /**
     * @Route("/task/details/{id}", requirements={"id": "\d+"})
     */
    public function taskAction(Request $request, $id = null)
    {
        $helpers = $this->get(Helpers::class);
        $jwt_auth = $this->get(JwtAuth::class);

        $token = $request->get('authorization', null);
        $authCheck = $jwt_auth->checkToken($token);

        if ($authCheck) {
            $identity = $jwt_auth->checkToken($token, true);

            $em = $this->getDoctrine()->getManager();

            $task = $em->getRepository('AppBundle:Tasks')->findOneBy(array(
                'id' => $id
            ));

            if ($task && is_object($task) && $identity->sub == $task->getUser()->getId()) {
                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'data' => $task
                );
            } else {
                $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'data' => 'Task not found'
                );
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

    /**
     * @Route("/task/search/{search}")
     */
    public function searchAction(Request $request, $search = null)
    {
        $helpers = $this->get(Helpers::class);
        $jwt_auth = $this->get(JwtAuth::class);

        $token = $request->get('authorization', null);
        $authCheck = $jwt_auth->checkToken($token);

        if ($authCheck) {
            $identity = $jwt_auth->checkToken($token, true);

            $em = $this->getDoctrine()->getManager();

            $filter = $request->get('filter', null);

            if (empty($filter)) {
                $filter = null;
            } elseif ($filter == 1) {
                $filter = 'new';
            } elseif ($filter == 2) {
                $filter = 'todo';
            } else {
                $filter = 'finished';
            }

            $order = $request->get('order', null);

            if (empty($order) || $order == 2) {
                $order = 'DESC';
            } else {
                $order = 'ASC';
            }

            if ($search != null) {
                $dql = "SELECT t FROM AppBundle:Tasks t "
                    . "WHERE t.user = $identity->sub AND "
                    . "(t.title LIKE :search OR t.description LIKE :search ) ";
            } else {
                $dql = "SELECT t FROM appBundle.Tasks t "
                    . "WHERE t.user = $identity->sub ";
            }

            if ($filter != null) {
                $dql .= "AND t.status = :filter ";
            }

            $dql .= "ORDER BY t.id $order";

            $query = $em->createQuery($dql)->setParameter('filter', "$filter");

            if (!empty($query)) {
                $query->setParameter('search', "%$search%");
            }

            $tasks = $query->getResult();

            $data = array(
                'status' => 'success',
                'code' => 200,
                'data' => $tasks
            );
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