<?php

/**
 * The Default Template Implementation Grammar/Language Map
 *
 * @author Faizan Ayubi
 */

namespace Framework\Template\Implementation {

    use Framework\Template as Template;
    use Framework\StringMethods as StringMethods;

    class Standard extends Template\Implementation {

        /**
         * list of language tags for template dialect,
         * parser can know what the different types of template tags are,
         * and how to parse them
         * 
         * @var type 
         */
        protected $_map = array(
            "echo" => array(
                "opener" => "{echo",
                "closer" => "}",
                "handler" => "_echo"
            ),
            "script" => array(
                "opener" => "{script",
                "closer" => "}",
                "handler" => "_script"
            ),
            "statement" => array(
                "opener" => "{",
                "closer" => "}",
                "tags" => array(
                    "foreach" => array(
                        "isolated" => false,
                        "arguments" => "{element} in {object}",
                        "handler" => "_each"
                    ),
                    "for" => array(
                        "isolated" => false,
                        "arguments" => "{element} in {object}",
                        "handler" => "_for"
                    ),
                    "if" => array(
                        "isolated" => false,
                        "arguments" => null,
                        "handler" => "_if"
                    ),
                    "elseif" => array(
                        "isolated" => true,
                        "arguments" => null,
                        "handler" => "_elif"
                    ),
                    "else" => array(
                        "isolated" => true,
                        "arguments" => null,
                        "handler" => "_else"
                    ),
                    "macro" => array(
                        "isolated" => false,
                        "arguments" => "{name}({args})",
                        "handler" => "_macro"
                    ),
                    "literal" => array(
                        "isolated" => false,
                        "arguments" => null,
                        "handler" => "_literal"
                    )
                )
            )
        );

        /**
         * Converts the string “{echo $hello}” to “$_text[] = $hello”, 
         * so that it is already optimized for our final evaluated function.
         * 
         * @param type $tree
         * @param type $content
         * @return type
         */
        protected function _echo($tree, $content) {
            $raw = $this->_script($tree, $content);
            return "\$_text[] = {$raw}";
        }

        /**
         * Converts the string “{:$foo + = 1}” to “$foo + = 1”.
         * @param type $tree
         * @param type $content
         * @return type
         */
        protected function _script($tree, $content) {
            $raw = !empty($tree["raw"]) ? $tree["raw"] : "";
            return "{$raw};";
        }

        /**
         * Returns the code to perform a foreach loop through an array
         * @param type $tree
         * @param type $content
         * @return type
         */
        protected function _each($tree, $content) {
            $object = $tree["arguments"]["object"];
            $element = $tree["arguments"]["element"];

            return $this->_loop(
                            $tree, "foreach ({$object} as {$element}_i => {$element}) {
                    {$content}
                }"
            );
        }

        /**
         * Produces the code to perform a for loop through an array
         * @param type $tree
         * @param type $content
         * @return type
         */
        protected function _for($tree, $content) {
            $object = $tree["arguments"]["object"];
            $element = $tree["arguments"]["element"];

            return $this->_loop(
                            $tree, "for ({$element}_i = 0; {$element}_i < sizeof({$object}); {$element}_i++) {
                    {$element} = {$object}[{$element}_i];
                    {$content}
                }"
            );
        }

        protected function _if($tree, $content) {
            $raw = $tree["raw"];
            return "if ({$raw}) {{$content}}";
        }

        protected function _elif($tree, $content) {
            $raw = $tree["raw"];
            return "elseif ({$raw}) {{$content}}";
        }

        protected function _else($tree, $content) {
            return "else {{$content}}";
        }

        /**
         * Creates the string representation of a function, based on the contents of a {macro...}...{/macro} tag set.
         * It is possible, using the {macro} tag, to define functions, which we then use within our templates.
         * 
         * @param type $tree
         * @param type $content
         * @return type
         */
        protected function _macro($tree, $content) {
            $arguments = $tree["arguments"];
            $name = $arguments["name"];
            $args = $arguments["args"];

            return "function {$name}({$args}) {
                \$_text = array();
                {$content}
                return implode(\$_text);
            }";
        }

        /**
         * Directly quotes any content within it. The template parser only stops directly quoting the content when it finds a {/literal} closing tag.
         * @param type $tree
         * @param type $content
         * @return type
         */
        protected function _literal($tree, $content) {
            $source = addslashes($tree["source"]);
            return "\$_text[] = \"{$source}\";";
        }

        protected function _loop($tree, $inner) {
            $number = $tree["number"];
            $object = $tree["arguments"]["object"];
            $children = $tree["parent"]["children"];

            if (!empty($children[$number + 1]["tag"]) && $children[$number + 1]["tag"] == "else") {
                return "if (is_array({$object}) && sizeof({$object}) > 0) {{$inner}}";
            }
            return $inner;
        }

    }

}