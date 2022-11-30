<?php

namespace App\Model;

use App\Model\Exceptions\GameCrashException;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class RatchetServer implements MessageComponentInterface
{
    protected $clients;

    /**
     * RatchetServer constructor.
     * @param ServerWork $serverWork
     */
    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
    }

    /**
     * @param ConnectionInterface $connection
     */
    public function onOpen(ConnectionInterface $connection)
    {
        // Store the new connection to send messages to later
        $this->clients->attach($connection);

        Helper::sendJson($connection, 'Open message');
        Helper::log(Helper::SECTION_RATCHET, "New connection! ({$connection->resourceId})");
//        gc_collect_cycles();
    }

    /**
     * @param ConnectionInterface $from
     * @param string $msg
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        $messages = json_decode($msg, true);

        foreach ($messages as $messageArray) {
            Helper::sendJson($from, 'Message' . $messageArray);
            Helper::log(Helper::SECTION_SERVER, sprintf('Message'));
        }
    }



    /**
     * @param ConnectionInterface $connection
     */
    public function onClose(ConnectionInterface $connection)
    {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($connection);

        echo "Connection {$connection->resourceId} has disconnected\n";
    }

    /**
     * @param ConnectionInterface $conn
     * @param \Exception $e
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();


    }


}
