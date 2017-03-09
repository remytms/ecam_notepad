<?php

namespace NotepadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Category
 *
 * @ORM\Table(name="category")
 * @ORM\Entity(repositoryClass="NotepadBundle\Repository\CategoryRepository")
 */
class Category
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     */
    private $name;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Note", mappedBy="category")
     */
    private $notes;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->notes = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * String representation
     */
    public function __toString()
    {
        return $this->getName();
    }

    /**
     * Return an array representing this object.
     *
     * @return array
     */
    public function toArray() {
        return array(
            'id' => $this->getId(),
            'name' => $this->getName(),
        );
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Category
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add note
     *
     * @param \NotepadBundle\Entity\Note $note
     *
     * @return Category
     */
    public function addNote(\NotepadBundle\Entity\Note $note)
    {
        $this->notes[] = $note;

        return $this;
    }

    /**
     * Remove note
     *
     * @param \NotepadBundle\Entity\Note $note
     */
    public function removeNote(\NotepadBundle\Entity\Note $note)
    {
        $this->notes->removeElement($note);
    }

    /**
     * Get notes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getNotes()
    {
        return $this->notes;
    }
}
