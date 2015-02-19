<?php

/**
 * pjdietz\WellRESTed\Interfaces\Route\PrefixRouteInterface
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2015 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed\Interfaces\Routes;

interface PrefixRouteInterface
{
    /**
     * Returns the target class this maps to.
     *
     * @return string Fully qualified name for a HandlerInterface
     */
    public function getHandler();

    /**
     * Returns the path prefixes this maps to a target handler.
     *
     * @return array Array of path prefixes.
     */
    public function getPrefixes();
}