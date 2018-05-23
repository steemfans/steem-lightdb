<?php

namespace App\Command;
ini_set('memory_limit', '-1');

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;

use App\Service\UserManager;
use App\Service\CommentManager;
use App\Service\CustomJsonManager;
use App\Service\ConfigManager;
use App\Service\PowManager;
use App\Service\Discord;

class TransferRunCommand extends Command
{
    protected static $defaultName = 'transfer:run';
    private $conn;
    private $logger;
    private $discord;
    private $output;
    private $em;
    private $user_manager;
    private $comment_manager;
    private $custom_json_manager;
    private $config_manager;
    private $pow_manager;

    public function __construct(
                        LoggerInterface $logger,
                        UserManager $user_manager,
                        CommentManager $comment_manager,
                        CustomJsonManager $custom_json_manager,
                        ConfigManager $config_manager,
                        PowManager $pow_manager,
                        Discord $discord
                    )
    {
        parent::__construct();
        $this->logger = $logger;
        $this->user_manager = $user_manager;
        $this->comment_manager = $comment_manager;
        $this->custom_json_manager = $custom_json_manager;
        $this->config_manager = $config_manager;
        $this->pow_manager = $pow_manager;
        $this->discord = $discord;
        pcntl_signal(SIGTERM, array($this, 'handle'));
        pcntl_signal(SIGINT, array($this, 'handle'));
    }

    protected function configure()
    {
        $this
            ->setDescription('run the transfer shell')
            // ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('start_num', null, InputOption::VALUE_OPTIONAL, 'Start block number')
            ->addOption('sleep_time', null, InputOption::VALUE_OPTIONAL, 'sleep time', 3)
            ->addOption('step', null, InputOption::VALUE_OPTIONAL, 'step', 100)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $start_num = $input->getOption('start_num');
        if (!$start_num) {
            $start_num_stored = $this->config_manager->getConfig('current_head', false);
            if ($start_num_stored) {
                $start_num = (int)$start_num_stored + 1;
            } else {
                $start_num = 1;
            }
        }
        $sleep_time = $input->getOption('sleep_time');
        $step = $input->getOption('step');
        $debug = getenv('APP_ENV');
        $test_latest_block_num = getenv('test_latest_block_num');

        // connect chain db
        if (!getenv('CHAIN_DB')) {
            $this->logger->error('CHAIN_DB undefined');
            exit();
        }
        $chain_db = $this->parseDBStr(getenv('CHAIN_DB'));
        try {
            $this->conn = new \PDO(
                            "{$chain_db[1]}:host={$chain_db[4]};dbname={$chain_db[6]}",
                            $chain_db[2],
                            $chain_db[3],
                            array(\PDO::ATTR_PERSISTENT => true)
                        );
        } catch (\Exception $e) {
            $msg = sprintf('DB error: %s', $e->getMessage());
            $this->logger->error($msg);
            $this->discord->notify('error', $msg);
            exit();
        }

        $tmp = compact('start_num', 'sleep_time', 'step', 'debug', 'test_latest_block_num');
        $status_msg = '<info>'.json_encode($tmp).'</info>';
        $output->writeln($status_msg);
        $this->logger->info($status_msg);
        // main loop
        $last_block_num = $start_num;
        while (1) {
            if ($debug == 'prod') {
                $latest_block_num = $this->getLatestBlockNum();
            } else {
                $latest_block_num = $test_latest_block_num;
                $latest_block_num = $latest_block_num ? $latest_block_num : 10;
            }
            $msg = '<info>LatestBlockNum: '.$latest_block_num.'</info>';
            $output->writeln($msg);
            $this->logger->info($msg);

            if ($latest_block_num && ($latest_block_num - $last_block_num) > 0) {
                $output->writeln("<info>Get block from {$last_block_num} to {$latest_block_num}");
                $this->logger->info("<info>Get block from {$last_block_num} to {$latest_block_num}");
                $tmp_start = $last_block_num;
                while($tmp_start <= $latest_block_num) {
                    $err = false;
                    // group getting block task
                    $tmp_end = $tmp_start + $step;
                    if ($tmp_end >= $latest_block_num) {
                        $tmp_data = $this->getDataFromChain($tmp_start, $latest_block_num+1);
                        $output->writeln("<info>got [{$tmp_start}, ".($latest_block_num+1).")");
                    } else {
                        $tmp_data = $this->getDataFromChain($tmp_start, $tmp_end);
                        $output->writeln("<info>got [{$tmp_start}, {$tmp_end})");
                    }
                    switch ($tmp_data['status']) {
                        case 1:
                            $this->transferData($tmp_data['data']);
                            break;
                        case -1:
                            $this->logger->error('[error]get blocks: start > end');
                            $err = true;
                            break;
                        case -2:
                            $this->logger->error('[error]get blocks: length error');
                            $err = true;
                            break;
                        case -3:
                            $this->logger->error('[error]get blocks: block data wrong', $tmp_data['data']);
                            $err = true;
                            break;
                        default:
                            $this->logger->error('undefined status');
                            $err = true;
                            break;
                    }
                    if ($err) {
                        break;
                    }
                    $tmp_start = $tmp_end;
                }
            }
            if ($debug != 'prod') {
                $output->writeln('<info>Not Prod ENV</info>');
                exit();
            } else {
                $last_block_num = $latest_block_num;
                pcntl_signal_dispatch();
                sleep($sleep_time);
            }
        }
    }

