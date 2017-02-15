<?php

namespace NotepadBundle\Controller;

use NotepadBundle\Entity\Category;
use NotepadBundle\Entity\Note;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route("/notepad/note")
 */
class NoteController extends Controller
{
    /**
     * @Route("/create/{category}", name="notepad_note_create")
     */
    public function createAction(Category $category)
    {
        $em = $this->getDoctrine()->getManager();
        $note = new Note();

        $note->setTitle("Test note");
        $note->setContent("Blah, blah content for this note.");
        $note->setCategory($category);

        $em->persist($note);
        $em->flush();

        return $this->redirectToRoute('notepad_note_list');
    }


    /**
     * @Route("/list", name="notepad_note_list")
     */
    public function listAction()
    {
        $note_repository = $this->getDoctrine()->getRepository('NotepadBundle:Note');

        $notes = $note_repository->findAll();

        return $this->render(
            'NotepadBundle:Note:list_note.html.twig',
            array(
                'notes' => $notes,
            ));
    }
}
