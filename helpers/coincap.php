<?php
defined("APP_PATH") || exit("No direct script access allowed");

// Connect the helper builder
require_once __DIR__ . "/core/ws_helper.php";


class CoinCap extends WS_Helper
{
    // CoinCap API URL
    private $APIUrl = "http://www.coincap.io/";

    // Lifetime of the storage
    private $lifetime = 3600;

    // List of the coins
    private $coins = array();

    // List of the assets
    private $assets = array();


    public function __construct()
    {
        parent::__construct();
        self::initialize();
    }

    /**
     * Get list of the coins
     * @return array
     */
    public function getListCoins()
    {
        return $this->assets;
    }

    /**
     * Get list of the assets
     * @return array
     */
    public function getListAssets()
    {
        return $this->assets;
    }


    // =================================== PRIVATE METHODS =================================== //

    /**
     * Initialize the class
     * @return void
     */
    private function initialize()
    {
        // Get data storage
        $contents = $this->getLocalStorageData();

        if (empty($contents["time"]))
        {
            // Collect data storage
            $contents = array(
                "coins"         => $this->collectListCoins(),
                "assets"        => $this->collectListAssets()
            );

            // Set the time
            $contents["time"] = time();
        }
        elseif ($this->isExpiredLifeTime($contents["time"]))
        {
            if ( !$contents["coins"])
            {
                // Update the list of coins, if it is empty
                $contents["coins"] = $this->collectListCoins();
            }

            // Get list of the assets
            $assets = $this->collectListAssets();
            if ( !empty($assets))
            {
                // Update the list of assets, if data not empty
                $contents["assets"] = $assets;
            }

            // Set the time
            $contents["time"] = time();
        }

        // Refresh the local storage
        $this->refreshLocalStorage($contents);

        // Merge list of the assets
        $this->assets = array_merge(
            $this->assets, $contents["assets"]
        );

        // Merge list of the coins
        $this->coins = array_merge(
            $this->coins, $contents["coins"]
        );
    }

    /**
     * Check that is expired lifetime
     * @param int $time
     * @return bool
     */
    private function isExpiredLifeTime($time = 0)
    {
        return (time() - $time >= $this->lifetime);
    }

    /**
     * Get data from the local storage
     * @return mixed
     */
    private function getLocalStorageData()
    {
        if ( !file_exists(__DIR__ . "/etc/ccap.stdc"))
        {
            return false;
        }

        // Parse data from the local storage
        $contents = file_get_contents(__DIR__ . "/etc/ccap.stdc");
        $dataset = $this->parse($contents, "json");

        return $dataset;
    }

    /**
     * Refresh the local storage
     *
     * @param array $dataset
     * @return void
     */
    private function refreshLocalStorage($dataset = array())
    {
        file_put_contents(__DIR__ . "/etc/ccap.stdc", json_encode(
            $dataset
        ));
    }

    /**
     * Call the coin API service
     *
     * @param string $method
     * @return mixed
     */
    private function callService($method = "")
    {
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL                 => $this->APIUrl . ltrim($method, "/"),

            CURLOPT_HEADER              => false,
            CURLOPT_SSL_VERIFYPEER      => false,
            CURLOPT_SSL_VERIFYHOST      => false,
            CURLOPT_RETURNTRANSFER      => true,

            CURLOPT_TIMEOUT             => 1,
            CURLOPT_CONNECTTIMEOUT      => 1
        ));

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    /**
     * Collect list of the coins
     * @return mixed
     */
    private function collectListCoins()
    {
        return $this->parse($this->callService("/coins/"));
    }

    /**
     * Collect list of the assets
     * @return array
     */
    private function collectListAssets()
    {
        // List of the assets
        $assets = array();

        if ($results = $this->parse($this->callService("/front/")))
        {
            foreach ($results as $attributes)
            {
                // Collect list of the assets
                $assets[$attributes["short"]] = array(
                    "name"          => $attributes["long"],
                    "code"          => $attributes["short"],
                    "percent"       => $attributes["perc"],
                    "price"         => $attributes["price"]
                );
            }
        }

        return $assets;
    }
}

# end of file