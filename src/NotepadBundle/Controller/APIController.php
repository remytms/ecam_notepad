<?php

namespace NotepadBundle\Controller;

use NotepadBundle\Entity\Category;
use NotepadBundle\Entity\Note;
use NotepadBundle\Form\NoteType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @Route("/notepad/api")
 */
class APIController extends Controller
{
    /*
     * Status code constant for http responses
     */
    const SC_BADREQ = 400;
    const SC_NOTFOUND = 404;

    /*
     * Notes API
     */

    /**
     * @Route("/notes")
     * @Method({"GET", "OPTIONS"})
     */
    public function allNotesAction(Request $request)
    {
        $allowed_methods = 'GET, OPTIONS';

        if ($request->isMethod('OPTIONS')) {
            return $this->getCrossOriginResponse($allowed_methods);
        }

        $note_repository = $this->getDoctrine()
            ->getRepository('NotepadBundle:Note');

        $notes = $note_repository->findBy(
            array(), 
            array('date' => 'desc')
        );

        $notes_array = array();

        foreach ($notes as $note) {
            $notes_array[] = $note->toArray();
        }

        return $this->setCrossOriginResponse(
            new JsonResponse($notes_array),
            $allowed_methods
        );
    }

    /**
     * @Route("/tag/{search}/notes")
     * @Method({"GET", "OPTIONS"})
     */
    public function searchNotesAction(Request $request, $search)
    {
        $allowed_methods = 'GET, OPTIONS';

        if ($request->isMethod('OPTIONS')) {
            return $this->getCrossOriginResponse($allowed_methods);
        }

        $note_repository = $this->getDoctrine()
            ->getRepository('NotepadBundle:Note');

        $notes = $note_repository->findBy(
            array(),
            array('date' => 'desc')
        );

        $notes_array = array();

        foreach ($notes as $note) {
            $dom = new \DOMDocument();
            $dom->loadXML($note->getXMLContent());
            $xpath = new \DOMXpath($dom);
            $elements = $xpath->evaluate("/note/tag"); 
            $added = false;
            foreach ($elements as $element) {
                if (trim(strtolower($element->nodeValue)) === 
                    trim(strtolower($search)) && !$added) {
                    $notes_array[] = $note->toArray();
                    $added = true;
                }
            }
        }

        return $this->setCrossOriginResponse(
            new JsonResponse($notes_array),
            $allowed_methods
        );
    }

    /**
     * @Route("/notes/{note}")
     * @Method({"OPTIONS"})
     */
    public function getNotesCrossOriginAction(Note $note)
    {
        return $this->getCrossOriginResponse('GET, PATCH, DELETE, OPTIONS');
    }

    /**
     * @Route("/notes/{note}")
     * @Method({"GET"})
     */
    public function getNotesAction(Note $note)
    {
        return $this->setCrossOriginResponse(
            new JsonResponse($note->toArray()),
            'GET, PATCH, DELETE, OPTIONS'
        );
    }

