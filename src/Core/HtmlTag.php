<?php
namespace Packaged\Glimpse\Core;

use Packaged\Helpers\Arrays;
use Packaged\Helpers\ValueAs;
use Packaged\SafeHtml\ISafeHtmlProducer;
use Packaged\SafeHtml\SafeHtml;

/**
 * Render a HTML tag in a way that treats user content as unsafe by default.
 *
 * Tag rendering has some special logic which implements security features:
 *
 *   - When rendering `<a>` tags, if the `rel` attribute is not specified, it
 *     is interpreted as `rel="noreferrer"`.
 *   - When rendering `<a>` tags, the `href` attribute may not begin with
 *     `javascript:`.
 *
 * These special cases can not be disabled.
 *
 * IMPORTANT: The `$tag` attribute and the keys of the `$attributes` array are
 * trusted blindly, and not escaped. You should not pass user data in these
 * parameters.
 *
 */
abstract class HtmlTag implements ISafeHtmlProducer
{
  protected $_tag;
  protected $_attributes = [];
  protected $_content;

  public function __construct() { }

  /**
   * @return static
   */
  public static function create()
  {
    return new static();
  }

  /**
   * @return string
   */
  public function getTag()
  {
    return $this->_tag;
  }

  /**
   * Array of attributes for the tag
   *
   * @param array $attributes
   *
   * @return $this
   */
  public function setAttributes(array $attributes)
  {
    $this->_attributes = $attributes;
    return $this;
  }

  /**
   * Array of attributes for the tag
   *
   * @param array $attributes
   * @param bool  $overwriteIfExists
   *
   * @return $this
   */
  public function addAttributes(array $attributes, bool $overwriteIfExists = false)
  {
    foreach($attributes as $k => $v)
    {
      if($overwriteIfExists || !array_key_exists($k, $this->_attributes))
      {
        $this->setOrRemoveAttribute($k, $v);
      }
    }
    return $this;
  }

  /**
   * @param string $key
   *
   * @return $this
   */
  public function removeAttribute($key)
  {
    unset($this->_attributes[$key]);
    return $this;
  }

  /**
   * @return array
   */
  public function getAttributes()
  {
    return $this->_attributes;
  }

  /**
   * Content to put in the tag.
   *
   * @param $content
   *
   * @return $this
   */
  public function setContent($content)
  {
    $this->_content = $content;
    return $this;
  }

  /**
   * @param bool $asArray
   *
   * @return array|string
   */
  public function getContent($asArray = true)
  {
    if($asArray)
    {
      return (array)$this->_content;
    }
    else if(is_array($this->_content))
    {
      return implode('', $this->_content);
    }
    return $this->_content;
  }

  /**
   * @param string $key
   * @param string $value
   *
   * @return $this
   */
  public function setOrRemoveAttribute($key, $value)
  {
    if(!empty($value))
    {
      $this->setAttribute($key, $value);
    }
    else
    {
      $this->removeAttribute($key);
    }
    return $this;
  }

  /**
   * @param string $key
   * @param string $value
   *
   * @return $this
   */
  public function setAttribute($key, $value)
  {
    $this->_attributes[$key] = $value;
    return $this;
  }

  /**
   * @param string $key
   * @param string $default
   *
   * @return string
   */
  public function getAttribute($key, $default = null)
  {
    return Arrays::value($this->_attributes, $key, $default);
  }

  /**
   * @param $key
   *
   * @return bool
   */
  public function hasAttribute($key)
  {
    return array_key_exists($key, $this->_attributes);
  }

  /**
   * @param $content
   *
   * @return $this
   */
  public function appendContent($content)
  {
    if(!is_array($this->_content))
    {
      $this->_content = [$this->_content];
    }
    $this->_content[] = $content;
    return $this;
  }

  /**
   * @param $content
   *
   * @return $this
   */
  public function prependContent($content)
  {
    if(!is_array($this->_content))
    {
      $this->_content = [$this->_content];
    }
    array_unshift($this->_content, $content);
    return $this;
  }

  /**
   * @param string $id
   *
   * @return $this
   */
  public function setId($id)
  {
    return $this->setOrRemoveAttribute('id', $id);
  }

  /**
   * @return string
   */
  public function getId()
  {
    return $this->getAttribute('id');
  }

  /**
   * @param string|array $class
   *
   * @return $this
   */
  public function addClass($class)
  {
    if(func_num_args() === 1)
    {
      $classes = ValueAs::arr($class);
    }
    else
    {
      $classes = func_get_args();
    }

    foreach($classes as $class)
    {
      if(is_array($class))
      {
        foreach($class as $c)
        {
          $this->_addClass($c);
        }
      }
      else
      {
        $this->_addClass($class);
      }
    }
    return $this;
  }

  protected function _addClass($class)
  {
    if(!isset($this->_attributes['class']))
    {
      $this->_attributes['class'] = [];
    }
    $this->_attributes['class'][$class] = $class;
    return $this;
  }

