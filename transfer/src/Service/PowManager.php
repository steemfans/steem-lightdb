<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Users;
use App\Service\Discord;

class PowManager 
{
    private $logger;
    private $em;
    private $discord;

    public function __construct(
                        LoggerInterface $logger,
                        EntityManagerInterface $em,
                        Discord $discord
                    )
    {
        $this->logger = $logger;
        $this->em = $em;
        $this->discord = $discord;
    }

    public function powHandle($data)
    {
        try {
            extract($data);
            $username = $operation[1]['worker_account'];
            $json_metadata = [];
            $user = $this->em->getRepository(Users::class)->findOneBy(['username'=>$username]);
            if (!$user) {
                $user = new Users();
                $user->setUsername($username);
                $user->setJsonMetadata($json_metadata);
                $user->setIsPow(true);
                $this->em->persist($user);
                $this->em->flush();
                $msg = 'pow_user_create_success: '.json_encode($data);
                $this->logger->info($msg);
                echo $msg."\n";
            }
        } catch(\Exception $e) {
            $msg = 'pow_user_create_error: '.$e->getMessage().", ".json_encode($data);
            $this->logger->error($msg);
            $this->discord->notify('error', $msg);
            echo $msg."\n";
        }
    }

    public function pow2Handle($data)
    {
        try {
            extract($data);
            $username = $operation[1]['work'][1]['input']['worker_account'];
            $json_metadata = [];
            $user = $this->em->getRepository(Users::class)->findOneBy(['username'=>$username]);
            if (!$user) {
                $user = new Users();
                $user->setUsername($username);
                $user->setJsonMetadata($json_metadata);
                $user->setIsPow(true);
                $this->em->persist($user);
                $this->em->flush();
                $msg = 'pow2_user_create_success: '.json_encode($data);
                $this->logger->info($msg);
                echo $msg."\n";
            }
        } catch(\Exception $e) {
            $msg = 'pow2_user_create_error: '.$e->getMessage().", ".json_encode($data);
            $this->logger->error($msg);
            $this->discord->notify('error', $msg);
            echo $msg."\n";
        }
    }
}
