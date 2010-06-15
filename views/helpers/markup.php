<?php
/**
 * Markup Helper class file.
 *
 * MarkupHelper provides a fluent interface for building complex (X)HTML.
 *
 * Copyright (c) 2010 Takayuki Miwa <i@tkyk.name>
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) 2010 Takayuki Miwa <i@tkyk.name>
 * @link          http://github.com/tkyk/cakephp-markup-helper
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

class MarkupHelper extends AppHelper
{
    /**
     * @var array Helpers
     */
    public $helpers = array();

    /**
     * View instance
     * 
     * @var object 
     */
    protected $_view = null;

    /**
     * The tags that have been opened and not yet closed.
     * 
     * @var array
     */
    protected $_tagStack = array();

    /**
     * Output buffer.
     *
     * @var array
     */
    protected $_buffer = array();

    /**
     * @var array
     */
    protected $_contextStack = array();

    /**
     * The elements declared as EMPTY in the XHTML 1.0 DTD.
     * 
     * @var array
     */
    public $emptyElements = array('area', 'base', 'br', 'col', 'hr', 'img',
                                  'input', 'link', 'meta', 'param');

    /**
     * @var array
     */
    protected $_methodAliases = array('end' => 'endTag',
                                      'nl'  => 'newline');

    /**
     * @var array  methodPrefix => helperName
     */
    public $prefix2Helper = array();

    /**
     * @var array
     */
    private $__helperPrefixes = array();

    /**
     * @var string
     */
    private $__prefixMatchRegex = null;

    /**
     * CamelizedHelperName|prefix => Helper instance
     * 
     * @var array
     */
    protected $_loadedHelpers = array();

    /**
     * Constructor.
     * 
     * Options:
     *   helpers => array(HelperName,
     *                    HelperName => prefix,
     *                    array('name' => HelperName, 'prefix' => prefix))
     *
     * @param $options array
     */
    public function __construct($opts=array()) {
        $tmp = array();
        foreach($this->emptyElements as $tag) {
            $tmp[$tag] = true;
        }
        $this->emptyElements = $tmp;
        
        $this->useHelper(array('name' => 'Html',
                               'prefix' => 'h'));
        $this->useHelper(array('name' => 'Form',
                               'prefix' => 'f'));

        if(!empty($opts['helpers'])) {
            $helpers = is_array($opts['helpers']) ? $opts['helpers'] : array($opts['helpers']);
            foreach($helpers as $i => $helper) {
                if(is_string($i)) {
                    if(is_string($helper)) {
                        $helper = array('prefix' => $helper);
                    }
                    $helper['name'] = $i;
                }
                $this->useHelper($helper);
            }
        }
        parent::__construct();
    }

    /**
     * Creates a start-tag of $tag and appends it to the buffer.
     * If $content is supplied, also appends $content and the end-tag.
     * 
     * @param $tag   string  Tag name.
     * @param $attrs string or array  HTML attributes. If a string, treated as a CSS class name.
     * @param $content string  String content of the element.
     * @param $escapeContent boolean  If true, $content will be HTML-escaped.
     * @return MarkupHelper
     */
    public function startTag($tag, $attrs=null, $content=null, $escapeContent=true) {
        if (is_string($attrs)) {
            $attrs = array('class' => $attrs);
        }
        $attrStr = $this->_parseAttributes($attrs, null, ' ', '');

        if(isset($this->emptyElements[$tag])) {
            $this->_buffer[] = "<{$tag}{$attrStr} />";
        } else {
            $this->_buffer[] = "<{$tag}{$attrStr}>";
            
            if(!is_string($content)) {
                array_unshift($this->_tagStack, $tag);
            } else {
                $this->_buffer[] = ($escapeContent ? h($content) : $content) . "</{$tag}>";
            }
        }
        return $this;
    }

    /**
     * Creates an end-tag of the innermost tag and appends it to the buffer.
     * If $tag is supplied, appends end-tags of the innermost <$tag> tag and
     * all tags inside it.
     * 
     * If $tag is supplied and <$tag> tag has not been opened,
     * E_USER_WARNING will be raised.
     *
     * @param string  $tag  Tag name.
     * @return MarkupHelper
     */
    public function endTag($tag=null) {
        if($tag === null) {
            if(count($this->_tagStack) > 0) {
                $tagToClose = array_shift($this->_tagStack);
                $this->_closeTags(array($tagToClose));
            }
            return $this;
        }

        if(($pos = array_search($tag, $this->_tagStack)) !== false) {
            $tagsToClose = array_slice($this->_tagStack, 0, $pos+1);
            $this->_tagStack = array_slice($this->_tagStack, $pos+1);
            $this->_closeTags($tagsToClose);
        } else {
            trigger_error("Closing an unopened tag: {$tag}", E_USER_WARNING);
        }
        return $this;
    }

    /**
     * Creates end-tags of the tags that have been opened and not yet closed.
     * 
     * @return MarkupHelper
     */
    public function endAllTags() {
        $this->_closeTags($this->_tagStack);
        $this->_clearTagStack();
        return $this;
    }

    protected function _closeTags($tags) {
        foreach($tags as $_tag) {
            $this->_buffer[] = "</{$_tag}>";
        }
    }

    /**
     * Appends a newline to the buffer.
     *
     * @return MarkupHelper
     */
    public function newline() {
        $this->_buffer[] = "\n";
        return $this;
    }

    protected function _clearTagStack() {
        $this->_tagStack = array();
    }

    protected function _clearBuffer() {
        $this->_buffer = array();
    }

    /**
     * Clears the output buffer and the tag stack.
     *
     * @return MarkupHelper
     */
    public function clear() {
        $this->_clearBuffer();
        $this->_clearTagStack();
        return $this;
    }

    /**
     * Appends strings to the buffer.
     * 
     * @return MarkupHelper
     */
    public function html() {
        foreach(func_get_args() as $str) {
            $this->_buffer[] = $str;
        }
        return $this;
    }

    /**
     * Appends HTML-escaped strings to the buffer.
     * 
     * @return MarkupHelper
     */
    public function text() {
        foreach(func_get_args() as $str) {
            $this->_buffer[] = h($str);
        }
        return $this;
    }

    /**
     * @return MarkupHelper
     */
    public function pushNewContext() {
        array_push($this->_contextStack,
                   array($this->_tagStack,
                         $this->_buffer));
        $this->clear();
        return $this;
    }

    /**
     * Pops the last context and returns its buffer contents as a string.
     * If the stack is empty, returns empty string.
     * 
     * @return string
     */
    public function popContext() {
        $return = "";
        if(count($this->_contextStack) > 0) {
            $return = $this->__toString();
            list($tag, $buf) = array_pop($this->_contextStack);
            $this->_tagStack = $tag;
            $this->_buffer = $buf;
        }
        return $return;
    }

    /**
     * The beforeRender callback
     *
     * Collects loaded helpers and builds regex.
     */
    public function beforeRender() {
        $this->_view =& ClassRegistry::getObject('view');

        $helperNames = array();
        if(!empty($this->_view)) {
            foreach($this->_view->loaded as $camelBacked => $obj) {
                $camelized = Inflector::camelize($camelBacked);
                $this->_loadedHelpers[$camelized] =& $this->_view->loaded[$camelBacked];
                $helperNames[] = $camelized;
            }
        } else {
            foreach($this->helpers as $camelized) {
                $this->_loadedHelpers[$camelized] =& $this->{$camelized};
                $helperNames[] = $camelized;
            }
        }
        $prefixes = array_keys($this->prefix2Helper);

        $this->__prefixMatchRegex = $this->buildHelperRegex($helperNames, $prefixes);
    }

    /**
     * The afterRender callback.
     */
    public function afterRender() {
        $this->clear();
    }

    /**
     * The afterLayout callback.
     */
    public function afterLayout() {
        $this->clear();
    }

    /**
     * Returns and clears the output buffer contents.
     * 
     * @return string
     */
    public function __toString() {
        $ret = $this->output(join("", $this->_buffer));
        $this->_clearBuffer();
        return $ret;
    }

    /**
     * Short-cut methods.
     *
     * - <tag-name>(arg1, arg2, ...) means startTag(<tagname>, arg1, arg2, ...)
     * - <tag-name>_(arg1, arg2, arg3, ...) means startTag(<tagname>, arg2, arg1, arg3, ...)
     * - end<tag-name>() means endTag(<tagname>)
     */
    public function __call($method, $args) {
        switch(true) {
        case isset($this->_methodAliases[$method]):
            return $this->dispatchMethod($this->_methodAliases[$method], $args);
        case preg_match($this->__prefixMatchRegex, $method, $m):
            return $this->html($this->callHelperMethod($m[1], $m[2], $args));
        case preg_match('/^end(.+)/', $method, $m):
            return $this->endTag($m[1]);
        case preg_match('/(.+)_$/', $method, $m):
            $tag = $m[1];
            $content = isset($args[0]) ? $args[0] : null;
            $attr    = isset($args[1]) ? $args[1] : null;
            return $this->dispatchMethod('startTag', array_merge(array($tag, $attr, $content), array_slice($args, 2)));
        default:
            return $this->dispatchMethod('startTag', array_merge(array($method), $args));
        }
    }

    /**
     * 
     */
    public function __get($prop) {
        return $this->{$prop}();
    }

    /**
     * Calls View::element() in a new context and appends the returned html
     * to the buffer.
     * 
     * @return MarkupHelper
     */
    public function renderElement() {
        $args = func_get_args();
        $this->pushNewContext();
        $ret = $this->_view->dispatchMethod('element', $args);
        $this->popContext();
        return $this->html($ret);
    }

    /**
     * Imports another helper.
     * Its methods can now be called as PREFIX_methodName syntax.
     *
     * @param array or string  array('name' => HelperName,
     *                               'prefix' => method_prefix);
     *                         If 'prefix' is not specified, 'name' is used.
     */
    public function useHelper($helper) {
        $helper = $this->__normalizeHelperConfig($helper);

        // create a variable to assign the Helper object by reference
        $this->{$helper['name']} = null;

        if(!in_array($helper['name'], $this->helpers)) {
            $this->helpers[] = $helper['name'];
        }
        if($helper['prefix'] != $helper['name']) {
            $this->prefix2Helper[$helper['prefix']] = $helper['name'];
        }
    }

    /**
     * Returns normalized form of helper option
     * 
     * @param string or array
     * @return array  ('name' => HelperName, 'prefix' => prefix)
     */
    private function __normalizeHelperConfig($helper) {
        if(is_string($helper)) {
            $helper = array('name' => $helper);
        }
        if(!isset($helper['prefix'])) {
            $helper['prefix'] = $helper['name'];
        }
        return $helper;
    }

    /**
     * Builds regex to match `prefix_otherHelperMethod'
     * 
     * @param array  camelized HelperNames
     * @param array  prefixes
     * @return string  regex
     */
    public function buildHelperRegex($helperNames, $prefixes=array()) {
        $helpers = array_unique(array_merge($helperNames, $prefixes));
        $matcher = join('|', $helpers);
        return '/^('. $matcher .')_(.+)$/';
    }

    /**
     * Invokes other helper's method.
     * 
     * @param string  HelperName or prefix
     * @param string  method name
     * @param array   arguments
     * @return mixed
     */
    public function callHelperMethod($helper, $method, $args) {
        $helperName = isset($this->prefix2Helper[$helper]) ?
            $this->prefix2Helper[$helper] : $helper;
        return $this->_loadedHelpers[$helperName]->dispatchMethod($method, $args);
    }

    /**
     * @param string
     * @param string
     */
    public function aliasMethod($newName, $currentName) {
        $this->_methodAliases[$newName] = $currentName;
    }
}
