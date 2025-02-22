<?php

class Swift3_Html_Tag {

      public $attributes = array();

      public $properties = array();

      public static $single = array('img', 'br', 'link');

      public $tag_name = '';
      public $inner_html = '';
      public $outer_html = '';

      public $is_single_quote = false;

      public $json_encoded = false;

      public function __construct($html){
            if (strpos($html, '"') !== false || strpos($html, '\/') !== false){
                  $maybe_decoded = json_decode('"' . $html . '"');

                  if (!empty($maybe_decoded)){
                        $this->json_encoded = true;
                        $html = $maybe_decoded;
                  }
            }
            $inside = $read_tagname = $wait_for_close = false;
            $open = $tag_name = $quote = '';
            foreach (str_split($html) as $chr){

                  $open .= $chr;

                  if ($wait_for_close && !$inside && $chr == '>'){
                        break;
                  }

                  if (preg_match('(\s|>)',$chr)){
                        $read_tagname = false;
                  }

                  if ($read_tagname && !$inside){
                        $tag_name .= $chr;
                  }

                  if ($chr == '<'){
                        $read_tagname = true;
                        $wait_for_close = true;
                  }
                  else if (!$inside && in_array($chr, array('"', "'"))){
                        $quote = $chr;
                        $wait_for_close = false;
                        $inside = true;
                  }
                  else if ($inside && $quote == $chr){
                        $quote = '';
                        $inside = false;
                        $wait_for_close = true;
                  }

            }
            preg_match('~</'.$tag_name.'>\s?$~', $html, $matches);
            $close = (isset($matches[0]) && !empty($matches[0]) ? $matches[0] : '');
            //preg_match_all('~([\w\-\:]+)(=([^\s>]*))?~', $open, $attributes);
            preg_match_all('~([\w\-_]+)(=("[^"]*"|\'[^\']*\'|\S*))?~', $open, $attributes);
            //preg_match_all('~(crossorigin|async|defer|disabled|hidden|autoplay|muted|novalidate|readonly)?~', $open, $properties);

            //$this->is_single_quote = in_array("'", $attributes[3]);
            $this->is_single_quote = false;
            $this->tag_name = $tag_name;
            $this->outer_html = $html;
            $this->inner_html = substr($html, strlen($open), (strlen($html) - strlen($open) - strlen($close)));
            for($i = 1; $i < count($attributes[1]); $i++){
                  $value = '';
                  if (!empty($attributes[3][$i])){
                        if (empty($this->is_single_quote)) {
                              $this->is_single_quote = (substr($attributes[3][$i], 0, 1) == "'" ? true : false);
                        }
                        $value = trim($attributes[3][$i], '"\'');
                  }
                  $this->attributes[$attributes[1][$i]] = $value;
            }
            /*
            for($i = 1; $i < count($properties[1]); $i++){
                  if (!isset($this->attributes[$properties[1][$i]]))
                  $this->attributes[$properties[1][$i]] = '';
            }
            */
      }

      public function remove_attribute($attribute){
            if (isset($this->attributes[$attribute])){
                  unset($this->attributes[$attribute]);
            }
      }

      public function __toString(){
            $html = "<{$this->tag_name}";
            foreach ($this->attributes as $key => $value){
                  if (empty($value) && in_array($key, array('class','id', 'srcset', 'sizes', 'title', 'alt', 'width', 'height', 'style', 'target', 'type', 'name', 'method', 'action', 'rel', 'media', 'for'))){
                        continue;
                  }

                  $html .= ' ' . $key;
                  if (!empty($value) || in_array($key, array('src','href'))){
                        if ($this->is_single_quote || strpos($value, '"') !== false){
                              $html .= '=\'' . $value . '\'';
                        }
                        else {
                              $html .= '="' . $value . '"';
                        }
                  }
            }

            if (in_array($this->tag_name, self::$single)){
                  $html .= ' />';
            }
            else {
                  $html .= ">{$this->inner_html}</{$this->tag_name}>";
            }

            if ($this->json_encoded){
                  return trim(json_encode($html), '"');
            }

            return $html;
      }

}

?>