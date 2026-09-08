<?php

/**
 * Class RecognitionModule
 * 
 * Manages plant identification through image recognition.
 * 
 * Supports multiple providers, selectable via the 'plantrec_provider' app
 * setting. Every provider normalizes its response into the same shape:
 * 
 *   { results: [ { score, species: { scientificName, scientificNameWithoutAuthor, commonNames } } ] }
 * 
 * so that calling code never needs to know which provider produced it.
 */
class RecognitionModule {
    const PROVIDER_PLANTNET = 'plantnet';
    const PROVIDER_PLANTID = 'plantid';

    const PLANTNET_API_ENDPOINT = 'https://my-api.plantnet.org/v2/identify/all';
    const PLANTID_API_ENDPOINT = 'https://api.plant.id/v3/identification';

    /**
     * Identifies a plant, automatically falling back to the other configured
     * provider if the preferred one fails (network error, rate limit, invalid
     * key, or simply no match found) and a second API key is configured.
     * 
     * @param $asset
     * @return mixed
     * @throws \Exception
     */
    public static function identify($asset)
    {
        if (!app('plantrec_enable')) {
            throw new \Exception('Recognition feature is currently deactivated');
        }

        $primary = app('plantrec_provider', self::PROVIDER_PLANTNET);
        $secondary = ($primary === self::PROVIDER_PLANTID) ? self::PROVIDER_PLANTNET : self::PROVIDER_PLANTID;

        try {
            return static::query($primary, $asset);
        } catch (\Exception $primaryException) {
            if (!static::isProviderConfigured($secondary)) {
                throw $primaryException;
            }

            try {
                return static::query($secondary, $asset);
            } catch (\Exception $secondaryException) {
                throw new \Exception($primaryException->getMessage() . ' / ' . $secondaryException->getMessage());
            }
        }
    }

    /**
     * Identifies a plant for an anonymous visitor of the public catalogue
     * (see PublicController::identify_plant). Deliberately narrower than
     * identify():
     *
     *  - only ever queries Pl@ntNet, and only using the free API key the
     *    admin already configured for their own use - it never falls back
     *    to Plant.id, since that key may be a separate, paid, personal
     *    quota the admin didn't intend to share with the public
     *  - gated by its own 'public_plantid_enable' switch, independent of
     *    the admin-facing 'plantrec_enable' toggle, so an admin can offer
     *    (or withhold) each independently
     *
     * Callers are expected to have already applied rate limiting - this
     * method only checks whether the feature is configured at all.
     *
     * @param $asset
     * @return mixed
     * @throws \Exception
     */
    public static function identifyPublic($asset)
    {
        if (!app('public_plantid_enable')) {
            throw new \Exception('Public identification is currently deactivated');
        }

        if (empty(app('plantrec_apikey'))) {
            throw new \Exception('Public identification is not configured');
        }

        return static::query(self::PROVIDER_PLANTNET, $asset);
    }

    /**
     * @param $provider
     * @return bool
     */
    private static function isProviderConfigured($provider)
    {
        if ($provider === self::PROVIDER_PLANTID) {
            return !empty(app('plantrec_apikey_plantid'));
        }

        return !empty(app('plantrec_apikey'));
    }

    /**
     * Dispatches to the given provider and normalizes failure conditions
     * (curl errors as well as in-band API error payloads) into thrown
     * exceptions, so identify() can reliably detect and fall back on them.
     * 
     * @param $provider
     * @param $asset
     * @return mixed
     * @throws \Exception
     */
    private static function query($provider, $asset)
    {
        if ($provider === self::PROVIDER_PLANTID) {
            return static::queryPlantId($asset);
        }

        $data = (strpos($asset, 'https://') !== false) ? static::queryGet($asset) : static::queryPost($asset);

        if ((isset($data->statusCode)) && ((int)$data->statusCode >= 400)) {
            throw new \Exception(isset($data->message) ? $data->message : 'Pl@ntNet request failed');
        }

        if ((!isset($data->results)) || (!is_array($data->results))) {
            throw new \Exception('Invalid results returned');
        }

        return $data;
    }

