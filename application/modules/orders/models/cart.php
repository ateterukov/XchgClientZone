<?php
// Connect the class builder
require_once APP_PATH . "/classes/core/builder.php";


class Cart extends Builder
{
    // Collection of items
    private $collection = array();


    public function __construct()
    {
        parent::__construct();

        // Initialize collection of items
        if ($this->cookies->exists("cart_storage"))
        {
            $this->collection = array_filter(
                $this->cookies->get("cart_storage")
            );
        }
    }

    /**
     * Collect list of items
     * @return array
     */
    public function collect()
    {
        return array_column($this->collection,
            "attributes", "unique_hash"
        );
    }

    /**
     * Count the number of items
     * in the collection
     *
     * @return int
     */
    public function size()
    {
        return count($this->collection);
    }

    /**
     * Get attributes
     * of the collection item
     *
     * @param string $hash
     * @return bool|mixed
     */
    public function get($hash = "")
    {
        if ( !$element = $this->find($hash))
        {
            return false;
        }

        return $element["attributes"];
    }

    /**
     * Find item
     * in the collection
     *
     * @param string $hash
     * @return bool|mixed
     */
    public function find($hash = "")
    {
        if ( !$this->exists($hash))
        {
            return false;
        }

        return $this->collection[$hash];
    }

    /**
     * Check if items
     * is exists in the collection
     *
     * @param string $hash
     * @return bool
     */
    public function exists($hash = "")
    {
        return (key_exists($hash, $this->collection));
    }

    /**
     * Append a new item
     * to the collection
     *
     * @param array $attributes
     * @param int $lifetime
     * @return bool
     */
    public function append($attributes = array(), $lifetime = 0)
    {
        if ( !$attributes) return false;

        // Build unique hash of the item
        $hash = $this->buildUniqueHash();

        // Append in the collection
        $this->collection[$hash] = array(
            "unique_hash"   => $hash,
            "lifetime"      => $lifetime,
            "attributes"    => $attributes
        );

        // Save changes
        $this->cookies->set("cart_storage",
            $this->collection
        );

        return true;
    }

    /**
     * Update item
     * in the collection
     *
     * @param array $argums
     * @return bool
     */
    public function update($argums = array())
    {
        // Find item attributes
        if ( !key_exists("hash", $argums) ||
             !$item = $this->find($argums["hash"]))
        {
            return false;
        }

        // Update item attributes
        $item["attributes"] = array_merge(
            $item["attributes"], $argums
        );

        // Update collection
        $this->collection[$argums["hash"]] = array_merge(
            $this->collection[$argums["hash"]], $item
        );

        // Save changes
        $this->cookies->set("cart_storage",
            $this->collection
        );

        return true;
    }

    /**
     * Remove item
     * from the collection
     *
     * @param array $argums
     * @return bool
     */
    public function remove($argums = array())
    {
        // Find item
        if ( !key_exists("hash", $argums) ||
             !$this->exists($argums["hash"]))
        {
            return false;
        }

        // Remove item
        unset($this->collection[$argums["hash"]]);

        // Save changes
        $this->cookies->set("cart_storage",
            $this->collection
        );

        return true;
    }

    /**
     * Clear the collection
     * @return void
     */
    public function clear()
    {
        // Clear collection
        $this->collection = array();

        // Save changes
        $this->cookies->set("cart_storage",
            $this->collection
        );
    }


    // ============================================== PRIVATE METHODS ============================================== //

    /**
     * Build unique hash
     * @return string
     */
    private function buildUniqueHash()
    {
        return md5(time());
    }
}