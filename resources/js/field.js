Nova.booting((Vue, router, store) => {
    Vue.component(
        "index-here-api-map-field",
        require("./components/IndexField")
    );
    Vue.component(
        "detail-here-api-map-field",
        require("./components/DetailField")
    );
    Vue.component("form-here-api-map-field", require("./components/FormField"));
    Vue.config.devtools = true;
});
