<?php

namespace NotepadBundle\Controller;

use NotepadBundle\Entity\Category;
use NotepadBundle\Form\CategoryType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/notepad/category")
 */
class CategoryController extends Controller
{
    /**
     * @Route("/list", name="notepad_category_list")
     */
    public function listAction()
    {
        $category_repository =
            $this->getDoctrine()->getRepository('NotepadBundle:Category');

        $categories = $category_repository->findAll();

        return $this->render(
            'NotepadBundle:Category:list_category.html.twig',
            array(
                'categories' => $categories,
            ));
    }

    /**
     * @Route("/new", name="notepad_category_new")
     */
    public function newAction(Request $request)
    {
        return $this->editAction($request, new Category());
    }

    /**
     * @Route("/edit/{category}", name="notepad_category_edit")
     */
    public function editAction(Request $request, Category $category)
    {
        $form = $this->createForm(CategoryType::class, $category);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $category = $form->getData();

            $em = $this->getDoctrine()->getManager();
            $em->persist($category);
            $em->flush();

            return $this->redirectToRoute('notepad_category_list');
        }

        return $this->render(
            'NotepadBundle:Category:new_category.html.twig', 
            array(
                'form' => $form->createView(),
            ));
    }

    /**
     * @Route("/delete/{category}", name="notepad_category_delete")
     */
    public function deleteAction(Category $category)
    {
        $em = $this->getDoctrine()->getManager();

        if ($category->getNotes()->isEmpty()) {
            $em->remove($category);
            $em->flush();
        } else {
            return new Response(
                '<html><body>Cannot delete this category. Notes are
                assigned to this category. Please delete all the
                associated note before trying again.</body></html>');
        }

        return $this->redirectToRoute('notepad_category_list');
    }
}
