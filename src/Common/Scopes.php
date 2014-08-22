<?php

namespace Aquicore\API\PHP\Common;

class Scopes
{
    const SCOPE_READ_STATION = 'read_station';
    const SCOPE_READ_THERM   = 'read_thermostat';
    const SCOPE_WRITE_THERM  = 'write_thermostat';

    static $validScopes = array(
        Scopes::SCOPE_READ_STATION,
        Scopes::SCOPE_WRITE_STATION,
        Scopes::SCOPE_READ_THERM,
        Scopes::SCOPE_WRITE_THERM,
    );
}
