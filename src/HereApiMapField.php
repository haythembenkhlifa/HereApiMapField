<?php

namespace Haythem\HereApiMapField;

use Laravel\Nova\Fields\Field;

class HereApiMapField extends Field
{
    public function __construct($name, $attribute = null, callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);
        $this->component = 'here-api-map-field';
        $this->rules([]);
        $this->setGlobalApiKey(!empty(config('here-api-map-field.global_api_key')) ?
            config('here-api-map-field.global_api_key')
            :
            null);
        $this->fillUsing(function () {
            return null;
        });
    }



    /**
     * Set zoom level and initial postion.
     * 
     * @param mixed $center_lat 
     * @param mixed $center_lng 
     * @param int $zoom
     * @return $this 
     */
    public function setCenterAndZoom($center_lat, $center_lng, $zoom = 14)
    {
        return $this->withMeta([
            'center_lat' => $center_lat,
            'center_lng' => $center_lng,
            'zoom' => $zoom
        ]);
    }



    /**
     * Set a global api key if this key is specified on config that means it will override all apikey specified on the field level.
     * 
     * @param mixed $api_key 
     * @return $this 
     */
    private function setGlobalApiKey($api_key)
    {
        return $this->withMeta(['globalApiKey' => $api_key]);
    }



    /**
     * Api key for this map instance.
     *
     * @var string
     */
    public $component = 'route-map-field';

    public function apiKey($apikey)
    {
        return $this->withMeta(['apiKey' => $apikey]);
    }



    /**
     * Add the gps points.
     *
     * @param mixed $gpsPoints [
     *                  json_encode([lat=>location_lat,lng=>location_lng,"svg"=>"svg marker if you want to update the default",show_marker=>false])
     *                  json_encode([lat=>location_lat,lng=>location_lng])
     *              ]
     * @param mixed $updateUrl Url to update the markers position this end point should return the same data structure of the gps points. 
     * @param int $time Frequence of updating the markers position in ms.
     * @return $this 
     */
    public function gpsPoints($gps_points, $update_url = null, $time = 10000)
    {
        return $this->withMeta([
            'gpsPoints' => $gps_points,
            'updateUrl' => $update_url,
            'time' => $time
        ]);
    }



    /**
     * Show the distance of the route.
     * 
     * @param bool $show_distance 
     * @return $this 
     */
    public function showDistance($show_distance = true)
    {
        return $this->withMeta(['showDistance' => $show_distance]);
    }



    /**
     * Show the estimated time.
     * 
     * @param bool $show_time
     * @return $this 
     */
    public function showTime($show_time = true)
    {
        return $this->withMeta(['showTime' => $show_time]);
    }



    /**
     * Add a cirles.
     * 
     * @param array $circles
     * @return $this 
     */
    public function addCircles($circles = [])
    {
        return $this->withMeta([
            'circles' => $circles,
        ]);
    }



    /**
     * Show instructions.
     * 
     * @param bool $show_instructions 
     * @return $this 
     */
    public function showInstructions($show_instructions = true)
    {
        return $this->withMeta(['showInstructions' => $show_instructions]);
    }



    /**
     * Set up route setting.
     * 
     * @param string $route_line_color 
     * @param int $route_line_width 
     * @param bool $draw_route 
     * @param string $type 
     * @param string $Transport_mode 
     * @param string $Traffic_mode 
     * @return $this 
     */
    public function routeParameter(
        $route_line_color = "orange",
        $route_line_width = 5,
        $draw_route = true,
        $type = "fast",
        $Transport_mode = "car",
        $Traffic_mode = "default"
    ) {
        return $this->withMeta([
            'draw_route' => $draw_route,
            'type' => $type,
            'transport_mode' => $Transport_mode,
            'traffic_mode' => $Traffic_mode,
            'route_line_color' => $route_line_color,
            'route_line_width' => $route_line_width,
        ]);
    }
}
