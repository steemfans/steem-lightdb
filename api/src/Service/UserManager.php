<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\ConfigManager;

use App\Entity\Users;
use App\Entity\UserRelations;
use App\Service\Discord;

class UserManager
{
    private $logger;
    private $em;
    private $config_manager;

    public function __construct(
                        LoggerInterface $logger,
                        EntityManagerInterface $em,
                        ConfigManager $config_manager,
                        Discord $discord
                    )
    {
        $this->logger = $logger;
        $this->em = $em;
        $this->config_manager = $config_manager;
        $this->discord = $discord;
    }

    public function addUser($data)
    {
        try {
            extract($data);
            $username = $operation[1]['new_account_name'];
            $json_metadata = $operation[1]['json_metadata'];
            if ($json_metadata != '') {
                $json_metadata = json_decode($json_metadata, true);
            } else {
                $json_metadata = [];
            }
            $user = $this->em->getRepository(Users::class)->findOneBy(['username'=>$username]);
            if ($user) {
                $msg = "username {$username} has existed";
                $this->logger->info($msg);
            } else {
                $user = new Users();
                $user->setUsername($username);
                $user->setJsonMetadata($json_metadata);
                $user->setCreatedAt($timestamp);
                $this->em->persist($user);
                $this->em->flush();
                $which_table = 'users';
                $which_id = $user->getId();
                $msg = 'user_create_info: '.json_encode(compact(
                                                'block_num',
                                                'transaction_id',
                                                'username',
                                                'which_table',
                                                'which_id'
                                            ));
                $this->logger->info($msg);
            }
        } catch (\Exception $e) {
            $msg = 'user_create_error: '.$e->getMessage().", ".json_encode($data);
            $this->logger->error($msg);
            $this->discord->notify('error', $msg);
        }
        echo $msg."\n";
    }

    public function updateUser($data)
    {
        try {
            extract($data);
            $username = $operation[1]['account'];
            $json_metadata = $operation[1]['json_metadata'];
            if ($json_metadata != '') {
                $json_metadata = json_decode($json_metadata, true);
            } else {
                $json_metadata = [];
            }
            $user = $this->em
                            ->getRepository(Users::class)
                            ->findOneBy([
                                'username' => $username,
                            ]);
            if ($user) {
                $user->setJsonMetadata($json_metadata);
                $user->setUpdatedAt($timestamp);
                $this->em->persist($user);
                $this->em->flush();
                $which_table = 'users';
                $which_id = $user->getId();
                $msg = 'user_update_info: '.json_encode(compact(
                                                'block_num',
                                                'transaction_id',
                                                'username',
                                                'which_table',
                                                'which_id'
                                            ));
                $this->logger->info($msg);
            } else {
                $msg = '[error]user_update: not_found, '.json_encode($data);
                $this->logger->error($msg);
            }
        } catch (\Exception $e) {
            $msg = '[error]user_update: '.$e->getMessage().json_encode($data);
            $this->logger->error($msg);
            $this->discord->notify('error', $msg);
        }
        echo $msg."\n";
        return;
    }
    
    public function addFollowing($follower, $following, $what, $block_num, $transaction_id, $timestamp)
    {
        $data = compact('follower', 'following', 'what', 'block_num', 'transaction_id', 'timestamp');
        $follower_user = $this->em
                                ->getRepository(Users::class)
                                ->findOneBy([
                                    'username' => $follower,
                                ]);
        if (!$follower_user) {
            $msg = '[error]follower_not_exist: '.json_encode($data);
            echo $msg."\n";
            $this->logger->error($msg);
            return;
        }

        $following_user = $this->em
                                ->getRepository(Users::class)
                                ->findOneBy([
                                    'username' => $following,
                                ]);
        if (!$following_user) {
            $msg = '[error]following_not_exist: '.json_encode($data);
            $this->logger->error($msg);
            echo $msg."\n";
            return;
        }

        $relation = new UserRelations();
        $relation->setFollower($follower_user);
        $relation->setFollowing($following_user);
        $relation->setCreatedAt($timestamp);
        $relation->setWhat($what);
        
        try {
            $this->em->persist($relation);
            $this->em->flush();
            $msg = 'add_following: '.json_encode($data);
        } catch (\Exception $e) {
            $msg = 'add_following_failed: '.$e->getMessage().', '.json_encode($data);
            $this->logger->error($msg);
            $this->discord->notify('error', $msg);
            echo $msg."\n";
        }

        return;
    }
}