    /**
     * @param $asset
     * @return mixed
     * @throws \Exception
     */
    private static function queryPost($asset)
    {
        try {
            $ch = curl_init(self::PLANTNET_API_ENDPOINT . '?api-key=' . app('plantrec_apikey'));

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);

            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: multipart/form-data'
            ]);

            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'images' => curl_file_create($asset)
            ]);

            $response = curl_exec($ch);

            $error = curl_error($ch);
            if ((is_string($error)) && (strlen($error) > 0)) {
                throw new \Exception($error);
            }

            curl_close($ch);

            return json_decode($response);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $asset
     * @return mixed
     * @throws \Exception
     */
    private static function queryGet($asset)
    {
        try {
            $ch = curl_init(self::PLANTNET_API_ENDPOINT . '?api-key=' . app('plantrec_apikey') . '&images=' . $asset);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, false);

            $response = curl_exec($ch);

            $error = curl_error($ch);
            if ((is_string($error)) && (strlen($error) > 0)) {
                throw new \Exception($error);
            }

            curl_close($ch);

            return json_decode($response);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Queries the Plant.id (Kindwise) identification API and normalizes
     * the response into the same shape used by the Pl@ntNet provider.
     * 
     * @param $asset string Local file path or public HTTPS URL of the image
     * @return object
     * @throws \Exception
     */
    private static function queryPlantId($asset)
    {
        try {
            $imageData = static::fetchAssetContents($asset);

            $finfo = finfo_open();
            $mime = finfo_buffer($finfo, $imageData, FILEINFO_MIME_TYPE);
            finfo_close($finfo);

            if (!is_string($mime) || strpos($mime, 'image/') !== 0) {
                $mime = 'image/jpeg';
            }

            $payload = json_encode([
                'images' => ['data:' . $mime . ';base64,' . base64_encode($imageData)],
                'details' => ['common_names']
            ]);

            $ch = curl_init(self::PLANTID_API_ENDPOINT);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);

            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Api-Key: ' . app('plantrec_apikey_plantid')
            ]);

            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

            $response = curl_exec($ch);

            $error = curl_error($ch);
            if ((is_string($error)) && (strlen($error) > 0)) {
                throw new \Exception($error);
            }

            curl_close($ch);

            $json = json_decode($response);

            if ((isset($json->message)) && (!isset($json->result))) {
                throw new \Exception($json->message);
            }

            return static::normalizePlantIdResponse($json);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Reads the raw bytes of a local file or downloads them from a public URL.
     * 
     * @param $asset string
     * @return string
     * @throws \Exception
     */
    private static function fetchAssetContents($asset)
    {
        if (strpos($asset, 'https://') !== false) {
            $ch = curl_init($asset);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            $data = curl_exec($ch);
            $error = curl_error($ch);

            curl_close($ch);

            if (($data === false) || (strlen($error) > 0)) {
                throw new \Exception('Unable to download image from URL: ' . $error);
            }

            return $data;
        }

        $data = file_get_contents($asset);

        if ($data === false) {
            throw new \Exception('Unable to read image file');
        }

        return $data;
    }

    /**
     * Normalizes a Plant.id v3 identification response into the same shape
     * returned by the Pl@ntNet provider.
     * 
     * @param $json object
     * @return object
     */
    private static function normalizePlantIdResponse($json)
    {
        $results = [];

        $suggestions = (isset($json->result) && isset($json->result->classification) && isset($json->result->classification->suggestions))
            ? $json->result->classification->suggestions
            : [];

        foreach ($suggestions as $suggestion) {
            $name = isset($suggestion->name) ? $suggestion->name : '';

            $results[] = (object)[
                'score' => isset($suggestion->probability) ? $suggestion->probability : 0,
                'species' => (object)[
                    'scientificName' => $name,
                    'scientificNameWithoutAuthor' => $name,
                    'commonNames' => (isset($suggestion->details) && isset($suggestion->details->common_names))
                        ? $suggestion->details->common_names
                        : []
                ]
            ];
        }

        return (object)[
            'results' => $results
        ];
    }
}
