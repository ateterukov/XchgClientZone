<?php
class MenuBuilder
{
    private $path = "/";

    private $wrappersDir = "/template/wrappers";
    private $wrappers = array(
        "tree"              => "<ul></ul>",
        "childTree"         => "<ul></ul>",
        "node"              => "<li></li>",
        "childNode"         => "<li></li>"
    );

    private $CSSOptions = array(
        "tree_class"        => "",
        "childTree_class"   => "",
        "node_class"        => "",
        "childNode_class"   => ""
    );


    /**
     * HtmlBuilder constructor
     */
    public function __construct()
    {
        if ( !session_id()) session_start();

        $this->path = $_SERVER["DOCUMENT_ROOT"] . rtrim(
                BASE_DIR, DIRECTORY_SEPARATOR
            );
    }

    /**
     * Build the navigation menu
     *
     * @param array $treeNodes
     * @param array $wrappers
     * @return string
     */
    public function buildNavMenu($treeNodes = array(), $wrappers = array(), $options = array())
    {
        if ( !is_array($treeNodes) || !count($treeNodes))
        {
            return false;
        }

        // Initialize wrappers
        if (is_array($wrappers) &&
            count($wrappers))
        {
            foreach ($wrappers as $name => $path)
            {
                if (key_exists($name, $this->wrappers) &&
                    $this->isExistsHtmlWrapper($path))
                {
                    $this->wrappers[$name] = $this->getHtmlWrapperContents(
                        $path
                    );
                }
            }
        }

        if (is_array($options) &&
            count($options))
        {
            $this->CSSOptions = array_merge(
                $this->CSSOptions, $options
            );
        }

        // Collect tree nodes
        $outputHtmlData = $this->collectTreeNodes(
            $treeNodes, min(array_keys(
                $treeNodes
            ))
        );

        return $outputHtmlData;
    }

    /**
     * Collect tree nodes
     * of the navigation menu
     *
     * @param array $treeNodes
     * @param int $actualNodeId
     * @return mixed|string
     */
    private function collectTreeNodes($treeNodes = array(), $actualNodeId = 0)
    {
        $outputHtmlData = "";
        if ( !key_exists($actualNodeId, $treeNodes))
        {
            return $outputHtmlData;
        }

        if ( !is_array($treeNodes[$actualNodeId]) ||
            !count($treeNodes[$actualNodeId]))
        {
            return $outputHtmlData;
        }

        $treePositions = array();
        foreach ($treeNodes[$actualNodeId] as $position => $node)
        {
            $treePositions[$position] = $node["weight"];
        }

        // Sorting tree nodes
        array_multisort($treePositions, SORT_ASC, $treeNodes[$actualNodeId]);

        // Tree properties
        $listTreeProps = array(
            "dataHtmlNodes"     => ""
        );

        // Node properties by default
        $defaultNodeProps = array(
            // Link properties
            "itemTitle"         => "",
            "itemWebUrl"        => "/",
            "itemTarget"        => "_self",

            // CSS properties
            "itemCSSClass"      => "",
            "activeCSSClass"    => "active"
        );

        foreach ($treeNodes[$actualNodeId] as $treeNode)
        {
            // Fetch node properties
            $listNodeProps = array_replace(
                $defaultNodeProps, array_intersect_key(
                    $treeNode, $defaultNodeProps
                )
            );

            // Get data of the child nodes
            $listNodeProps["dataHtmlChildNodes"] = $this->collectTreeNodes(
                $treeNodes, $treeNode["id"]
            );

            // Add HTML data of tree node
            if ( !empty($actualNodeId))
            {
                $listTreeProps["dataHtmlNodes"] .= $this->attachHtmlWrapper(
                    "childNode", $listNodeProps
                );

                continue;
            }

            $listNodeProps["nodeCSSClass"] = (count($treeNodes[$treeNode["id"]]))
                ? $this->CSSOptions["childNode_class"]
                : $this->CSSOptions["node_class"];

            // Add HTML data of tree node
            $listTreeProps["dataHtmlNodes"] .= $this->attachHtmlWrapper(
                "node", $listNodeProps
            );
        }

        // Add HTML data of tree
        if ( !empty($actualNodeId))
        {
            $outputHtmlData = $this->attachHtmlWrapper(
                "childTree", $listTreeProps
            );
        }
        else
        {
            $outputHtmlData = $this->attachHtmlWrapper(
                "tree", $listTreeProps
            );
        }

        return $outputHtmlData;
    }

    /**
     * Check that is exists
     * the HTML wrapper
     *
     * @param string $path
     * @return bool
     */
    private function isExistsHtmlWrapper($path = "")
    {
        return ( !empty($path) && file_exists(
                $this->path . $this->wrappersDir . DIRECTORY_SEPARATOR . rtrim(
                    $path, DIRECTORY_SEPARATOR
                )
            ));
    }

    /**
     * Get contents of the HTML wrapper
     *
     * @param string $path
     * @return string
     */
    private function getHtmlWrapperContents($path = "")
    {
        return file_get_contents(
            $this->path . $this->wrappersDir . DIRECTORY_SEPARATOR . rtrim(
                $path, DIRECTORY_SEPARATOR
            )
        );
    }

    /**
     * Attach the HTML wrapper
     *
     * @param string $name
     * @param array $attributes
     * @return mixed|string
     */
    private function attachHtmlWrapper($name = "", $attributes = array())
    {
        $outputHtmlData = "";
        if ( !key_exists($name, $this->wrappers))
        {
            return $outputHtmlData;
        }

        $outputHtmlData = str_replace(array_map(function($attribute){
            return sprintf("[[~%s]]", $attribute);
        }, array_keys($attributes)),
            array_values($attributes), $this->wrappers[$name]
        );

        return $outputHtmlData;
    }
}

# end of file