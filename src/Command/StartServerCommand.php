<?php

namespace App\Command;

use App\Model\Notification;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTManager;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

// the name of the command is what users type after "php bin/console"
#[AsCommand(name: 'app:start-server')]
class StartServerCommand extends Command
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        $this->container = $container;
    }

    /**
     * Configure a new Command Line
     */
    protected function configure()
    {
        $this
            ->setName('Project:notification:server')
            ->setDescription('Start the notification server.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $server = IoServer::factory(new HttpServer(
            new WsServer(
                new Notification($this->container)
            )
        ), 7070);

        $server->run();

        return 1;
    }
}