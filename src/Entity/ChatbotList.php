<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="chatbot_lists")
 * @Assert\Callback({"App\Validation\Validator", "validate"})
 */
class ChatbotList {



    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * * @ORM\Column(type="integer", length=11, nullable=true)
     */
    private $org_id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $template_id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $custom_name;

    /**
     * @ORM\Column(nullable=true)
     */
    private $type;

    /**
     * @ORM\Column(nullable=false)
     */
    protected $description;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $chatbot_id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $chatbot_name;

    /**
     * @ORM\Column(nullable=true)
     */
    private $status;

    /**
     * @var \DateTime $createdAt
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime $updatedAt
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;


    public function getId() {
        return $this->id;
    }

    public function __toString() {
        return $this->getName();
    }

    public function __call($method, $arguments) {
        return PropertyAccess::createPropertyAccessor()->getValue($this->translate(), $method);
    }

    public function getName() {
        return $this->translate()->getName();
    }

    public function getUpdatedAt() {
        return $this->updatedAt == $this->createdAt ? null : $this->updatedAt;
    }

    public function getCreatedAt() {
        return $this->createdAt;
    }

    public function setCreatedAt($createdAt) {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function setUpdatedAt($updatedAt) {
        $this->updatedAt = $updatedAt;

        return $this;
    }




}
