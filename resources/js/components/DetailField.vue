<template>
  <div class="border-40 border-b mt-4 mb-4  pb-8">
    <h4 class="font-normal text-80 my-2">{{ this.field.name }}</h4>
    <div
      v-show="!error"
      ref="map"
      class="rounded-sm"
      style="height: 600px; width: 100%"
    ></div>
    <div v-show="distance" class="mt-4">
      <b>Distance : {{ distance }} Km</b>
    </div>
    <div v-show="estimated_time">
      <b>Estimated Time : {{ toMMSS(estimated_time) }}</b>
    </div>
    <div v-show="route_instructions.length > 0">
      <ul class="h-60 overflow-auto ">
        <li v-for="(ri, index) in route_instructions">
          {{ index + 1 }}- {{ ri }}
        </li>
      </ul>
    </div>
  </div>
</template>

<script>
import H from "@here/maps-api-for-javascript";
import "@here/maps-api-for-javascript/bin/mapsjs-ui.css";
import Axios from "axios";

export default {
  props: ["resource", "resourceName", "resourceId", "field"],
  data() {
    return {
      map: null,
      route_instructions: [],
      markers: [],
      distance: null,
      estimated_time: null,
      gps_points: [],
      intervalId: null,
    };
  },
  methods: {
    init(platform) {
      // departure point (origin).
      var start = this.gps_points[0].lat + "," + this.gps_points[0].lng;

      // waypoints.
      var waypoints = this.gps_points
        .slice(1, -1)
        .map((element) => element.lat + "," + element.lng);

      // end point (destination)
      var end =
        this.gps_points[this.gps_points.length - 1].lat +
        "," +
        this.gps_points[this.gps_points.length - 1].lng;

      // routing parameters
      var routingParameters = {
        origin: start,
        destination: end,
        via: new H.service.Url.MultiValueQueryParameter(waypoints),
        routingMode: this.field.type ? this.field.type : "fast",
        transportMode: this.field.Transport_mode
          ? this.field.Transport_mode
          : "car",
        traffic: this.field.traffic_mode ? this.field.traffic_mode : "enabled",
        return: "polyline,turnByTurnActions,actions,instructions,travelSummary",
      };

      // Get an instance of the routing service version 8:
      var router = platform.getRoutingService(null, 8);

      // Call `calculateRoute` with the routing parameters,
      // the success callback and an error callback function
      // The implementation of the two callback functions is left out for brevity
      // see documentation link below for callback examples
      router.calculateRoute(routingParameters, this.onSuccess, this.onError);
    },
    onSuccess(result) {
      /*
       * The styling of the route response on the map is entirely under the developer's control.
       * A representitive styling can be found the full JS + HTML code of this example
       * in the functions below:
       */
      this.addRouteShapeToMap(result);

      this.addMarkers();

      this.addCirles();

      this.addInstructions(result);

      this.addDistance(result);

      this.addEstimatedTravelTime(result);

      this.refreshMarkersPosistion();
    },
    onError(error) {
      alert("Can't reach the remote server");
    },

    /**
     * Add marker popup message.
     */
    openBubble(position, text) {
      if (!this.bubble) {
        this.bubble = new H.ui.InfoBubble(
          position,
          // The FO property holds the province name.
          { content: text }
        );
        this.ui.addBubble(this.bubble);
      } else {
        this.bubble.setPosition(position);
        this.bubble.setContent(text);
        this.bubble.open();
      }
    },

    /**
     * this will draw the route line in the map.
     *
     */
    addRouteShapeToMap(result) {
      let draw_route =
        this.field.draw_route !== undefined ? this.field.draw_route : true;
      if (result.routes.length && draw_route && this.gps_points.length > 1) {
        result.routes[0].sections.forEach((section, index) => {
          this.addRouteSectionShape(section);
        });
      }
    },

    /**
     * this will draw the route section line in the map.
     *
     */
    addRouteSectionShape(section) {
      // Create a linestring to use as a point source for the route line
      let linestring = H.geo.LineString.fromFlexiblePolyline(section.polyline);

      // Create a polyline to display the route:
      let routeLine = new H.map.Polyline(linestring, {
        style: {
          strokeColor:
            this.field.route_line_color !== undefined
              ? this.field.route_line_color
              : "blue",
          lineWidth:
            this.field.route_line_width !== undefined
              ? this.field.route_line_width
              : 5,
        },
      });

      // Add the route polyline and the two markers to the map:
      this.map.addObjects([routeLine]);

      // Set the map's viewport to make the whole route visible:
      this.map
        .getViewModel()
        .setLookAtData({ bounds: routeLine.getBoundingBox() });

      this.setMapInitPosition();
    },

    /**
     * This function will add markers to the map.
     *
     */
    addMarkers() {
      var group = new H.map.Group(),
        i,
        j;

      // first let's filter markers that user want to hide via show_marker attribute by default it will be true.
      this.gps_points
        .filter((gps_point) => {
          gps_point = gps_point;
          return gps_point.show_marker === false ? false : true;
        })
        .forEach((gps_point) => {
          gps_point = gps_point;
          var defaultMarkerIcon = this.field.defaultMarkerSvg
              ? this.field.defaultMarkerSvg
              : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="orange"><path d="M10 20S3 10.87 3 7a7 7 0 1 1 14 0c0 3.87-7 13-7 13zm0-11a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg>',
            markerIcon = new H.map.Icon(
              gps_point.svg ? gps_point.svg : defaultMarkerIcon,
              { size: { w: 56, h: 56 } }
            );
          var marker = new H.map.Marker(
            {
              lat: gps_point.lat,
              lng: gps_point.lng,
            },
            {
              icon: markerIcon,
            }
          );
          marker.instruction = gps_point.description;

          // Let's save this marker to the markers array if the user want to update the postion frequently.
          if (this.field.updateUrl) {
            this.markers[gps_point.id] = marker;
          }

          group.addObject(marker);
          group.addEventListener(
            "tap",
            function(evt) {
              this.map.setCenter(evt.target.getGeometry());
              this.openBubble(
                evt.target.getGeometry(),
                evt.target.instruction,
                this.ui
              );
            }.bind(this),
            false
          );
        });
      this.map.addObject(group);
    },

    /**
     * Add a radius circle to the map.
     */
    addCirles() {
      this.field.circles.forEach((circle) => {
        if (circle.lat && circle.lng && circle.radius) {
          this.map.addObject(
            new H.map.Circle(
              // The central point of the circle
              {
                lat: circle.lat,
                lng: circle.lng,
              },
              // The radius of the circle in meters
              circle.radius,
              {
                style: {
                  strokeColor:
                    circle.border_color !== undefined
                      ? circle.border_color
                      : "rgba(0, 0, 255, 0.1)", // Color of the perimeter
                  lineWidth:
                    circle.border_width !== undefined ? circle.border_width : 5,
                  fillColor:
                    circle.color !== undefined
                      ? circle.color
                      : "rgba(0, 0, 255, 0.1)", // Color of the circle
                },
              }
            )
          );
        }
      });
    },

    /**
     * Add a list of actions under the map.
     */
    addInstructions(result) {
      if (
        result.routes.length > 0 &&
        this.field.showInstructions &&
        this.gps_points.length > 1
      ) {
        result.routes[0].sections.forEach((section) => {
          section.actions.forEach((action) => {
            this.route_instructions.push(action.instruction);
          });
        });
      }
    },

    /**
     * Calculate the route distance.
     */
    addDistance(result) {
      if (this.field.showDistance && this.gps_points.length > 1) {
        result.routes[0].sections.forEach((section) => {
          this.distance += section.travelSummary.length / 1000;
        });
      }
    },

    /**
     * Calculate the estimated travel time.
     */
    addEstimatedTravelTime(result) {
      if (this.field.showTime && this.gps_points.length > 1) {
        result.routes[0].sections.forEach((section) => {
          this.estimated_time += section.travelSummary.duration;
        });
      }
    },

    /**
     * Convert to humain readble time.
     */
    toMMSS(time) {
      return Math.floor(time / 60) + " minutes " + (time % 60) + " seconds.";
    },

    /**
     * Set the initial map position and zoom.
     */
    setMapInitPosition() {
      this.map.setCenter({
        lat:
          this.field.center_lat !== undefined
            ? this.field.center_lat
            : this.gps_points[0].lat,
        lng:
          this.field.center_lng !== undefined
            ? this.field.center_lng
            : this.gps_points[0].lng,
      });
      this.map.setZoom(this.field.zoom !== undefined ? this.field.zoom : 14);
    },

    /**
     * Refresh marker position.
     */
    refreshMarkersPosistion() {
      {
        if (this.field.updateUrl) {
          this.intervalId = setInterval(() => {
            Axios.get(this.field.updateUrl)
              .then((result) => {
                result.data.forEach((p) => {
                  var marker = this.markers[p.id];
                  if (marker) {
                    this.ease(
                      marker.getGeometry(),
                      { lat: p.lat, lng: p.lng },
                      4000,
                      function(p) {
                        marker.setGeometry({
                          lat: p.lat,
                          lng: p.lng,
                        });
                      }
                    );
                    //marker.setGeometry({ lat: p.lat, lng: p.lng });
                  }
                  //console.log("Call BACK-END");
                });
              })
              .catch((err) => {
                console.log(err);
              });
          }, this.field.time);
        }
      }
    },

    /**
     * Here we make sure that we are not calling the back-end when the tab is hidden.
     */
    intervalEvent() {
      if (this.intervalId) {
        if (document.hidden) {
          clearInterval(this.intervalId);
        } else {
          this.refreshMarkersPosistion();
        }
      }
    },

    /**
     * Ease function add animation to the market when its moving position.
     * @param   {H.geo.IPoint} startCoord   start geo coordinate
     * @param   {H.geo.IPoint} endCoord     end geo coordinate
     * @param   number durationMs           duration of animation between start & end coordinates
     * @param   function onStep             callback executed each step
     * @param   function onStep             callback executed at the end
     */
    ease(
      startCoord = { lat: 0, lng: 0 },
      endCoord = { lat: 1, lng: 1 },
      durationMs = 200,
      onStep = console.log,
      onComplete = function() {}
    ) {
      var raf =
          window.requestAnimationFrame ||
          function(f) {
            window.setTimeout(f, 16);
          },
        stepCount = durationMs / 16,
        valueIncrementLat = (endCoord.lat - startCoord.lat) / stepCount,
        valueIncrementLng = (endCoord.lng - startCoord.lng) / stepCount,
        sinValueIncrement = Math.PI / stepCount,
        currentValueLat = startCoord.lat,
        currentValueLng = startCoord.lng,
        currentSinValue = 0;

      function step() {
        currentSinValue += sinValueIncrement;
        currentValueLat +=
          valueIncrementLat * Math.sin(currentSinValue) ** 2 * 2;
        currentValueLng +=
          valueIncrementLng * Math.sin(currentSinValue) ** 2 * 2;

        if (currentSinValue < Math.PI) {
          onStep({ lat: currentValueLat, lng: currentValueLng });
          raf(step);
        } else {
          onStep(endCoord);
          onComplete();
        }
      }

      raf(step);
    },
  },
  mounted() {
    if (this.field.gpsPoints.length < 1) {
      alert("Please provide at least one gps point.");
      return false;
    }
    // Let's convert the array elements to json objects.
    this.gps_points = this.field.gpsPoints;

    this.routeInstructionsContainer = this.$refs.panel;

    var platform = new H.service.Platform({
      apikey: this.field.globalApiKey || this.field.apiKey,
    });

    var defaultLayers = platform.createDefaultLayers();

    var map = new H.Map(this.$refs.map, defaultLayers.vector.normal.map, {
      // initial center and zoom level of the map
      center: new H.geo.Point(this.gps_points[0].lat, this.gps_points[0].lng),
      zoom: this.field.zoom !== undefined ? this.field.zoom : 14,
      pixelRatio: window.devicePixelRatio || 1,
    });

    // add a resize listener to make sure that the map occupies the whole container
    window.addEventListener("resize", () => map.getViewPort().resize());

    this.map = map;

    //add abolity to zoom in out wen scrolling in the map.
    new H.mapevents.Behavior(new H.mapevents.MapEvents(map));

    // Create the default UI components
    this.ui = H.ui.UI.createDefault(this.map, defaultLayers);

    this.init(platform);

    //Here we make sure that we are not calling the back-end when the tab is hidden.
    document.addEventListener("visibilitychange", this.intervalEvent);
  },
};
</script>
