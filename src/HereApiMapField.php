<?php

namespace Haythem\HereApiMapField;

use Laravel\Nova\Fields\Field;

class HereApiMapField extends Field
{

    private $internalGpsPoints = [];
    private $internalGpsCircles = [];


    public function __construct($name, $attribute = null, callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);
        $this->component = 'here-api-map-field';
        $this->rules([]);
        $this->setGlobalApiKey(!empty(config('here-api-map-field.global_api_key')) ?
            config('here-api-map-field.global_api_key')
            :
            null);
        $this->setDefaultMarkerSvg(!empty(config('here-api-map-field.default_marker_svg')) ?
            config('here-api-map-field.default_marker_svg')
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
    public function apiKey($apikey)
    {
        return $this->withMeta(['apiKey' => $apikey]);
    }



    private function setGlobalApiKey($api_key)
    {
        return $this->withMeta(['globalApiKey' => $api_key]);
    }


    private function setDefaultMarkerSvg($svg)
    {
        return $this->withMeta(['defaultMarkerSvg' => $svg]);
    }



    /**
     * Api key for this map instance.
     *
     * @var string
     */
    public $component = 'route-map-field';




    /**
     * Add the gps points.
     *
     * @param mixed $gpsPoints [
     *                  [lat=>location_lat,lng=>location_lng,"svg"=>"svg marker if you want to update the default",show_marker=>false]
     *                  [lat=>location_lat,lng=>location_lng]
     *              ]
     * @param mixed $updateUrl Url to update the markers position this end point should return the same data structure of the gps points. 
     * @param int $time Frequence of updating the markers position in ms.
     * @return $this 
     */
    public function addGpsPoints($gps_points)
    {
        $this->internalGpsPoints = array_merge($gps_points, $this->internalGpsPoints);
        return $this->withMeta(["gpsPoints" => $this->internalGpsPoints]);
    }



    /**
     * Add gps point.
     * 
     * @param mixed $lat 
     * @param mixed $lng 
     * @param string $description 
     * @param mixed $svg 
     * @param bool $show_marker 
     * @return $this 
     */
    public function addGpsPoint($id, $lat, $lng, $description = "", $svg = null, $show_marker = true)
    {
        $this->internalGpsPoints[] = [
            "id" => $id,
            "lat" => $lat,
            "lng" => $lng,
            "description" => $description,
            "svg" => $svg,
            "show_marker" => $show_marker
        ];
        return $this->addGpsPoints([]);
    }



    /**
     * Add a cirles.
     * 
     * @param array $circles
     * @return $this 
     */
    public function addCircles($circles)
    {
        $this->internalGpsCircles = array_merge($circles, $this->internalGpsCircles);
        return $this->withMeta(["circles" => $this->internalGpsCircles]);
    }




    /**
     * Add a cirle.
     * 
     * @param mixed $lat 
     * @param mixed $lng 
     * @param int $radius 
     * @param string $color 
     * @param string $border_color 
     * @param int $border_width 
     * @return $this 
     */
    public function addCircle($lat, $lng, $radius = 1000, $color = "rgba(255, 87, 34, 0.5)", $border_color = "rgba(0,0,0,1)", $border_width = 1)
    {

        $this->internalGpsCircles[] = [
            "lat" => $lat,
            "lng" => $lng,
            "radius" => $radius,
            "color" => $color, //oprional
            "border_color" => $border_color, //oprional
            "border_width" => $border_width //oprional
        ];
        return $this->addCircles([]);
    }




    /**
     * Refresh the gps points via a url.
     * 
     * @param mixed $from_url 
     * @param mixed $interval
     * @return $this 
     */
    public function autoRefreshPoints($from_url, $interval = 5000)
    {
        return $this->withMeta([
            'updateUrl' => $from_url,
            'time' => $interval
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
