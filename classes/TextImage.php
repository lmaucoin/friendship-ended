<?php

/**
 * Builds text to be composited into the background images
 */
class TextImage extends ImagickDraw {
  public $color = false;
  public $fontSize = 38;
  public $fontWeight = 800;
  public $gradient = false;
  public $position = [0,0];
  public $strokeColor = "#006488";
  public $strokeWidth = 1;
  public $scaleSize = [1,2];
  public $text = "";

  function __construct($args = []) {

    // Add the gradient for the one item that needs a gradient
    if(isset($args['gradient'])) {
      $gradient = new Imagick();
      $gradient->newPseudoImage(50, 120, "gradient:{$args['gradient']}");
      $this->pushPattern('gradient', 0, 0, 50, 120);
      $this->composite(Imagick::COMPOSITE_OVER, 0, 0, 50, 120, $gradient);
      $this->popPattern();
      $this->setFillPatternURL('#gradient');
    }
    // Add a fill with the defined color
    elseif(isset($args['color'])) {
      $this->setFillColor($args['color']);
    }

    $fontSize = isset($args['fontSize']) ? $args['fontSize'] : $this->fontSize;
    $this->setFontSize($fontSize);

    $scale = isset($args['scale']) ? $args['scale'] : $this->scaleSize;
    $this->scale($scale[0], $scale[1]);

    $this->setFontWeight($this->fontWeight);
    $this->setStrokeWidth($this->strokeWidth);
    $this->setStrokeColor($this->strokeColor);
    $this->setStrokeAntialias(true);

    $this->annotation($args['position'][0], $args['position'][1], $args['text']);
  }

}
