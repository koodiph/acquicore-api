<?php

namespace Aquicore\API\PHP\Api;

/**
 * API Helpers
 */
class Helper
{
    public $client;
    public $devices = array();

    public function __construct($client)
    {
        $this->client = $client;
    }

    public function api($method, $action, $params = array())
    {
        if(isset($this->client))
        {
            return $this->client->api($method, $action, $params);
        }
        else
        {
            return NULL;
        }
    }

    public function simplifyDeviceList($app_type = "app_station")
    {
        $this->devices = $this->client->api('devicelist', 'POST', array('app_type' => $app_type));
        foreach ($this->devices['devices'] as $d => $device)
        {
            $moduledetails = array();
            foreach ($device['modules'] as $module)
            {
                foreach ($this->devices['modules'] as $moduledetail)
                {
                    if ($module == $moduledetail['_id'])
                    {
                        $moduledetails[] = $moduledetail;
                    }
                }
            }
            unset($this->devices['devices'][$d]['modules']);
            $this->devices['devices'][$d]['modules']=$moduledetails;
        }
        unset($this->devices['modules']);
        return($this->devices);
    }

    public function getMeasure($device, $device_type, $date_begin, $module = null, $module_type = null)
    {
        $params = array(
            'scale'      => 'max',
            'date_begin' => $date_begin,
            'date_end'   => $date_begin + 5 * 60,
            'device_id'  => $device
        );
        $result = array();
        if (!is_null($module))
        {
            switch ($module_type)
            {
                case 'NAModule1':
                    $params['type'] = 'Temperature,Humidity';
                break;
                case 'NAModule4':
                    $params['type'] = 'Temperature,CO2,Humidity';
                break;
                case 'NAModule3':
                    $params['type'] = 'Rain';
                break;
            }

            $params['module_id'] = $module;
        }
        else
        {
            switch ($device_type)
            {
                case 'NAMain':
                    $params['type'] = 'Temperature,CO2,Humidity,Pressure,Noise';
                break;
                case 'NAPlug':
                    $params['type'] = 'Temperature,Sp_Temperature,BoilerOn,BoilerOff';
                break;
            }
        }
        $types = explode(',', $params['type']);
        if ($types === FALSE)
        {
            $types = array($params['type']);
        }
        $meas = $this->client->api('getmeasure', 'POST', $params);
        if (isset($meas[0]))
        {
            $result['time'] = $meas[0]['beg_time'];
            foreach ($meas[0]['value'][0] as $key => $val)
            {
                $result[$types[$key]] = $val;
            }
        }
        return($result);
    }

    public function getLastMeasures()
    {
        $results = array();
        foreach ($this->devices['devices'] as $device)
        {
            $result = array();
            if (isset($device['station_name']))
            {
                $result['station_name'] = $device['station_name'];
            }
            if (isset($device['modules'][0]))
            {
                $result['modules'][0]['module_name'] = $device['module_name'];
            }
            $result['modules'][0] = array_merge($result['modules'][0], $device['dashboard_data']);
            foreach ($device['modules'] as $module)
            {
                $addmodule = array();
                if(isset($module['module_name']))
                {
                    $addmodule['module_name'] = $module['module_name'];
                }
                $addmodule = array_merge($addmodule, $module['dashboard_data']);
                $result['modules'][] = $addmodule;
            }
            $results[] = $result;
        }
        return $results;
    }

    public function getAllMeasures($date_begin)
    {
        $results = array();
        foreach ($this->devices['devices'] as $device)
        {
            $result = array();
            if (isset($device['station_name']))
            {
                $result['station_name'] = $device['station_name'];
            }
            if (isset($device['modules'][0]))
            {
                $result['modules'][0]['module_name'] = $device['module_name'];
            }
            $result['modules'][0] = array_merge(
                $result['modules'][0],
                $this->getMeasure($device['_id'], $device['type'], $date_begin)
            );
            foreach ($device['modules'] as $module)
            {
                $addmodule = array();
                if(isset($module['module_name']))
                {
                    $addmodule['module_name'] = $module['module_name'];
                }
                $addmodule = array_merge(
                    $addmodule,
                    $this->getMeasure(
                        $device['_id'],
                        $device['type'],
                        $date_begin,
                        $module['_id'],
                        $module['type']
                    )
                );
                $result['modules'][] = $addmodule;
            }
            $results[] = $result;
        }
        return $results;
    }
}
