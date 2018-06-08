<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MultiTasksRepository")
 * @ORM\Table(indexes={@ORM\Index(name="task_type_idx", columns={"task_type"})})
 */
class MultiTasks
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="bigint", options={"unsigned"=true})
     */
    private $id;

    /**
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private $task_type;

    /**
     * @ORM\Column(type="bigint", options={"unsigned"=true})
     */
    private $block_num_from;

    /**
     * @ORM\Column(type="bigint", options={"unsigned"=true})
     */
    private $block_num_to;

    /**
     * @ORM\Column(type="boolean")
     */
    private $is_finished;

    public function getId()
    {
        return $this->id;
    }

    public function getTaskType(): ?int
    {
        return $this->task_type;
    }

    public function setTaskType(int $task_type): self
    {
        $this->task_type = $task_type;

        return $this;
    }

    public function getBlockNumFrom(): ?int
    {
        return $this->block_num_from;
    }

    public function setBlockNumFrom(int $block_num_from): self
    {
        $this->block_num_from = $block_num_from;

        return $this;
    }

    public function getBlockNumTo(): ?int
    {
        return $this->block_num_to;
    }

    public function setBlockNumTo(int $block_num_to): self
    {
        $this->block_num_to = $block_num_to;

        return $this;
    }

    public function getIsFinished(): ?bool
    {
        return $this->is_finished;
    }

    public function setIsFinished(bool $is_finished): self
    {
        $this->is_finished = $is_finished;

        return $this;
    }
}
