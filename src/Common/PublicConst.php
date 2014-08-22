<?php

namespace Aquicore\API\PHP\Common;

class PublicConst
{
    const UNIT_METRIC            = 0;
    const UNIT_US                = 1;
    const UNIT_TYPE_NUMBER       = 2;

    const UNIT_WIND_KMH          = 0;
    const UNIT_WIND_MPH          = 1;
    const UNIT_WIND_MS           = 2;
    const UNIT_WIND_BEAUFORT     = 3;
    const UNIT_WIND_KNOT         = 4;
    const UNIT_WIND_NUMBER       = 5;

    const FEEL_LIKE_HUMIDEX_ALGO = 0;
    const FEEL_LIKE_HEAT_ALGO    = 1;
    const FEEL_LIKE_NUMBER       = 2;

    const KIND_READ_TIMELINE     = 0;
    const KIND_NOT_READ_TIMELINE = 1;
    const KIND_BOTH_TIMELINE     = 2;
}
