# ARCHE OpenAIRE tracking plugin

An [arche-core](https://github.com/acdh-oeaw/arche-core) plugin implementing [usage tracking trough the OpenAIRE](https://openaire.github.io/usage-statistics-guidelines/service-specification/service-spec/).

## Installation

* Install with:
  ```
  composer require acdh-oeaw/arche-openaire
  ```
* Adjust your repository `config.yaml` so it contains the `openaire` section and defines `get` and `getMetadata` handlers:
  ```yaml
  openaire:
    # OpenAIRE tracker URL
    url: "https://analytics.openaire.eu/piwik.php"
    # id of the repository in the OpenAIRE tracker
    id: 100
    # OpenAIRE auth token
    authToken: "OpenAIRETrackerAuthToken"
    # should client IP be tracked? (true/false)
    trackIp: false
    # should client's user agent header be tracked? (true/false)
    trackUserAgent: true
    # template for the urlref tracking parameter
    # may contain {baseUrl} and {id} placeholders which are substituted with
    # arche repository base URL and internal repository resource id, respectively
    urlref: "{baseUrl}../browser/oeaw_detail/{id}"
  rest:
    handlers:
      methods:
        get:
        - type: function
          function: \acdhOeaw\arche\openaire\Handlers::onGet
        getMetadata:
        - type: function
          function: \acdhOeaw\arche\openaire\Handlers::onGetMetadata
  ```
