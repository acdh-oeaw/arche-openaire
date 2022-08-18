# ARCHE OpenAIRE tracking plugin

An [arche-core](https://github.com/acdh-oeaw/arche-core) plugin implementing [usage tracking trough the OpenAIRE](https://openaire.github.io/usage-statistics-guidelines/service-specification/service-spec/).

## Installation

* Install with:
  ```
  composer require acdh-oeaw/arche-openaire
  ```
* Adjust your repository `config.yaml` so it:
  * contains the `openaire` section with following config:
    ```yaml
    openaire:
      # OpenAIRE tracker URL
      url: "https://analytics.openaire.eu/piwik.php"
      # id of the repository in the OpenAIRE tracker
      id: 602
      # OpenAIRE auth token
      authToken: "32846584f571be9b57488bf4088f30ea"
      # true or false - should client IP be tracked?
      trackIp: false
      # template for the urlref tracking parameter
      # may contain {baseUrl} and {id} placeholders which are substituted with
      # arche repository base URL and internal repository resource id, respectively
      urlref: "{baseUrl}browser/oeaw_detail/{id}"
    ```
  * defines this plugin methods as `get` and `getMetadata` handlers:
    ```yaml
    rest:
      handlers:
        methods:
          get:
          - type: function
            function: \acdhOeaw\arche\openaire\onGet
          getMetadata:
          - type: function
            function: \acdhOeaw\arche\openaire\onGetMetadata
    ```
