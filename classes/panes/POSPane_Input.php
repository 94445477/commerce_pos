<?php


class POSPane_Input extends POS_Pane {

  function build(POS $pos, POS_Interface $interface, $js = FALSE) {
    return drupal_get_form('pos_input_form');
  }
}
