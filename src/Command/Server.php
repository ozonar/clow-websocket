<?php

namespace App\Command;

use App\Model\Helper;
use App\Model\RatchetServer;
use App\Model\ShellRunner;
use Ratchet\MessageComponentInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

#[AsCommand(name: 'app:server')]
class Server extends Command
{
    protected function configure()
    {
        $this->setDescription('Game server')
            ->addOption('runner', 'r', InputOption::VALUE_NONE, '', null);
    }

    private $container;
    private $ratchetServer;

    public function __construct(ContainerInterface $container, RatchetServer $ratchetServer)
    {
        parent::__construct();
        $this->container = $container;
        $this->ratchetServer = $ratchetServer;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $runInRunner = $input->getOption('runner');

        if ($runInRunner) {
            $runner = new ShellRunner('bin/console app:server');
            $runner->run();
            return 0;
        }

        $output->writeln("Starting websockert server");
        try {
            $this->startServer();
        } catch (\Exception $e) {
            $output->writeln("<error>Server crashed</error>");
            Helper::errorLog($e);
        }

        return 1;
    }

    protected function startServer()
    {
        /** @var MessageComponentInterface $ratchetServer */

        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    $this->ratchetServer
                )
            ),
            9090
        );

        $server->run();
    }


}