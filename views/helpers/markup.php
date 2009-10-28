<?php
/**
 * Markup Helper class file.
 *
 * Allows you to create a complex (X)HTML structure.
 *
 * Copyright (c) 2009 Takayuki Miwa <i@tkyk.name>
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) 2009 Takayuki Miwa <i@tkyk.name>
 * @link          http://wp.serpere.info/
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

class MarkupHelper extends AppHelper
{
  /**
   * The tags that have been opened and not yet closed.
   * 
   * @var array
   */
  var $_tagStack = array();

  /**
   * Output buffer.
   *
   * @var array
   */
  var $_buffer = array();

  /**
   * @var array
   */
  var $_contextStack = array();

  /**
   * The elements declared as EMPTY in the XHTML 1.0 DTD.
   * 
   * @var array
   */
  var $emptyElements = array('area', 'base', 'br', 'col', 'hr', 'img',
			     'input', 'link', 'meta', 'param');

  /**
   * @var array
   */
  var $methodAlias = array('end' => 'endTag',
			   'nl'  => 'newline');

  /**
   * Constructor.
   *
   * @param $opts array  currently not in use.
   */
  function __construct($opts=array())
  {
    $tmp = array();
    foreach($this->emptyElements as $tag) {
      $tmp[$tag] = true;
    }
    $this->emptyElements = $tmp;
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
  function startTag($tag, $attrs=null, $content=null, $escapeContent=true)
  {
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
  function endTag($tag=null)
  {
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
  function endAllTags()
  {
    $this->_closeTags($this->_tagStack);
    $this->_clearTagStack();
    return $this;
  }

  function _closeTags($tags)
  {
    foreach($tags as $_tag) {
      $this->_buffer[] = "</{$_tag}>";
    }
  }

  /**
   * Appends a newline to the buffer.
   *
   * @return MarkupHelper
   */
  function newline()
  {
    $this->_buffer[] = "\n";
    return $this;
  }

  function _clearTagStack()
  {
    $this->_tagStack = array();
  }

  function _clearBuffer()
  {
    $this->_buffer = array();
  }

  /**
   * Clears the output buffer and the tag stack.
   *
   * @return MarkupHelper
   */
  function clear()
  {
    $this->_clearBuffer();
    $this->_clearTagStack();
    return $this;
  }

  /**
   * Appends strings to the buffer.
   * 
   * @return MarkupHelper
   */
  function html()
  {
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
  function text()
  {
    foreach(func_get_args() as $str) {
      $this->_buffer[] = h($str);
    }
    return $this;
  }

  /**
   * @return MarkupHelper
   */
  function pushNewContext()
  {
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
  function popContext()
  {
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
   * The afterRender callback.
   */
  function afterRender()
  {
    $this->clear();
  }

  /**
   * The afterLayout callback.
   */
  function afterLayout()
  {
    $this->clear();
  }

  /**
   * Returns and clears the output buffer contents.
   * 
   * @return string
   */
  function __toString()
  {
    $ret = $this->output(join("", $this->_buffer));
    $this->_clearBuffer();
    return $ret;
  }

  /**
   * Short-cut methods.
   *
   * - <tag-name>(arg1, arg2, ...) means startTag(<tagname>, arg1, arg2, ...)
   * - end<tag-name>() means endTag(<tagname>)
   */
  function __call($method, $args)
  {
    switch(true) {
    case isset($this->methodAlias[$method]):
      return $this->dispatchMethod($this->methodAlias[$method], $args);
    case preg_match('/^end(.+)/', $method, $m):
      return $this->endTag($m[1]);
    default:
      return $this->dispatchMethod('startTag', array_merge(array($method), $args));
    }
  }

  /**
   * 
   */
  function __get($prop)
  {
    return $this->{$prop}();
  }

  /**
   * Calls View::element() in a new context and appends the returned html
   * to the buffer.
   * 
   * @return MarkupHelper
   */
  function renderElement()
  {
    $args = func_get_args();
    $this->pushNewContext();
    $ret = ClassRegistry::getObject('view')->dispatchMethod('element', $args);
    $this->popContext();
    return $this->html($ret);
  }

}
