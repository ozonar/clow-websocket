<?php

namespace App\Model;

use Ratchet\ConnectionInterface;

class Helper
{

    const SECTION_RATCHET = 'rat';
    const SECTION_SERVER = 'srv';
    const SECTION_EXCEPTION = 'exc';
    const SECTION_DEBUG = 'dbg';

    /**
     * @param $section
     * @param mixed ...$messages
     */
    public static function log($section, ...$messages)
    {
        $resultMessage = $section . ' | ';

        foreach ($messages as $message) {

            if (is_array($message) || is_object($message)) {
//                print_r($message); TODO
            } else {
                $resultMessage .= $message . ' | ';
            }

        }

        echo $resultMessage . "\n";
    }

    /**
     * @param mixed ...$data
     */
    public static function errorLog(...$data)
    {
        foreach ($data as $datum) {
            var_dump($datum);
        }
    }

    /**
     * @param $target
     * @param string $message
     * @param array $params
     */
    public static function sendJson($target, $message = '', $params = [])
    {
        $params['message'] = $message;
        $jsonArray = json_encode($params);

        if (is_array($target)) {
            foreach ($target as $currentTarget) {
                if ($currentTarget instanceof ConnectionInterface) {
                    $currentTarget->send($jsonArray);
                } else {
                    Helper::errorLog('#1001 Current target is not instance of connection interface', $currentTarget);
                }
            }
        } else {
            /** @var $target ConnectionInterface */
            $target->send($jsonArray);
        }
    }

    /**
     * @param $target
     * @param string $message
     * @param array $params
     */
    public static function sendSockets($target, $message = '', $params = [])
    {
        $params['message'] = $message;
        $jsonArray = json_encode($params);

        if (is_array($target)) {
            foreach ($target as $currentTarget) {
                if ($currentTarget instanceof Player) {
                    $currentTarget->send($jsonArray);
                } else {
                    Helper::errorLog('#1001 Current target is not instance of connection interface', $currentTarget);
                }
            }
        } else {
            /** @var $target ConnectionInterface */
            $target->send($jsonArray);
        }
    }

    /**
     * @param $text
     * @return string
     */
    public static function mb_ucfirst($text)
    {
        return mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
    }

    /**
     * @param mixed ...$arrays
     * @return array
     */
    public static function array_merge_with_keys(...$arrays)
    {
        $bigArray = [];
        foreach ($arrays as $array) {
            foreach ($array as $key => $value) {
                $bigArray[$key] = $value;
            }
        }

        return $bigArray;
    }

}