    protected function transferData($data) {
        foreach($data as $k => $block) {
            $block_num = $block['block_num'];
            $block_info = $block['block_info'];
            $timestamp = strtotime($block['timestamp']);
            foreach($block['transactions'] as $kk => $transaction) {
                if (isset($block_info['transaction_ids'][$kk])) {
                    $transaction_id = $block_info['transaction_ids'][$kk];
                } else {
                    $transaction_id = $kk;
                }
                foreach($transaction['content']['operations'] as $kkk => $operation) {
                    $operation_index = $kkk;
                    // var_dump($block_num.' : '.$transaction_id.' : '.$operation[0]);
                    $op_data = compact(
                        'block_num',
                        'transaction_id',
                        'timestamp',
                        'operation'
                    );
                    switch($operation[0]) {
                        case 'pow':
                            $this->pow_manager->powHandle($op_data);
                            break;
                        case 'pow2':
                            $this->pow_manager->pow2Handle($op_data);
                            break;
                        case 'comment':
                            $this->comment_manager->handle($op_data);
                            break;
                        case 'comment_options':
                            break;
                        case 'delete_comment':
                            $this->comment_manager->delComment($op_data);
                            break;
                        case 'vote':
                            $this->comment_manager->voteComment($op_data);
                            break;
                        case 'custom_json':
                            $this->custom_json_manager->handle($op_data);
                            break;
                        case 'account_create':
                            $this->user_manager->addUser($op_data);
                            break;
                        case 'account_update':
                            $this->user_manager->updateUser($op_data);
                            break;
                        case 'limit_order_cancel':
                            break;
                        case 'limit_order_create':
                            break;
                        case 'transfer':
                            break;
                        case 'transfer_from_savings':
                            break;
                        case 'transfer_to_savings':
                            break;
                        case 'transfer_to_vesting':
                            break;
                        case 'convert':
                            break;
                        case 'withdraw_vesting':
                            break;
                        case 'witness_update':
                            break;
                        case 'feed_publish':
                            break;
                        case 'account_witness_vote':
                            break;
                    }
                }
            }
            $this->config_manager->setConfig('current_head', $block_num);
            pcntl_signal_dispatch();
        }
    }
    
    protected function parseDBStr($str)
    {
        preg_match('/([A-Za-z]+):\/\/([A-Za-z]+):(.+)@(.+):([0-9]+)\/([A-Za-z]+)/', $str, $matches);
        return $matches;
    }

    /**
     * [$start, $end)
     */
    protected function getDataFromChain($start, $end)
    {
        if ($start >= $end) {
            return ['status' => -1];
        }
        $this->output->writeln("<info>Will get blocks: [{$start}, {$end})</info>");
        $sql = "select * from blocks where block_num >= {$start} and block_num < {$end} order by block_num asc";
        $sth = $this->conn->prepare($sql);
        $sth->execute();
        $res = $sth->fetchAll(\PDO::FETCH_ASSOC);
        // var_dump($res);die();
        $length = $end - $start;
        if (count($res) < $length) {
            return ['status' => -2];
        }
        
        $tmp = $this->getBlocks([$start-1]);
        $last_block_id = $tmp ? $tmp[0]['block_id'] : '0000000000000000000000000000000000000000';

        $final = [];
        foreach($res as $k => $v) {
            if ($v['previous'] == $last_block_id) {
                $v['block_info'] = json_decode($v['block_info'], true);
                if (count($v['block_info']['transaction_ids']) > 0) {
                    $v['transactions'] = $this->getTransactions($v['block_num']);
                } else {
                    $v['transactions'] = [];
                }
                $final[$v['block_num']] = $v;
                $last_block_id = $v['block_id'];
            } else {
                return ['status' => -3, 'data' => [$v, $last_block_id]];
            }
        }
        return ['status' => 1, 'data' => $final];
    }

    protected function getBlocks($block_nums)
    {
        if (!is_array($block_nums) || count($block_nums) == 0) {
            return false;
        }
        $block_nums_str = implode(',', $block_nums);
        $sql = "select * from blocks where block_num in ({$block_nums_str}) order by block_num asc";
        $sth = $this->conn->prepare($sql);
        $sth->execute();
        $res = $sth->fetchAll(\PDO::FETCH_ASSOC);
        if (count($res) == 0) {
            return false;
        } else {
            return $res;
        }
    }

    protected function getTransactions($block_num)
    {
        $sql = "select * from transactions where block_num = {$block_num} order by id asc";
        $sth = $this->conn->prepare($sql);
        $sth->execute();
        $res = $sth->fetchAll(\PDO::FETCH_ASSOC);
        if (count($res) > 0) {
            foreach($res as $k => $v) {
                $res[$k]['content'] = json_decode($v['content'], true);
            }
            return $res;
        } else {
            return false;
        }
    }

    protected function getLatestBlockNum()
    {
        $sql = "select block_num from blocks order by block_num desc limit 1";
        $sth = $this->conn->prepare($sql);
        $sth->execute();
        $res = $sth->fetchAll(\PDO::FETCH_ASSOC);
        if ($res) {
            return $res[0]['block_num'];
        } else {
            return false;
        }
    }

    public function handle($signo)
    {
        $msg = 'exit success: '.$signo;
        $this->logger->info($msg);
        $this->discord->notify('info', $msg);
        $this->output->writeln('<info>'.$msg.'</info>');
        exit();
    }

}
