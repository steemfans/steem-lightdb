<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\ConfigManager;

use App\Entity\Users;
use App\Entity\Tags;
use App\Entity\Posts;
use App\Entity\Comments;
use App\Entity\PostsVotes;
use App\Entity\CommentsVotes;

use DiffMatchPatch\DiffMatchPatch;
use App\Service\Discord;

class CommentManager
{
    private $logger;
    private $em;
    private $config_manager;
    private $discord;

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

    public function handle($data)
    {
        extract($data);
        $comment_type = null;
        if ($operation[1]['parent_author'] == '') {
            $comment_type = 'post';
            $this->addOrUpdatePost($data);
        } else {
            $comment_type = 'comment';
            $this->addOrUpdateComment($data);
        }
    }

    public function addOrUpdatePost($data)
    {
        extract($data);
        try {
            $main_tag = $this->getTag($operation[1]['parent_permlink']);
            $author = $this->getAuthor($operation[1]['author']);
            if (!$author)
                throw new \Exception('author not exist');
            $permlink = $operation[1]['permlink'];
            $title = $operation[1]['title'];
            $body = $operation[1]['body'];
            if ($operation[1]['json_metadata'] == '') {
                $json_metadata = [];
            } else {
                $json_metadata = json_decode($operation[1]['json_metadata'], true);
                if ($json_metadata == null) {
                    $json_metadata = [];
                }
            }

            $post = $this->getPost($author, $permlink);
            if (!$post) {
                $post = new Posts();
                $post->setMainTag($main_tag);
                $post->setAuthor($author);
                $post->setPermlink($permlink);
                $post->setTitle($title);
                $post->setBody($body);
                $post->setJsonMetadata($json_metadata);
                $post->setCreatedAt($timestamp);

                if (isset($json_metadata['tags']) && is_array($json_metadata['tags'])) {
                    foreach($json_metadata['tags'] as $k => $tag_name) {
                        $post->addTag($this->getTag($tag_name));
                    }
                }
                $msg = 'will_add_post: '.json_encode($data);
            } else {
                $dmp = new DiffMatchPatch();
                try {
                    $patches = $dmp->patch_fromText($body);
                    $newBody = $dmp->patch_apply($patches, $post->getBody());
                    $post->setBody($newBody[0]);
                } catch(\Exception $ee) {
                    $post->setBody($body);
                    $tmp_msg = 'diff_match_patch_post_failed: '.json_encode($data);
                    $this->logger->info($tmp_msg);
                }
                $post->setUpdatedAt($timestamp);
                $msg = 'will_update_post: '.json_encode($data);
            }
            $this->logger->info($msg);
            echo $msg."\n";

            $this->em->persist($post);
            $this->em->flush();

            $msg = 'add_or_update_post_success';
            $this->logger->info($msg);
            echo $msg."\n";
        } catch(\Exception $e) {
            $msg = 'add_or_update_post: '.$e->getMessage().', '.json_encode($data);
            $this->logger->error($msg);
            $this->discord->notify('error', $msg);
            echo $msg."\n";
        }
    }

    public function addOrUpdateComment($data)
    {
        extract($data);
        try {
            $parent_permlink = $operation[1]['parent_permlink'];
            $parent_author = $this->getAuthor($operation[1]['parent_author']);
            $permlink = $operation[1]['permlink'];
            $author = $this->getAuthor($operation[1]['author']);
            if (!$author || !$parent_author)
                throw new \Exception('author not exist or parent_author not exist');
            $title = $operation[1]['title'];
            $body = $operation[1]['body'];
            if ($operation[1]['json_metadata'] == '') {
                $json_metadata = [];
            } else {
                $json_metadata = json_decode($operation[1]['json_metadata'], true);
                if ($json_metadata == null) {
                    $json_metadata = [];
                }
            }

            $parent_comment = $this->getComment($parent_author, $parent_permlink);
            if (!$parent_comment) {
                // this is the parent comment
                // get post which be commented.
                $post = $this->getPost($parent_author, $parent_permlink);
                if (!$post)
                    throw new \Exception('comment\'s post not exist');
                $is_current_comment_parent = true;
            } else {
                // this is the child comment
                $tmp = $parent_comment;
                while($tmp->getParent()) {
                    $tmp = $tmp->getParent();
                }
                $post = $tmp->getPost();
                $is_current_comment_parent = false;
            }
            $comment = $this->getComment($author, $permlink);
            if (!$comment) {
                $comment = new Comments();
                $comment->setPermlink($permlink);
                $comment->setAuthor($author);
                $comment->setTitle($operation[1]['title']);
                $comment->setBody($operation[1]['body']);
                $comment->setJsonMetadata($json_metadata);
                $comment->setPost($post);
                $comment->setParentAuthor($parent_author);
                $comment->setParentPermlink($parent_permlink);
                if (!$is_current_comment_parent) {
                    $comment->setParent($parent_comment);
                }
                $comment->setCreatedAt($timestamp);
                $msg = 'will_add_comment: '.json_encode($data);
            } else {
                try {
                    $dmp = new DiffMatchPatch();
                    $patches = $dmp->patch_fromText($operation[1]['body']);
                    $newBody = $dmp->patch_apply($patches, $comment->getBody());
                    $comment->setBody($newBody[0]);
                } catch(\Exception $ee) {
                    $comment->setBody($operation[1]['body']);
                    $tmp_msg = 'diff_match_patch_comment_failed: '.json_encode($data);
                    $this->logger->info($tmp_msg);
                }
                $comment->setUpdatedAt($timestamp);
                $msg = 'will_update_comment: '.json_encode($data);
            }
            $this->logger->info($msg);
            echo $msg."\n";

            $this->em->persist($comment);
            $this->em->flush();

            $msg = 'add_or_update_comment_success';
            $this->logger->info($msg);
            echo $msg."\n";
        } catch(\Exception $e) {
            $msg = 'add_or_update_comment: '.$e->getMessage().', '.json_encode($data);
            $this->logger->error($msg);
            $this->discord->notify('error', $msg);
            echo $msg."\n";
        }
    }

