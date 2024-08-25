<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\GoogleMeetingRepository")
 */
class GoogleMeeting
{
  /**
   * @ORM\Id()
   * @ORM\GeneratedValue()
   * @ORM\Column(type="integer")
   */
  private $id;

  /**
   * @ORM\Column(type="integer")
   */
  private $organizerId;

  /**
   * @ORM\Column(type="integer", nullable=true)
   */
  private $calendarId;

  /**
   * @ORM\Column(type="text", nullable=true)
   */
  private $topic;

  /**
   * @ORM\Column(type="text", nullable=true)
   */
  private $description;

  /**
   * @ORM\Column(type="string",length=255)
   */
  private $meetUrl;

  /**
   * @ORM\Column(type="string", length=86)
   */
  private $timeZone;

  /**
   * @ORM\Column(type="datetime")
   */
  private $startDate;

  /**
   * @ORM\Column(type="datetime")
   */
  private $endDate;

  /**
   * @ORM\Column(type="datetime")
   */
  private $createdAt;

  /**
   * @ORM\Column(type="datetime")
   */
  private $updatedAt;

  public function getId(): ?int
  {
    return $this->id;
  }

  public function getOrganizerId(): ?int
  {
    return $this->organizerId;
  }

  public function setOrganizerId(int $organizerId): self
  {
    $this->organizerId = $organizerId;

    return $this;
  }

  public function getCalendarId(): ?int
  {
    return $this->calendarId;
  }

  public function setCalendarId(?int $calendarId): self
  {
    $this->calendarId = $calendarId;

    return $this;
  }

  public function getTopic(): ?string
  {
    return $this->topic;
  }

  public function setTopic(?string $topic): self
  {
    $this->topic = $topic;

    return $this;
  }

  public function getDescription(): ?string
  {
    return $this->description;
  }

  public function setDescription(?string $description): self
  {
    $this->description = $description;

    return $this;
  }

  public function getMeetUrl(): ?string
  {
    return $this->meetUrl;
  }

  public function setMeetUrl(string $meetUrl): self
  {
    $this->meetUrl = $meetUrl;

    return $this;
  }

  public function getTimeZone(): ?string
  {
    return $this->timeZone;
  }

  public function setTimeZone(string $timeZone): self
  {
    $this->timeZone = $timeZone;

    return $this;
  }

  public function getStartDate(): ?\DateTimeInterface
  {
    return $this->startDate;
  }

  public function setStartDate(\DateTimeInterface $startDate): self
  {
    $this->startDate = $startDate;

    return $this;
  }

  public function getEndDate(): ?\DateTimeInterface
  {
    return $this->endDate;
  }

  public function setEndDate(\DateTimeInterface $endDate): self
  {
    $this->endDate = $endDate;

    return $this;
  }

  public function getCreatedAt(): ?\DateTimeInterface
  {
    return $this->createdAt;
  }

  public function setCreatedAt(\DateTimeInterface $createdAt): self
  {
    $this->createdAt = $createdAt;

    return $this;
  }

  public function getUpdatedAt(): ?\DateTimeInterface
  {
    return $this->updatedAt;
  }

  public function setUpdatedAt(\DateTimeInterface $updatedAt): self
  {
    $this->updatedAt = $updatedAt;

    return $this;
  }
}
