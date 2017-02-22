<?php

namespace NotepadBundle\Controller;

use NotepadBundle\Entity\Category;
use NotepadBundle\Form\CategoryType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/notepad/category")
 */
class CategoryController extends Controller
{
    /**
     * @Route("/create/{name}", name="notepad_category_create")
     */
    public function createAction($name)
    {
        $category = new Category();
        $em = $this->getDoctrine()->getManager();

        $category->setName($name);

        $em->persist($category);
        $em->flush();

        return $this->redirectToRoute('notepad_category_list');
    }

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
        $category = new Category();
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
}