    public function voteComment($data)
    {
        extract($data);
        try {
            $voter = $this->getAuthor($operation[1]['voter']);
            $author = $this->getAuthor($operation[1]['author']);
            if (!$voter || !$author)
                throw new \Exception('voter_not_exist_or_author_not_exist');
            $permlink = $operation[1]['permlink'];
            $weight = $operation[1]['weight'];
            $post = $this->getPost($author, $permlink);
            if ($post) {
                $post_vote = $this->getPostsVotes($post, $voter);
                if ($post_vote) {
                    $post_vote->setWeight($weight);
                    $msg = 'vote_post_update: '.json_encode($data);
                } else {
                    $post_vote = new PostsVotes();
                    $post_vote->setPost($post);
                    $post_vote->setUser($voter);
                    if ($weight >= 0) {
                        $post_vote->setWeight($weight);
                        $post_vote->setUpdown(true);
                    } else {
                        $post_vote->setWeight(-1 * $weight);
                        $post_vote->setUpdown(false);
                    }
                    $post_vote->setCreatedAt($timestamp);
                    $msg = 'vote_post_create: '.json_encode($data);
                }
                $this->em->persist($post_vote);
                $this->em->flush();
            } else {
                $comment = $this->getComment($author, $permlink);
                if ($comment) {
                    $comment_vote = $this->getCommentsVotes($comment, $voter);
                    if ($comment_vote) {
                        $comment_vote->setWeight($weight);
                        $msg = 'vote_comment_update: '.json_encode($data);
                    } else {
                        $comment_vote = new CommentsVotes();
                        $comment_vote->setComment($comment);
                        $comment_vote->setUser($voter);
                        if ($weight >= 0) {
                            $comment_vote->setWeight($weight);
                            $comment_vote->setUpdown(true);
                        } else {
                            $comment_vote->setWeight(-1 * $weight);
                            $comment_vote->setUpdown(false);
                        }
                        $msg = 'vote_comment_create: '.json_encode($data);
                    }
                    $this->em->persist($comment_vote);
                    $this->em->flush();
                } else {
                    throw new \Exception('no_post_and_no_comment');
                }
            }
            $this->logger->info($msg);
            echo $msg."\n";
        } catch (\Exception $e) {
            $msg = 'vote_error: '.$e->getMessage().', '.json_encode($data);
            $this->logger->error($msg);
            $this->discord->notify('error', $msg);
            echo $msg."\n";
        }
    }

    public function delComment($data)
    {
        extract($data);
        try {
            $author = $this->getAuthor($operation[1]['author']);
            if (!$author)
                throw new \Exception('author_not_exist');
            $permlink = $operation[1]['permlink'];
            $post = $this->getPost($author, $permlink);
            if ($post) {
                $post->setIsDel(true);
                $this->em->persist($post);
                $this->em->flush();
                $msg = 'del_post_success: '.json_encode($data);
            } else {
                $comment = $this->getComment($author, $permlink);
                if (!$comment)
                    throw new \Exception('cannot_find_post_or_comment');
                $comment->setIsDel(true);
                $this->em->persist($comment);
                $this->em->flush();
                $msg = 'del_comment_success: '.json_encode($data);
            }
            $this->logger->info($msg);
            echo $msg."\n";
        } catch(\Exception $e) {
            $msg = 'del_post_or_comment_failed: '.$e->getMessage().', '.json_encode($data);
            $this->logger->error($msg);
            $this->discord->notify('error', $msg);
            echo $msg."\n";
        }
    }

    public function getTag($tag_name)
    {
        try {
            $tag = $this->em
                            ->getRepository(Tags::class)
                            ->findOneBy([
                                'tag_name' => $tag_name,
                            ]);
            if (!$tag) {
                $tag = new Tags();
                $tag->setTagName($tag_name);
                $this->em->persist($tag);
                $this->em->flush();
            }
            return $tag;
        } catch (\Exception $e) {
            $msg = 'get_tag_error: '.$e->getMessage().', '.$tag_name;
            $this->logger->error($msg);
            $this->discord->notify('error', $msg);
            echo $msg."\n";
            return false;
        }
    }

    public function getPost($author, $permlink)
    {
        return $this->em
                    ->getRepository(Posts::class)
                    ->findOneBy([
                        'author' => $author,
                        'permlink' => $permlink,
                    ]);
    }

    public function getComment($author, $permlink)
    {
        return $this->em
                    ->getRepository(Comments::class)
                    ->findOneBy([
                        'author' => $author,
                        'permlink' => $permlink,
                    ]);
    }

    public function getAuthor($author_name)
    {
        return $this->em
                        ->getRepository(Users::class)
                        ->findOneBy([
                            'username' => trim($author_name),
                        ]);
    }
    
    public function getPostsVotes($post, $voter)
    {
        return $this->em
                        ->getRepository(PostsVotes::class)
                        ->findOneBy([
                            'post' => $post,
                            'user' => $voter,
                        ]);
    }

    public function getCommentsVotes($comment, $voter)
    {
        return $this->em
                        ->getRepository(CommentsVotes::class)
                        ->findOneBy([
                            'comment' => $comment,
                            'user' => $voter,
                        ]);
    }

}