    /**
     * @Route("/categories/{category}/notes")
     * @Method({"POST", "OPTIONS"})
     */
    public function newNoteAction(Request $request, Category $category)
    {
        $allowed_methods = 'POST, OPTIONS';

        if ($request->isMethod('OPTIONS')) {
            return $this->getCrossOriginResponse($allowed_methods);
        }

        $content = $request->getContent();
        $validator = $this->get('validator');

        if (empty($content)) {
            $msg = "Content is empty";
            return new JsonResponse(['error' => $msg], self::SC_BADREQ);
        }

        $note_data = json_decode($content, true);
        if (!$note_data) {
            $msg = "Content is not a valid json";
            return new JsonResponse(['error' => $msg], self::SC_BADREQ);
        }

        $note = new Note();
        if (array_key_exists('title', $note_data)) 
            $note->setTitle($note_data['title']);
        if (array_key_exists('date', $note_data)) 
            $note->setDate(new \DateTime($note_data['date']));
        if (array_key_exists('content', $note_data)) 
            $note->setContent($note_data['content']);
        $note->setCategory($category);

        $errors = $validator->validate($note);

        if (count($errors) > 0) {
            $response_array['error'] = "Note is not valid";
            return new JsonResponse($response_array, self::SC_BADREQ);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($note);
        $em->flush();

        return $this->setCrossOriginResponse(
            new JsonResponse($note->toArray()),
            $allowed_methods
        );
    }

    /**
     * @Route("/notes/{note}")
     * @Method({"PATCH"})
     */
    public function editNoteAction(Request $request, Note $note)
    {
        $content = $request->getContent();
        $validator = $this->get('validator');

        if (empty($content)) {
            $msg = "Content is empty";
            return new JsonResponse(['error' => $msg], self::SC_BADREQ);
        }

        $note_data = json_decode($content, true);
        if (!$note_data) {
            $msg = "Content is not a valid json";
            return new JsonResponse(['error' => $msg], self::SC_BADREQ);
        }

        $category_repository = $this->getDoctrine()
            ->getRepository('NotepadBundle:Category');

        if (array_key_exists('title', $note_data)) 
            $note->setTitle($note_data['title']);
        if (array_key_exists('date', $note_data)) 
            $note->setDate(new \DateTime($note_data['date']));
        if (array_key_exists('content', $note_data)) 
            $note->setContent($note_data['content']);
        if (array_key_exists('category', $note_data)) {
            if (array_key_exists('id', $note_data['category']))
                $category = $category_repository->find(
                    $note_data['category']['id']
                );
            if (!empty($category))
                $note->setCategory($category);
        }

        $errors = $validator->validate($note);

        if (count($errors) > 0) {
            $response_array['error'] = "Note is not valid";
            return new JsonResponse($response_array, self::SC_BADREQ);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($note);
        $em->flush();

        return $this->setCrossOriginResponse(
            new JsonResponse($note->toArray()),
            'GET, PATCH, DELETE, OPTIONS'
        );
    }

    /**
     * @Route("/notes/{note}")
     * @Method({"DELETE"})
     */
    public function deleteNotesAction(Note $note)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($note);
        $em->flush();

        return $this->setCrossOriginResponse(
            new JsonResponse(['sucess' => true]),
            'GET, PATCH, DELETE, OPTIONS'
        );
    }

    /*
     * Categories API
     */

    /**
     * @Route("/categories")
     * @Method({"OPTIONS"})
     */
    public function getAllCategoriesCrossOriginAction()
    {
        return $this->getCrossOriginResponse('GET, POST, OPTIONS');
    }

    /**
     * @Route("/categories/{category}")
     * @Method({"OPTIONS"})
     */
    public function getCategoriesCrossOriginAction(Category $category)
    {
        return $this->getCrossOriginResponse('GET, PATCH, DELETE, OPTIONS');
    }

    /**
     * @Route("/categories")
     * @Method({"GET"})
     */
    public function allCategoriesAction()
    {
        $repository = $this->getDoctrine()
            ->getRepository('NotepadBundle:Category');

        $categories = $repository->findAll();

        $categories_array = array();

        foreach ($categories as $category) {
            $categories_array[] = $category->toArray();
        }

        return $this->setCrossOriginResponse(
            new JsonResponse($categories_array),
            'GET, POST, OPTIONS'
        );
    }

    /**
     * @Route("/categories/{category}")
     * @Method({"GET"})
     */
    public function getCategoriesAction(Category $category)
    {
        return $this->setCrossOriginResponse(
            new JsonResponse($category->toArray()),
            'GET, PATCH, DELETE, OPTIONS'
        );
    }

    /**
     * @Route("/categories")
     * @Method({"POST"})
     */
    public function newCategoriesAction(Request $request)
    {
        $content = $request->getContent();
        $validator = $this->get('validator');

        if (empty($content)) {
            $msg = "Content is empty";
            return new JsonResponse(['error' => $msg], self::SC_BADREQ);
        }

        $category_data = json_decode($content, true);
        if (!$category_data) {
            $msg = "Content is not a valid json";
            return new JsonResponse(['error' => $msg], self::SC_BADREQ);
        }

        $category = new Category();
        if (array_key_exists('name', $category_data)) 
            $category->setName($category_data['name']);

        $errors = $validator->validate($category);

        if (count($errors) > 0) {
            $response_array['error'] = "Category is not valid";
            return new JsonResponse($response_array, self::SC_BADREQ);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($category);
        $em->flush();

        return $this->setCrossOriginResponse(
            new JsonResponse($category->toArray()),
            'GET, POST, OPTIONS'
        );
    }

    /**
     * @Route("/categories/{category}")
     * @Method({"PATCH"})
     */
    public function editCategoriesAction(Request $request, Category $category)
    {
        $content = $request->getContent();
        $validator = $this->get('validator');

        if (empty($content)) {
            $msg = "Content is empty";
            return new JsonResponse(['error' => $msg], self::SC_BADREQ);
        }

        $category_data = json_decode($content, true);
        if (!$category_data) {
            $msg = "Content is not a valid json";
            return new JsonResponse(['error' => $msg], self::SC_BADREQ);
        }

        if (array_key_exists('name', $category_data)) 
            $category->setName($category_data['name']);

        $errors = $validator->validate($category);

        if (count($errors) > 0) {
            $response_array['error'] = "Note is not valid";
            return new JsonResponse($response_array, self::SC_BADREQ);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($category);
        $em->flush();

        return $this->setCrossOriginResponse(
            new JsonResponse($category->toArray()),
            'GET, PATCH, DELETE, OPTIONS'
        );
    }

    /**
     * @Route("/categories/{category}")
     * @Method({"DELETE"})
     */
    public function deleteCategoriesAction(Category $category)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($category);
        $em->flush();

        return $this->setCrossOriginResponse(
            new JsonResponse(['sucess' => true]),
            'GET, PATCH, DELETE, OPTIONS'
        );
    }

    /**
     * Cross-Origin response
     *
     * Args:
     *     $methods: string containing all the accepted methods coma
     *               separated
     */
    private function getCrossOriginResponse($methods)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/text');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set("Access-Control-Allow-Methods", $methods);
        return $response;
    }

    private function setCrossOriginResponse($response, $methods)
    {
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set("Access-Control-Allow-Methods", $methods);
        return $response;
    }
}
