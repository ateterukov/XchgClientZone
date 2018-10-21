<?php
// Maximum limit of items
define("MAX_LIMIT_ITEMS", 100);

// Minimum of visible page numbers
define("MIN_VIEWED_PAGES", 3);

// Default pagination options
define("DEFAULT_PAGE_NUMBER", 1);
define("DEFAULT_LIMIT_ITEMS", 50);

// Default number of visible page numbers
define("DEFAULT_NUM_VIEWED_PAGES", 5);

// Path to application HTML patterns
define("PATTERNS_PATH", TEMPLATES_PATH . DIRECTORY_SEPARATOR . "patterns");


class Paginator
{
    // Requested uri
    private static $uri = array(
        "path"      => "",
        "vars"      => ""
    );

    // List of the pagination options
    private static $options = array(
        "limit"     => DEFAULT_LIMIT_ITEMS,
        "viewed"    => DEFAULT_NUM_VIEWED_PAGES,
        "page"      => DEFAULT_PAGE_NUMBER
    );

    // List of the pagination patterns
    private static $patterns = array(
        "list.over"     => "<div class='pagination'>%s</div>",
        "item.prev"     => "<a href='%s' class='prev'>&#60;</a>",
        "item.first"    => "<a href='%s'>%d</a><span>...</span>",
        "item.this"     => "<a href='%s' class='active'>%d</a>",
        "item.other"    => "<a href='%s'>%d</a>",
        "item.last"     => "<span>...</span><a href='%s'>%d</a>",
        "item.next"     => "<a href='%s' class='next'>&#62;</a>"
    );

    /**
     * Define pagination options
     *
     * @param array $options
     * @param array $patterns
     * @return void
     */
    public static function define($options = array(), $patterns = array())
    {
        // Fetch input pagination options
        $options = array_replace(
            self::$options, array_intersect_key(
                $options, self::$options
            )
        );

        // Convert options
        $options = array_map("intval", $options);

        // Filter options
        $options = array_filter($options, function($option){
            return ($option > 1);
        });

        // Validation options
        if (key_exists("limit", $options) &&
            $options["limit"] > MAX_LIMIT_ITEMS)
        {
            $options["limit"] = DEFAULT_LIMIT_ITEMS;
        }

        if (key_exists("viewed", $options))
        {
            if ($options["viewed"] % 2 == 0 ||
                $options["viewed"] < MIN_VIEWED_PAGES)
            {
                $options["viewed"] = DEFAULT_NUM_VIEWED_PAGES;
            }
        }

        // Merge options
        self::$options = array_replace(
            self::$options, $options
        );

        // Fetch input pagination patterns
        if ( !empty($patterns))
        {
            self::$patterns = array_replace(
                self::$patterns, array_intersect_key(
                    $patterns, self::$patterns
                )
            );
        }

        // Parse list of the patterns
        foreach (self::$patterns as $code => $pattern)
        {
            if ($pattern == strip_tags($pattern) &&
                file_exists(self::getPatternPath($pattern)))
            {
                // Get the contents of the pattern
                self::$patterns[$code] = file_get_contents(
                    self::getPatternPath($pattern)
                );
            }
        }

        // Define requested uri path
        self::$uri["path"] = parse_url(
            $_SERVER["REQUEST_URI"], PHP_URL_PATH
        );

        // Define requested uri query
        parse_str(parse_url(
            $_SERVER["REQUEST_URI"], PHP_URL_QUERY
        ), self::$uri["vars"]);
    }

    /**
     * Build pagination panel
     *
     * @param array $options
     * @param array $patterns
     * @return string
     */
    public static function build($options = array(), $patterns = array())
    {
        // Output HTML result
        $htmlOutput = "";

        // Define pagination options
        self::define($options, $patterns);

        $totalNbItems = intval($options["number_items"]);
        if ($totalNbItems > 0)
        {
            // Set the total number of items
            self::$options["number_items"] = $totalNbItems;
        }

        // Calculate the total number of pages
        self::$options["total_pages"] = ceil(
            self::$options["number_items"] / self::$options["limit"]
        );

        if (self::$options["total_pages"] <= 1)
        {
            return $htmlOutput;
        }

        // Collect list of the page numbers
        $numbers = range(1, self::$options["total_pages"]);

        if (array_search(self::$options["page"], $numbers) === false)
        {
            return $htmlOutput;
        }

        if (self::$options["total_pages"] > self::$options["viewed"])
        {
            $halfIntl = floor(self::$options["viewed"] / 2);
            if (self::$options["page"] + $halfIntl > self::$options["total_pages"])
            {
                // Slice list of tje page numbers
                $numbers = array_slice(
                    $numbers, -self::$options["viewed"]
                );
            }
            else
            {
                $offset = 0;
                if (self::$options["page"] > $halfIntl)
                {
                    // Calculate offset of the page numbers
                    $offset = array_search(
                        self::$options["page"] - $halfIntl, $numbers
                    );
                }

                // Slice list of tje page numbers
                $numbers = array_slice(
                    $numbers, $offset, self::$options["viewed"]
                );
            }
        }

        if (self::$options["page"] > 1)
        {
            // Generate HTML for the previous button
            $htmlOutput .= sprintf(self::$patterns["item.prev"],
                self::getPageUrl(self::$options["page"] - 1)
            );
        }

        if (current($numbers) > 1)
        {
            // Generate HTML for the first page
            $htmlOutput .= sprintf(self::$patterns["item.first"],
                self::getPageUrl(1), 1
            );
        }

        foreach ($numbers as $number)
        {
            if (self::$options["page"] == $number)
            {
                // Generate HTML for the current page
                $htmlOutput .= sprintf(self::$patterns["item.this"],
                    self::getPageUrl($number), $number
                );

                continue;
            }

            // Generate HTML for the other page
            $htmlOutput .= sprintf(self::$patterns["item.other"],
                self::getPageUrl($number), $number
            );
        }

        if (self::$options["total_pages"] > end($numbers))
        {
            // Generate HTML for the last page
            $htmlOutput .= sprintf(self::$patterns["item.last"],
                self::getPageUrl(self::$options["total_pages"]),
                self::$options["total_pages"]
            );
        }

        if (self::$options["page"] < self::$options["total_pages"])
        {
            // Generate HTML for the next button
            $htmlOutput .= sprintf(self::$patterns["item.next"],
                self::getPageUrl(self::$options["page"] + 1)
            );
        }

        if ( !empty(self::$patterns["list.over"]))
        {
            // Wrap elements of pagination
            $htmlOutput = sprintf(self::$patterns["list.over"],
                $htmlOutput
            );
        }

        return $htmlOutput;
    }

    /**
     * Get URL of the page
     *
     * @param int $number
     * @return string
     */
    private static function getPageUrl($number = 1)
    {
        // Set page number to uri
        self::$uri["vars"]["page"] = $number;

        // Return URL of the page
        return self::$uri["path"] . "?" . http_build_query(
            self::$uri["vars"]
        );
    }

    /**
     * Get path to the HTML pattern
     *
     * @param string $path
     * @return string
     */
    private static function getPatternPath($path = "")
    {
        return PATTERNS_PATH . DIRECTORY_SEPARATOR .
            ltrim($path, DIRECTORY_SEPARATOR
        );
    }
}

# end of file