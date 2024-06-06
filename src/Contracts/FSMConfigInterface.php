<?php

namespace Gecche\FSM\Contracts;

interface FSMConfigInterface
{
    /**
     * @return array
     */
    public static function states();

    /**
     * @return string|null
     */
    public static function root();

    /**
     * @return array
     */
    public static function groups();

    /**
     * @return array
     */
    public static function transitions();


}