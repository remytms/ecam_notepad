<?php

namespace NotepadBundle\Controller;

use NotepadBundle\Entity\Category;
use NotepadBundle\Entity\Note;
use NotepadBundle\Form\NoteType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/notepad/note")
 */
class NoteController extends Controller
{
    /**
     * @Route("/list", name="notepad_note_list")
     * @Method({"GET", "POST"})
     */
    public function listAction(Request $request)
    {
        $note_repository = $this->getDoctrine()
            ->getRepository('NotepadBundle:Note');

        $all_notes = $note_repository->findBy(
            array(), 
            array('date' => 'desc')
        );
        $notes = array();
        $search_term = "";

        if ($request->getMethod() === 'POST') {
            $search_term = $request->request->get('srch');
            if (!empty($search_term)) {
                foreach ($all_notes as $note) {
                    $dom = new \DOMDocument();
                    $dom->loadXML($note->getXMLContent());
                    $xpath = new \DOMXpath($dom);
                    $elements = $xpath->evaluate("/note/tag"); 
                    $added = false;
                    foreach ($elements as $element) {
                        if (trim(strtolower($element->nodeValue)) === 
                            trim(strtolower($search_term)) && !$added) {
                            $notes[] = $note;
                            $added = true;
                        }
                    }
                }
            } else {
                $notes = $all_notes;
            }
        }

        if ($request->getMethod() === 'GET')
            $notes = $all_notes;


        return $this->render(
            'NotepadBundle:Note:list_note.html.twig',
            array(
                'notes' => $notes,
                'search' => $search_term,
            ));
    }

    /**
     * @Route("/new", name="notepad_note_new")
     */
    public function newAction(Request $request)
    {
        return $this->editAction($request, new Note());
    }

    /**
     * @Route("/edit/{note}", name="notepad_note_edit")
     */
    public function editAction(Request $request, Note $note)
    {
        $form = $this->createForm(NoteType::class, $note);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $note = $form->getData();

            $em = $this->getDoctrine()->getManager();
            $em->persist($note);
            $em->flush();

            return $this->redirectToRoute('notepad_note_list');
        }

        return $this->render(
            'NotepadBundle:Note:new_note.html.twig', 
            array(
                'form' => $form->createView(),
            ));
    }

    /**
     * @Route("/delete/{note}", name="notepad_note_delete")
     */
    public function deleteAction(Note $note)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($note);
        $em->flush();

        return $this->redirectToRoute('notepad_note_list');
    }
}