  /**
   * @param string $class
   *
   * @return bool
   */
  public function hasClass($class)
  {
    return isset($this->_attributes['class'][$class]);
  }

  /**
   * @param string|array $class
   *
   * @return $this
   */
  public function removeClass($class)
  {
    if(func_num_args() === 1)
    {
      $classes = ValueAs::arr($class);
    }
    else
    {
      $classes = func_get_args();
    }

    foreach($classes as $class)
    {
      if(is_array($class))
      {
        foreach($class as $c)
        {
          $this->_removeClass($c);
        }
      }
      else
      {
        $this->_removeClass($class);
      }
    }
    return $this;
  }

  protected function _removeClass($class)
  {
    unset($this->_attributes['class'][$class]);
    return $this;
  }

  public function getClasses()
  {
    return (array)Arrays::value($this->_attributes, 'class', []);
  }

  protected function _prepareForProduce(): HtmlTag
  {
    //Make any changes to the tag just before building the html
    return $this;
  }

  /**
   * @return SafeHtml
   * @throws \Exception
   */
  public function produceSafeHTML(): SafeHtml
  {
    $ele = $this->_prepareForProduce();

    // If the `href` attribute is present:
    //   - make sure it is not a "javascript:" URI. We never permit these.
    //   - if the tag is an `<a>` and the link is to some foreign resource,
    //     add `rel="nofollow"` by default.
    if(!empty($ele->_attributes['href']))
    {

      // This might be a URI object, so cast it to a string.
      $href = (string)$ele->_attributes['href'];

      if(isset($href[0]))
      {
        $isAnchorHref = ($href[0] == '#');

        // Is this a link to a resource on the same domain? The second part of
        // this excludes "///evil.com/" protocol-relative hrefs.
        $isDomainHref = ($href[0] == '/') && (!isset($href[1]) || $href[1] != '/');

        // Block 'javascript:' hrefs at the tag level: no well-designed
        // application should ever use them, and they are a potent attack vector.

        // This function is deep in the core and performance sensitive, so we're
        // doing a cheap version of this test first to avoid calling preg_match()
        // on URIs which begin with '/' or `#`. These cover essentially all URIs
        // in Phabricator.
        if(!$isAnchorHref && !$isDomainHref)
        {
          // Chrome 33 and IE 11 both interpret "javascript\n:" as a Javascript
          // URI, and all browsers interpret "  javascript:" as a Javascript URI,
          // so be aggressive about looking for "javascript:" in the initial
          // section of the string.

          $normalizedHref = preg_replace('([^a-z0-9/:]+)i', '', $href);
          if(preg_match('/^javascript:/i', $normalizedHref))
          {
            throw new \Exception(
              "Attempting to render a tag with an 'href' attribute that " .
              "begins with 'javascript:'. This is either a serious security " .
              "concern or a serious architecture concern. Seek urgent " .
              "remedy."
            );
          }
        }
      }
    }

    // For tags which can't self-close, treat null as the empty string -- for
    // example, always render `<div></div>`, never `<div />`.
    $selfClosingTags = [
      'area'    => true,
      'base'    => true,
      'br'      => true,
      'col'     => true,
      'command' => true,
      'embed'   => true,
      'frame'   => true,
      'hr'      => true,
      'img'     => true,
      'input'   => true,
      'keygen'  => true,
      'link'    => true,
      'meta'    => true,
      'param'   => true,
      'source'  => true,
      'track'   => true,
      'wbr'     => true,
    ];

    $attrString = '';
    foreach($ele->_attributes as $k => $v)
    {
      if($v === null || $v === true)
      {
        $attrString .= ' ' . $k;
      }
      else
      {
        $attrString .= ' ' . $k . '="' . SafeHtml::escape($v) . '"';
      }
    }

    $content = $ele->_getContentForRender();
    if(empty($content))
    {
      if(isset($selfClosingTags[$ele->_tag]))
      {
        return new SafeHtml('<' . $ele->_tag . $attrString . ' />');
      }
      $content = '';
    }
    else
    {
      $content = SafeHtml::escape($content, '');
    }

    return new SafeHtml('<' . $ele->_tag . $attrString . '>' . $content . '</' . $ele->_tag . '>');
  }

  protected function _getContentForRender()
  {
    return $this->_content;
  }

  public function __toString()
  {
    try
    {
      return $this->asHtml();
    }
    catch(\Exception $e)
    {
      error_log(
        ($e->getCode() > 0 ? '[' . $e->getCode() . '] ' : '')
        . $e->getMessage()
        . ' (' . $e->getFile() . ':' . $e->getLine() . ')'
      );
      return $e->getMessage();
    }
  }

  public function asHtml()
  {
    return (string)$this->produceSafeHTML();
  }

  /**
   * @param HtmlTag $tag
   *
   * @return $this
   */
  public function copyFrom(HtmlTag $tag)
  {
    $this->setContent($tag->getContent());
    $this->setAttributes($tag->getAttributes());
    $this->addClass($tag->getClasses());
    return $this;
  }
}
