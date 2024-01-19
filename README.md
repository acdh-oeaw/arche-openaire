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
    # If the PID reported to the OpenAIRE tracker trough the cvar parameter
    # is not the PID of the resource, you should specify an SQL query fetching it.
    # Otherwise skip this property or set it to an empty value.
    pidQuery: |
      SELECT m.value 
      FROM 
        metadata m 
        JOIN relations r ON r.target_id = m.id AND r.id = ? AND r.property = ?
    # pidQuery parameters. Use "{id}" to pass repository resource id
    pidQueryParam:
    - "{id}"
    - https://vocabs.acdh.oeaw.ac.at/schema#isPartOf
    # Tracking API connection timeout
    timeout: 1.0
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
