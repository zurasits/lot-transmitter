<?php

namespace LotTransmitter;

use DateTime;

/**
 * Class Logger
 *
 * PHP Version 7
 *
 * @category  PHP
 * @package   LotTransmitter
 * @author    privatrepo Trade GmbH <development@privatrepo.ag>
 * @copyright 2016-2019 privatrepo Trade GmbH
 * @license   Proprietary http://www.privatrepo.ag
 */
class Logger
{
    public const LEVEL_EMERGENCY = 'emergency';
    public const LEVEL_ALERT     = 'alert';
    public const LEVEL_CRITICAL  = 'critical';
    public const LEVEL_ERROR     = 'error';
    public const LEVEL_WARNING   = 'warning';
    public const LEVEL_NOTICE    = 'notice';
    public const LEVEL_INFO      = 'info';
    public const LEVEL_DEBUG     = 'debug';

    /**
     * System is unusable.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public static function emergency($message, array $context = array()): void
    {
        self::log(self::LEVEL_EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public static function alert($message, array $context = array()): void
    {
        self::log(self::LEVEL_ALERT, $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public static function critical($message, array $context = array()): void
    {
        self::log(self::LEVEL_CRITICAL, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public static function error($message, array $context = array()): void
    {
        self::log(self::LEVEL_ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public static function warning($message, array $context = array()): void
    {
        self::log(self::LEVEL_WARNING, $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public static function notice($message, array $context = array()): void
    {
        self::log(self::LEVEL_NOTICE, $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public static function info($message, array $context = array()): void
    {
        self::log(self::LEVEL_INFO, $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public static function debug($message, array $context = array()): void
    {
        self::log(self::LEVEL_DEBUG, $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param string $levelName
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public static function log($levelName, $message, array $context = array()): void
    {
        $levelMapping = [
            self::LEVEL_ALERT => 550,
            self::LEVEL_CRITICAL => 500,
            self::LEVEL_EMERGENCY => 600,
            self::LEVEL_ERROR => 400,
            self::LEVEL_WARNING => 300,
            self::LEVEL_NOTICE => 250,
            self::LEVEL_INFO => 200,
            self::LEVEL_DEBUG => 100,
        ];

        echo json_encode([
            'message'    => $message,
            'context'    => $context,
            'level'      => $levelMapping[$levelName],
            'level_name' => $levelName,
            'dateTime'   => new DateTime(),
        ]). "\n";
    }
}

