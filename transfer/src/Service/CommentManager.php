<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\ConfigManager;

class CommentManager
{
    private $logger;
    private $em;
    private $config_manager;

    public function __construct(
                        LoggerInterface $logger,
                        EntityManagerInterface $em,
                        ConfigManager $config_manager
                    )
    {
        $this->logger = $logger;
        $this->em = $em;
        $this->config_manager = $config_manager;
    }

    public function addOrUpdateComment($data)
    {
        var_dump('add_or_update_comment_in_comment_service', $data);
    }

    public function voteComment($data)
    {
        var_dump('vote_comment_in_comment_service', $data);
    }

    public function delComment($data)
    {
        var_dump('del_comment_in_comment_service', $data);
    }
}
