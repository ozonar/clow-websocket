<?php

namespace App\Model;

use App\Model\Exceptions\GameCrashException;
use App\Model\Exceptions\PlayerCrashException;
use App\Model\Games\Diamant;
use App\Model\Games\Tictactoe;
use App\Model\Games\SvoyaIgra;
use App\Model\Players\DefaultPlayer;
use App\Repository\FramePagesRepository;
use Ratchet\ConnectionInterface;
use App\Model\Games\WorldString;

class ServerWork
{

    const GAME_TYPE_WORLDSTRING = 1;
    const GAME_TYPE_TICTACTOE = 2;
    const GAME_TYPE_DIAMAND = 3;
    const GAME_TYPE_SVOYAIGRA = 4;

    /** @var $games Game[] */
    private static $games = [];

    private $clientToGameConnect = [];
    private $framePagesRepository;

    const MESSAGE_TYPE_START_SERVER = 'srv';
    const MESSAGE_TYPE_CONNECT_TO_SERVER = 'con';
    const MESSAGE_TYPE_START_GAME = 'sta';
    const MESSAGE_TYPE_START_AND_CONNECT_TO_SERVER = 'stn';
    const MESSAGE_TYPE_TEXT_ONLY = 'msg';
    const MESSAGE_TYPE_SERVER_SEES_PLAYER = 'saw';

    /**
     * ServerWork constructor.
     * @param FramePagesRepository $framePagesRepository
     */
    public function __construct(FramePagesRepository $framePagesRepository)
    {
        $this->framePagesRepository = $framePagesRepository;
    }

    /**
     * Handle incoming messages
     *
     * @param $type
     * @param $messageArray
     * @param $from
     * @throws GameCrashException
     * @throws PlayerCrashException
     * @throws \Exception
     */
    public function handleMessage($type, $messageArray, $from)
    {
        $playerId = $messageArray['playerId'];

        switch ($type) {
            case self::MESSAGE_TYPE_START_SERVER:
                $this->handleStartServer($messageArray, $from);
                break;

            case self::MESSAGE_TYPE_CONNECT_TO_SERVER:
                $this->handleConnectToServer($messageArray, $from);
                break;

            case self::MESSAGE_TYPE_START_AND_CONNECT_TO_SERVER:
                $serverId = $this->handleStartServer($messageArray, $from);
                $messageArray['serverId'] = $serverId;

                $this->handleConnectToServer($messageArray, $from);
                break;

            case self::MESSAGE_TYPE_START_GAME:
                $this->handleStartGame($messageArray, $from);
                break;

            default:
                $game = $this->findGameByPlayerId($playerId);
                if ($game) {
                    $game->subHandleMessage($type, $messageArray);
                } else {
                    throw new GameCrashException('Game not exist', GameCrashException::GAME_NOT_EXIST);
                }
                break;
        }
    }

    /**
     * @param $messageArray
     * @param $from
     * @throws PlayerCrashException
     */
    private function handleConnectToServer($messageArray, $from)
    {

        $serverId = $messageArray['serverId'];
        $playerId = $messageArray['playerId'];

        $connectionPlayer = new DefaultPlayer($from, $playerId, 0);

        if (isset($messageArray['playerName'])) {
            $name = $messageArray['playerName'];
            $connectionPlayer->name = $name;
        }

        $result = ServerWork::tryingToConnectToServer($serverId, $connectionPlayer);

        if ($result) {
            $this->clientToGameConnect[$playerId] = $serverId;
        }
    }

    /**
     * @param $messageArray
     * @param $from
     * @return int
     * @throws GameCrashException
     */
    private function handleStartServer($messageArray, $from)
    {
        $gameType = $messageArray['gameType'];
        return ServerWork::startGameServer($gameType, $from);
    }

    /**
     * @param $messageArray
     * @param $from
     * @throws GameCrashException
     * @throws PlayerCrashException
     */
    private function handleStartGame($messageArray, $from)
    {
        $playerId = $messageArray['playerId'];

        if ($serverId = $this->clientToGameConnect[$playerId]) {
            $game = ServerWork::$games[$serverId];
            if ($game && $game->isPlayerHost($playerId)) {
                $game->checkAndStartGame($from);
            }
        }
    }

    /**
     * @param $playerId
     * @return bool|Game|Games\WorldString
     * @throws PlayerCrashException
     */
    private function findGameByPlayerId($playerId)
    {
        if ($serverId = $this->clientToGameConnect[$playerId]) {
            return ServerWork::$games[$serverId];
        } else {
            throw new PlayerCrashException('Can not find player game');
        }
    }

    /**
     * @param $gameType
     * @param ConnectionInterface $server
     * @return int
     * @throws GameCrashException
     */
    private function startGameServer($gameType, ConnectionInterface $server)
    {

        switch ($gameType) {
            case ServerWork::GAME_TYPE_WORLDSTRING:
                $game = new WorldString($gameType, $server, $this->framePagesRepository);
                break;
            case ServerWork::GAME_TYPE_TICTACTOE:
                $game = new Tictactoe($gameType, $server, $this->framePagesRepository);
                break;
            case ServerWork::GAME_TYPE_DIAMAND:
                $game = new Diamant($gameType, $server, $this->framePagesRepository);
                break;
            case ServerWork::GAME_TYPE_SVOYAIGRA:
                $game = new SvoyaIgra($gameType, $server, $this->framePagesRepository);
                break;
            default:
                throw new GameCrashException('Такой игры не существует. Сервер не создан', GameCrashException::GAME_TYPE_NOT_EXIST);
        }

//        $gameid = self::generateGameId();
        $gameid = 1; // todo Debug mode. Use code below

        $this->addGameToList($gameid, $game);

        Helper::sendJson($server, 'Чтобы подключиться, зайдите на <b>atata.ru</b>. Код комнаты: <b>"' . $gameid . '"</b>', ['type' => self::MESSAGE_TYPE_START_SERVER]);
        Helper::log(Helper::SECTION_SERVER, sprintf('Server %d started', $gameid));

        return $gameid;
    }

    private function addGameToList($id, $game)
    {
        self::$games[$id] = $game;
    }

    /**
     * @param $serverId
     * @param Player $connectionPlayer
     * @return bool
     * @throws PlayerCrashException
     */
    private function tryingToConnectToServer($serverId, Player $connectionPlayer)
    {
        /** @var Game $currentServer */
        $currentServer = self::$games[$serverId];
        if ($currentServer) {
            return $currentServer->connectPlayerToGame($connectionPlayer);
        } else {
            Message::create(ServerWork::MESSAGE_TYPE_TEXT_ONLY)->setTarget($connectionPlayer->connectionInterface)->setMessage('Такой игры не существует')->send();
            return false; // TODO Game not exist
        }
    }

    /**
     * @return int
     */
    private function generateGameId()
    {
        $gameId = rand(10000, 99999);

        if ((boolean)self::$games[$gameId]) {
            return self::generateGameId();
        }

        return $gameId;
    }
}