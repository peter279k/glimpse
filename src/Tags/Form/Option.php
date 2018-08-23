<?php
namespace Packaged\Glimpse\Tags\Form;

class Option extends AbstractFormElementTag
{
  protected $_tag = 'option';

  public function __construct($content = null, $value = null)
  {
    if($value)
    {
      $this->setAttribute('value', $value);
    }
    parent::__construct($content);
  }

  public static function collection(array $items)
  {
    $return = [];
    foreach($items as $k => $item)
    {
      if($item instanceof static)
      {
        $return[] = $item;
      }
      else
      {
        $return[] = new static($item, $k);
      }
    }
    return $return;
  }

}