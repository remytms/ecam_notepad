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
     * @Method({"GET"})
     */
    public function allNotesAction()
    {
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

        return new JsonResponse($notes_array);
    }

    /**
     * @Route("/tag/{search}/notes")
     * @Method({"GET"})
     */
    public function searchNotesAction($search)
    {
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

        return new JsonResponse($notes_array);
    }


    /**
     * @Route("/notes/{note}")
     * @Method({"GET"})
     */
    public function getNotesAction(Note $note)
    {
        return new JsonResponse($note->toArray());
    }

    /**
     * @Route("/categories/{category}/notes")
     * @Method({"POST"})
     */
    public function newNoteAction(Request $request, Category $category)
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

        return new JsonResponse($note->toArray());
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

        return new JsonResponse($note->toArray());
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

        return new JsonResponse(['sucess' => true]);
    }

    /*
     * Categories API
     */

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

        return new JsonResponse($categories_array);
    }

    /**
     * @Route("/categories/{category}")
     * @Method({"GET"})
     */
    public function getCategoriesAction(Category $category)
    {
        return new JsonResponse($category->toArray());
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

        return new JsonResponse($category->toArray());
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

        return new JsonResponse($category->toArray());
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

        return new JsonResponse(['sucess' => true]);
    }
}
