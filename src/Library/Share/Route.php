<?php
namespace Wolfans\Command;

class Route {
    const END_TAG   = '_END_TAG';
    const START_TAG = 'START_TAG_';

    public function __construct() {
    }

    public function encodeRouteId($routeId) {
        return self::START_TAG . $routeId . self::END_TAG;
    }

    public function decodeRouteId($routeId) {
        return mb_substr($routeId, mb_strlen(self::START_TAG), -(mb_strlen(self::END_TAG)));
    }
}